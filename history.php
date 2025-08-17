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
?>
<?php include __DIR__ . '/partials/head.php'; ?>
  <section class="center">
    <div class="card" style="width:min(820px,100%)">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:8px">
        <h1 class="h1" style="margin:0">Historial</h1>
      </div>

      <?php if (!$rows): ?>
        <div class="alert">Aún no tienes registros. Empieza con una pausa de 1′.</div>
      <?php else: ?>
        <div class="stack-16">
          <?php foreach ($rows as $r): ?>
            <article class="card" style="display:flex; gap:12px; align-items:center">
              <div style="min-width:64px; text-align:center; font-family:'Patrick Hand',cursive; font-size:20px">
                <?= (int)$r['duration'] === 10 ? '10–15′' : e((string)$r['duration']) . '′' ?>
              </div>
              <div style="flex:1 1 auto">
                <div style="font-weight:600; margin-bottom:4px"><?= e($r['title']) ?></div>
                <?php if (!is_null($r['mood'])): ?>
                  <small class="help">Ánimo: <?= (int)$r['mood'] ?>/5</small>
                <?php endif; ?>
                <?php if (!empty($r['note'])): ?>
                  <div><small class="help">«<?= e($r['note']) ?>»</small></div>
                <?php endif; ?>
              </div>
              <div style="min-width:150px; text-align:right"><small class="help"><?= e($r['created_at']) ?></small></div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
</body>
</html>
