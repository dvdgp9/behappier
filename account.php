<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';

require_login();

$errors = [];
$success = '';

$u = current_user($pdo);
if (!$u) { redirect('index.php'); }

if (is_post()) {
  if (!csrf_check($_POST['csrf'] ?? '')) {
    $errors[] = 'Token CSRF inválido. Refresca la página.';
  } else {
    $current = (string)($_POST['current'] ?? '');
    $new = (string)($_POST['new'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');

    if ($current === '' || $new === '' || $confirm === '') {
      $errors[] = 'Rellena todos los campos.';
    }
    if (strlen($new) < 8) {
      $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
    }
    if ($new !== $confirm) {
      $errors[] = 'La confirmación no coincide.';
    }

    // Verificar contraseña actual
    if (!$errors) {
      if (!password_verify($current, $u['password_hash'])) {
        $errors[] = 'Tu contraseña actual no es correcta.';
      }
    }

    if (!$errors) {
      // Actualizar hash
      $hash = password_hash($new, PASSWORD_DEFAULT);
      $st = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
      $st->execute([$hash, (int)$u['id']]);

      // Invalidar "Recordarme" en todos los dispositivos
      $del = $pdo->prepare('DELETE FROM auth_tokens WHERE user_id = ?');
      $del->execute([(int)$u['id']]);
      clear_remember_cookie();

      $success = 'Tu contraseña se ha actualizado. Por seguridad, se cerrarán sesiones recordadas en otros dispositivos.';
    }
  }
}
?>
<?php include __DIR__ . '/partials/head.php'; ?>
  <section class="center">
    <div class="card desenfocado" style="width:min(520px, 100%)">
      <h1 class="h1">Mi cuenta</h1>
      <p class="text-subtle">Aquí puedes ver tu información y cambiar tu contraseña.</p>

      <div class="stack-16" style="margin:12px 0 20px">
        <div><strong>Nombre:</strong> <?= e((string)$u['nombre']) ?></div>
        <div><strong>Email:</strong> <?= e((string)$u['email']) ?></div>
        <div><strong>Miembro desde:</strong> <?php
          try {
            $dt = new DateTimeImmutable((string)$u['created_at']);
            echo e($dt->format('d/m/Y'));
          } catch (Throwable $e) {
            echo e((string)$u['created_at']);
          }
        ?></div>
      </div>

      <?php if ($errors): ?>
        <div class="alert" style="margin-bottom:12px">
          <?= e(implode(' ', $errors)) ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="success" style="margin-bottom:12px">
          <?= e($success) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="form stack-16" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <fieldset class="stack-16" style="border:0; padding:0; margin:0">
          <legend class="h2" style="margin-bottom:6px">Cambiar contraseña</legend>
          <label>
            <span>Contraseña actual</span>
            <input class="input" type="password" name="current" required autocomplete="current-password">
          </label>
          <label>
            <span>Nueva contraseña</span>
            <input class="input" type="password" name="new" required minlength="8" autocomplete="new-password">
          </label>
          <label>
            <span>Confirmar nueva</span>
            <input class="input" type="password" name="confirm" required minlength="8" autocomplete="new-password">
          </label>
          <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; margin-top:10px">
            <button class="btn" type="submit">Actualizar contraseña</button>
            <a class="btn secondary" href="logout.php">Cerrar sesión</a>
          </div>
        </fieldset>
      </form>
    </div>
  </section>
</main>
</body>
</html>
