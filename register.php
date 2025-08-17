<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';

if (current_user_id()) { redirect('home.php'); }

$errors = [];
$ok = false;
if (is_post()) {
  if (!csrf_check($_POST['csrf'] ?? '')) {
    $errors[] = 'Token CSRF inválido. Refresca la página.';
  } else {
    $email = trim((string)($_POST['email'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');
    if ($email === '' || $pass === '') {
      $errors[] = 'Completa email y contraseña.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Email no válido.';
    } elseif ($pass !== $pass2) {
      $errors[] = 'Las contraseñas no coinciden.';
    } else {
      try {
        $uid = register_user($pdo, $email, $pass);
        $_SESSION['uid'] = $uid;
        redirect('home.php');
      } catch (Throwable $e) {
        if (strpos($e->getMessage(), 'uq_users_email') !== false) {
          $errors[] = 'Ese email ya está registrado.';
        } else {
          $errors[] = 'No se pudo crear la cuenta. Intenta más tarde.';
        }
      }
    }
  }
}
?>
<?php include __DIR__ . '/partials/head.php'; ?>
  <section class="center">
    <div class="card" style="width:min(520px, 100%)">
      <h1 class="h1">Crear cuenta</h1>
      <p class="text-subtle">Solo necesitas un email y una contraseña.</p>
      <?php if ($errors): ?>
        <div class="alert" style="margin-bottom:12px"><?= e(implode(' ', $errors)) ?></div>
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
        <label>
          <span>Repite la contraseña</span>
          <input class="input" type="password" name="password2" required>
        </label>
        <button class="btn" type="submit">Crear cuenta</button>
        <div><small class="help">¿Ya tienes cuenta? <a href="index.php">Inicia sesión</a>.</small></div>
      </form>
    </div>
  </section>
</main>
</body>
</html>
