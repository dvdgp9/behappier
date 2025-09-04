<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require_login();

$uid = (int)current_user_id();

function parse_duration(string $raw): int {
  $d = (int)$raw;
  return in_array($d, [1,5,10], true) ? $d : 1;
}

$duration = parse_duration((string)($_GET['d'] ?? '1'));
$errors = [];
$success = false;

// Endpoints POST
if (is_post()) {
  $action = (string)($_POST['action'] ?? '');
  if ($action === 'autosave') {
    // Guardado inmediato al terminar (mood/note nulos)
    header('Content-Type: application/json; charset=utf-8');
    if (!csrf_check($_POST['csrf'] ?? '')) {
      echo json_encode(['ok' => false, 'error' => 'csrf']);
      exit;
    }
    $task_id = (int)($_POST['task_id'] ?? 0);
    $dur = (int)($_POST['duration'] ?? $duration);
    if ($task_id <= 0 || !in_array($dur, [1,5,10], true)) {
      echo json_encode(['ok' => false, 'error' => 'bad_params']);
      exit;
    }
    $st = $pdo->prepare('INSERT INTO entries (user_id, task_id, duration, mood, note) VALUES (?,?,?,?,?)');
    $st->execute([$uid, $task_id, $dur, null, null]);
    $entryId = (int)$pdo->lastInsertId();
    echo json_encode(['ok' => true, 'entry_id' => $entryId]);
    exit;
  }
  if ($action === 'save') {
    // Guardar/actualizar tras el formulario post-timer (sin mood, solo entry)
    if (!csrf_check($_POST['csrf'] ?? '')) {
      $errors[] = 'Token CSRF inválido.';
    } else {
      $task_id = (int)($_POST['task_id'] ?? 0);
      $entry_id = isset($_POST['entry_id']) ? (int)$_POST['entry_id'] : 0;
      if ($task_id <= 0) {
        $errors[] = 'Tarea no válida.';
      } else {
        if ($entry_id > 0) {
          // La entrada ya existe desde autosave, no necesita mood aquí
          $success = true;
        } else {
          // Inserta directamente si no hubo autosave
          $st = $pdo->prepare('INSERT INTO entries (user_id, task_id, duration) VALUES (?,?,?)');
          $st->execute([$uid, $task_id, $duration]);
          $success = true;
        }
      }
    }
  }
  if ($action === 'daily_mood') {
    // Guardar/actualizar estado anímico diario
    header('Content-Type: application/json; charset=utf-8');
    if (!csrf_check($_POST['csrf'] ?? '')) {
      echo json_encode(['ok' => false, 'error' => 'csrf']);
      exit;
    }
    $mood = (int)($_POST['mood'] ?? 0);
    $note = trim((string)($_POST['note'] ?? ''));
    if ($mood < 1 || $mood > 5) {
      echo json_encode(['ok' => false, 'error' => 'invalid_mood']);
      exit;
    }
    $today = date('Y-m-d');
    // Intentar actualizar primero
    $st = $pdo->prepare('UPDATE daily_moods SET mood=?, note=?, updated_at=CURRENT_TIMESTAMP WHERE user_id=? AND date=?');
    $st->execute([$mood, ($note !== '' ? $note : null), $uid, $today]);
    if ($st->rowCount() === 0) {
      // No existía, insertar nuevo
      $st2 = $pdo->prepare('INSERT INTO daily_moods (user_id, date, mood, note) VALUES (?,?,?,?)');
      $st2->execute([$uid, $today, $mood, ($note !== '' ? $note : null)]);
    }
    echo json_encode(['ok' => true]);
    exit;
  }
}

// Sugerir una tarea por duración evitando las últimas N=5 del usuario
function suggest_task(PDO $pdo, int $uid, int $duration): ?array {
  // Obtener últimas 5 tareas de este duration
  $st = $pdo->prepare('SELECT task_id FROM entries WHERE user_id=? AND duration=? ORDER BY created_at DESC LIMIT 5');
  $st->execute([$uid, $duration]);
  $avoid = array_column($st->fetchAll(), 'task_id');

  if ($avoid) {
    $in = implode(',', array_fill(0, count($avoid), '?'));
    $params = array_merge([$duration], $avoid);
    $sql = "SELECT id, title, guidance, category, steps FROM tasks WHERE duration=? AND active=1 AND id NOT IN ($in) ORDER BY RAND() LIMIT 1";
    $st2 = $pdo->prepare($sql);
    $st2->execute($params);
    $t = $st2->fetch();
    if ($t) { return $t; }
  }
  // Fallback sin exclusión si no hay suficientes tareas
  $st3 = $pdo->prepare('SELECT id, title, guidance, category, steps FROM tasks WHERE duration=? AND active=1 ORDER BY RAND() LIMIT 1');
  $st3->execute([$duration]);
  $t = $st3->fetch();
  return $t ?: null;
}

