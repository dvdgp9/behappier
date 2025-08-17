<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require_login();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
  <section class="hero desenfocado">
    <div class="container" style="padding:18px 0">
      <?php $u = current_user($pdo); $displayName = trim((string)($u['nombre'] ?? '')); ?>
      <h1 class="brand" style="margin:0"><?= e($displayName !== '' ? $displayName : 'Behappier') ?>,</h1>
      <p class="subtitle">tu momento de pausa</p>
      <div class="durations" style="margin-top:12px">
        <a class="btn duration" href="task.php?d=1" aria-label="Empezar una pausa de 1 minuto">1′</a>
        <a class="btn duration" href="task.php?d=5" aria-label="Empezar una pausa de 5 minutos">5′</a>
        <a class="btn duration" href="task.php?d=10" aria-label="Empezar una pausa de 10 a 15 minutos">10–15′</a>
      </div>
    </div>
  </section>
</main>
</body>
</html>
