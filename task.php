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

// Guardar entrada tras finalizar
if (is_post() && ($_POST['action'] ?? '') === 'save') {
  if (!csrf_check($_POST['csrf'] ?? '')) {
    $errors[] = 'Token CSRF inválido.';
  } else {
    $task_id = (int)($_POST['task_id'] ?? 0);
    $mood = $_POST['mood'] !== '' ? (int)$_POST['mood'] : null;
    $note = trim((string)($_POST['note'] ?? ''));
    if ($task_id <= 0) {
      $errors[] = 'Tarea no válida.';
    } else {
      $st = $pdo->prepare('INSERT INTO entries (user_id, task_id, duration, mood, note) VALUES (?,?,?,?,?)');
      $st->execute([$uid, $task_id, $duration, $mood, ($note !== '' ? $note : null)]);
      $success = true;
    }
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
// Título según duración elegida
$titleForDuration = ($duration === 1)
  ? 'Tu respiro de 1 minuto'
  : (($duration === 5)
    ? 'Recarga y conecta en 5 minutos'
    : '10 minutos de conexión profunda');
?>
<?php include __DIR__ . '/partials/head.php'; ?>
  <section class="center">
    <div class="card desenfocado" style="width:min(680px,100%)">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:8px">
        <h1 class="h1" style="margin:0"><?= e($titleForDuration) ?></h1>
      </div>

      <div class="stack-16">
        
        <?php if ($success): ?>
          <div class="success">¡Guardado! Gracias por cuidarte hoy. <a href="history.php">Ver historial</a></div>
        <?php endif; ?>
        <?php if ($errors): ?>
          <div class="alert"><?= e(implode(' ', $errors)) ?></div>
        <?php endif; ?>

        <?php if ($task): ?>
          <article class="card desenfocado">
            <h2 class="h1" style="font-size:24px; margin-bottom:6px"><?= e($task['title']) ?></h2>
            <p class="text-subtle" style="margin:8px 0 8px"><?= e($task['guidance']) ?></p>
            <?php
              $stepsArr = null;
              if (!empty($task['steps'])) {
                $decoded = json_decode((string)$task['steps'], true);
                if (is_array($decoded)) { $stepsArr = $decoded; }
              }
            ?>
            <?php if ($stepsArr): ?>
              <ol style="margin:0 0 8px 18px; padding:0; color:#4A3F35">
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

          <form method="post" class="form stack-16 card desenfocado" id="post-timer" style="display:none">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
            <div>
              <label><strong>¿Cómo te sientes ahora?</strong></label>
              <div style="display:flex; gap:8px; margin-top:6px">
                <?php for ($i=1; $i<=5; $i++): ?>
                  <label style="display:flex; align-items:center; gap:6px; background:#fff; padding:6px 10px; border-radius:12px; border:1px solid #eee">
                    <input type="radio" name="mood" value="<?= $i ?>"> <span><?= $i ?></span>
                  </label>
                <?php endfor; ?>
              </div>
            </div>
            <label>
              <span>Nota (opcional)</span>
              <input class="input" type="text" name="note" maxlength="240" placeholder="Una línea para recordarlo...">
            </label>
            <div style="display:flex; gap:8px; align-items:center">
              <button class="btn" type="submit">Guardar</button>
              <a class="btn secondary" href="?d=<?= $duration ?>">Conectar de nuevo</a>
            </div>
          </form>
        <?php else: ?>
          <div class="alert">No hay tareas para esta duración por ahora.</div>
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