$task = suggest_task($pdo, $uid, $duration);

// Obtener estado anímico del día actual
$today = date('Y-m-d');
$st_mood = $pdo->prepare('SELECT mood, note FROM daily_moods WHERE user_id=? AND date=?');
$st_mood->execute([$uid, $today]);
$todayMood = $st_mood->fetch();
// Helpers para representar el estado anímico
if (!function_exists('getMoodEmoji')) {
  function getMoodEmoji(int $mood): string {
    $emojis = [1 => '😞', 2 => '😔', 3 => '😐', 4 => '😌', 5 => '😊'];
    return $emojis[$mood] ?? '😐';
  }
}
if (!function_exists('getMoodText')) {
  function getMoodText(int $mood): string {
    $texts = [1 => 'Mal', 2 => 'Regular', 3 => 'Normal', 4 => 'Bien', 5 => 'Muy bien'];
    return $texts[$mood] ?? 'Normal';
  }
}
// Título según duración elegida
$titleForDuration = ($duration === 1)
  ? 'Tu respiro de 1 minuto'
  : (($duration === 5)
    ? 'Recarga y conecta en 5 minutos'
    : '10 minutos de conexión profunda');
// Si es una petición AJAX, devolvemos solo el bloque del ejercicio
if (isset($_GET['ajax']) && (int)$_GET['ajax'] === 1) {
  ob_start();
  if ($task): ?>
    <div id="exercise-block">
      <article class="card desenfocado">
        <h2 class="h1" style="font-size:24px; margin-bottom:6px"><?= e($task['title']) ?></h2>
        <?php
          $stepsArr = null;
          if (!empty($task['steps'])) {
            $decoded = json_decode((string)$task['steps'], true);
            if (is_array($decoded)) { $stepsArr = $decoded; }
          }
        ?>
        <?php if ($stepsArr): ?>
          <ol style="margin:0 0 8px 18px; padding:0; color:#4A3F35; font-size:15px">
            <?php foreach ($stepsArr as $s): ?>
              <li style="margin:4px 0;"><?= e((string)$s) ?></li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
        <div id="timer" data-mins="<?= $duration ?>" class="stack-16">
          <div style="font-family:'Patrick Hand',cursive; font-size:42px; letter-spacing:1px">00:00</div>
          <div style="display:flex; gap:8px; flex-wrap:wrap">
            <button class="btn" data-action="start" type="button">Empezar</button>
            <button class="btn secondary" data-action="pause" type="button">Pausar</button>
            <button class="btn secondary" data-action="finish" type="button">Terminar</button>
          </div>
        </div>
      </article>

      <form method="post" class="form" id="post-timer" style="display:none">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
        <input type="hidden" name="entry_id" value="">
        <div style="text-align:center; padding:20px">
          <p style="color:#6b6158; margin-bottom:16px">¡Ejercicio completado!</p>
          <button class="btn" type="submit">Continuar</button>
        </div>
      </form>
    </div>
  <?php else: ?>
    <div id="exercise-block">
      <div class="alert">No hay tareas para esta duración por ahora.</div>
    </div>
  <?php endif; 
  echo ob_get_clean();
  exit;
}
?>
<?php include __DIR__ . '/partials/head.php'; ?>
  <section class="center">
    <div class="card desenfocado" style="width:min(680px,100%)">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:8px">
        <h1 class="h1" style="margin:0"><?= e($titleForDuration) ?></h1>
        <a class="icon-btn js-swap-exercise" href="?d=<?= $duration ?>&swap=1" aria-label="Cambiar ejercicio" title="Cambiar ejercicio">
          <i class="iconoir-refresh"></i>
        </a>
      </div>

        <div class="stack-16">
        
        <?php if ($errors): ?>
          <div class="alert"><?= e(implode(' ', $errors)) ?></div>
        <?php endif; ?>

        <?php if ($task): ?>
          <div id="exercise-block">
          <article class="card desenfocado">
            <h2 class="h1" style="font-size:24px; margin-bottom:6px"><?= e($task['title']) ?></h2>
            <?php
              $stepsArr = null;
              if (!empty($task['steps'])) {
                $decoded = json_decode((string)$task['steps'], true);
                if (is_array($decoded)) { $stepsArr = $decoded; }
              }
            ?>
            <?php if ($stepsArr): ?>
              <ol style="margin:0 0 8px 18px; padding:0; color:#4A3F35; font-size:15px">
                <?php foreach ($stepsArr as $s): ?>
                  <li style="margin:4px 0;"><?= e((string)$s) ?></li>
                <?php endforeach; ?>
              </ol>
            <?php endif; ?>
            <div id="timer" data-mins="<?= $duration ?>" class="stack-16">
              <div style="font-family:'Patrick Hand',cursive; font-size:42px; letter-spacing:1px">00:00</div>
              <div style="display:flex; gap:8px; flex-wrap:wrap">
                <button class="btn primary" data-action="toggle" type="button" aria-label="Empezar">
                  <i class="iconoir-play"></i> <span class="btn-label">Empezar</span>
                </button>
                <button class="btn ghost small" data-action="reset" type="button" aria-label="Reiniciar" disabled>
                  <i class="iconoir-refresh"></i> Reiniciar
                </button>
                <button class="btn secondary small" data-action="finish" type="button" aria-label="Terminar">
                  <i class="iconoir-square"></i> Terminar
                </button>
              </div>
            </div>
          </article>

          <!-- Modal de estado anímico diario -->
          <div class="modal-overlay" id="daily-mood-modal" style="display:none">
            <div class="modal card desenfocado">
              <?php if ($todayMood): ?>
                <h3 class="h1" style="font-size:20px; margin-bottom:12px">Hoy te sientes:</h3>
                <div class="current-mood" style="text-align:center; margin-bottom:16px">
                  <span class="mood-display"><?= getMoodEmoji((int)$todayMood['mood']) ?> <?= getMoodText((int)$todayMood['mood']) ?></span>
                </div>
                <p style="margin-bottom:16px; color:#6b6158">¿Ha cambiado algo?</p>
              <?php else: ?>
                <h3 class="h1" style="font-size:20px; margin-bottom:8px">¿Cómo te sientes hoy?</h3>
                <p style="margin-bottom:16px; color:#6b6158">Tu registro diario te ayudará a conocerte mejor</p>
              <?php endif; ?>
              
              <div class="mood-options" style="display:grid; gap:8px; margin-bottom:16px">
                <?php 
                $moods = [
                  1 => ['emoji' => '😞', 'text' => 'Mal'],
                  2 => ['emoji' => '😔', 'text' => 'Regular'], 
                  3 => ['emoji' => '😐', 'text' => 'Normal'],
                  4 => ['emoji' => '😌', 'text' => 'Bien'],
                  5 => ['emoji' => '😊', 'text' => 'Muy bien']
                ];
                foreach ($moods as $value => $mood): 
                  $isSelected = $todayMood && (int)$todayMood['mood'] === $value;
                ?>
                  <label class="mood-option <?= $isSelected ? 'selected' : '' ?>">
                    <input type="radio" name="daily_mood" value="<?= $value ?>" <?= $isSelected ? 'checked' : '' ?>>
                    <span class="emoji"><?= $mood['emoji'] ?></span>
                    <span class="label"><?= $mood['text'] ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              
              <label style="margin-bottom:16px">
                <span style="color:#6b6158; font-size:14px">¿Algo que quieras recordar de hoy? (opcional)</span>
                <input class="input" type="text" name="daily_note" maxlength="100" placeholder="Una palabra o frase..." value="<?= e($todayMood['note'] ?? '') ?>" style="margin-top:4px">
              </label>
              
              <div style="display:flex; gap:10px; align-items:center; margin-top:12px">
                <button class="btn primary" id="save-daily-mood" type="button"><i class="iconoir-check"></i> Guardar</button>
                <?php if ($todayMood): ?>
                  <button class="btn secondary" id="keep-mood" type="button">Mantener así</button>
                <?php else: ?>
                  <button class="btn ghost" id="skip-mood" type="button">Saltar por hoy</button>
                <?php endif; ?>
              </div>
            </div>
          </div>
          
          <!-- Formulario simple para guardar la entrada del ejercicio -->
          <form method="post" class="form" id="post-timer" style="display:none">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
            <input type="hidden" name="entry_id" value="">
            <div style="text-align:center; padding:20px">
              <p style="color:#6b6158; margin-bottom:16px">¡Ejercicio completado!</p>
              <button class="btn" type="submit">Continuar</button>
            </div>
          </form>
          </div>
        <?php else: ?>
          <div id="exercise-block"><div class="alert">No hay tareas para esta duración por ahora.</div></div>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <script src="assets/app.js"></script>
  <script>
    window.BEH_DURATION = <?= $duration ?>;
  </script>
</main>
</body>
</html>
