<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require_login();
$uid = (int)current_user_id();

$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;
$st = $pdo->prepare('SELECT e.id, e.created_at, e.duration, e.mood, e.note, t.title
                     FROM entries e
                     JOIN tasks t ON t.id = e.task_id
                     WHERE e.user_id = ?
                     ORDER BY e.created_at DESC
                     LIMIT ' . $limit);
$st->execute([$uid]);
$rows = $st->fetchAll();
function rel_day_label(string $createdAt): string {
  try {
    $dt = new DateTime($createdAt);
  } catch (Throwable $e) {
    return $createdAt;
  }
  $today = new DateTime('today');
  $d = (int)$today->diff((clone $dt)->setTime(0,0,0))->days;
  if ($dt > new DateTime()) { return 'hoy'; }
  if ($d === 0) return 'hoy';
  if ($d === 1) return 'ayer';
  if ($d > 30) return 'hace +30 días';
  return 'hace ' . $d . ' días';
}
?>
<?php include __DIR__ . '/partials/head.php'; ?>
  <section class="center center-top">
    <div class="card history desenfocado">
      <div class="history-header">
        <h1 class="h1">Historial</h1>
      </div>

      <?php if (!$rows): ?>
        <div class="alert">Aún no tienes registros. Empieza con una pausa de 1′.</div>
      <?php else: ?>
        <div class="stack-16 history-list">
          <?php foreach ($rows as $r): ?>
            <article class="card history-item">
              <div class="dur-col">
                <span class="badge badge-duration"><?=$r['duration']==10 ? '10–15′' : e((string)$r['duration']).'′'?></span>
              </div>
              <div class="content">
                <div class="history-title"><?= e($r['title']) ?></div>
                <?php if (!is_null($r['mood'])): ?>
                  <div class="history-mood"><small class="help">Ánimo: <?= (int)$r['mood'] ?>/5</small></div>
                <?php endif; ?>
                <?php if (!empty($r['note'])): ?>
                  <div class="history-note"><small class="help">«<?= e($r['note']) ?>»</small></div>
                <?php endif; ?>
              </div>
              <div class="meta date-col"><span class="chip"><?= e(rel_day_label((string)$r['created_at'])) ?></span></div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
</body>
</html>
