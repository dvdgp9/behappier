<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';

// Si hay cookie remember y no hay sesión, intentar auto-login
attempt_remembered_login($pdo);
if (current_user_id()) { redirect('home.php'); }

$errors = [];
if (is_post()) {
  if (!csrf_check($_POST['csrf'] ?? '')) {
    $errors[] = 'Token CSRF inválido. Refresca la página.';
  } else {
    $email = trim((string)($_POST['email'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);
    if ($email === '' || $pass === '') {
      $errors[] = 'Introduce tu email y contraseña.';
    } else {
      global $REMEMBER_ME_DAYS;
      if (!login_user($pdo, $email, $pass, $remember, $REMEMBER_ME_DAYS)) {
        $errors[] = 'Credenciales no válidas.';
      } else {
        redirect('home.php');
      }
    }
  }
}
?>
<?php include __DIR__ . '/partials/head.php'; ?>
  <section class="center">
    <div class="card" style="width:min(480px, 100%)">
      <h1 class="h1">Bienvenida/o</h1>
      <p class="text-subtle">Inicia sesión para continuar.</p>
      <?php if ($errors): ?>
        <div class="alert" style="margin-bottom:12px">
          <?= e(implode(' ', $errors)) ?>
        </div>
      <?php endif; ?>
      <form method="post" class="form stack-16" autocomplete="email">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>
          <span>Email</span>
          <input class="input" type="email" name="email" required autofocus>
        </label>
        <label>
          <span>Contraseña</span>
          <input class="input" type="password" name="password" required>
        </label>
        <label style="display:flex; align-items:center; gap:8px">
          <input type="checkbox" name="remember" value="1">
          <small class="help">Recordarme durante <?= (int)$REMEMBER_ME_DAYS ?> días</small>
        </label>
        <button class="btn" type="submit">Entrar</button>
        <div><small class="help">¿No tienes cuenta? <a href="register.php">Crea una</a>.</small></div>
      </form>
    </div>
  </section>
</main>
</body>
</html>
