<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require_login();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
  <section class="center">
    <div class="card" style="text-align:center; width:min(680px, 100%)">
      <h1 class="h1">Tu momento de pausa</h1>
      <p class="text-subtle">Elige una duración y te sugerimos algo amable para hacer.</p>
      <div class="stack-16" style="margin-top:16px">
        <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:center">
          <a class="btn" href="task.php?d=1">1′</a>
          <a class="btn" href="task.php?d=5">5′</a>
          <a class="btn" href="task.php?d=10">10–15′</a>
        </div>
        <div style="display:flex; gap:8px; justify-content:center">
          <a class="btn secondary" href="history.php">Historial</a>
          <a class="btn secondary" href="logout.php">Salir</a>
        </div>
      </div>
    </div>
  </section>
</main>
</body>
</html>
