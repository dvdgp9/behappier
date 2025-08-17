<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require_login();
?>
<?php include __DIR__ . '/partials/head.php'; ?>
  <section class="hero desenfocado center">
    <div class="container" style="padding:18px 0">
      <h1 class="brand" style="margin:0">¿Cuánto tiempo te regalas hoy?</h1>
      <div class="duration-cards" role="group" aria-label="Duración de la pausa">
        <a class="card duration-card" href="task.php?d=1" aria-label="Empezar una pausa de 1 minuto">
          <div class="time">1′</div>
          <div class="desc">Un respiro rápido</div>
        </a>
        <a class="card duration-card" href="task.php?d=5" aria-label="Empezar una pausa de 5 minutos">
          <div class="time">5′</div>
          <div class="desc">Recarga breve</div>
        </a>
        <a class="card duration-card" href="task.php?d=10" aria-label="Empezar una pausa de 10 minutos">
          <div class="time">10′</div>
          <div class="desc">Pausa profunda</div>
        </a>
      </div>
    </div>
  </section>
</main>
</body>
</html>
