<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>behappier</title>
  <link rel="icon" href="assets/brand/favicon.png" sizes="32x32">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/iconoir@latest/css/iconoir.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Patrick+Hand&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <img src="assets/brand/Logo-behappier.png" alt="behappier" class="logo" width="140" height="auto">
    <?php if (function_exists('current_user_id') && current_user_id()): ?>
      <a href="logout.php" class="icon-btn" aria-label="Cerrar sesión" title="Cerrar sesión"><i class="iconoir-logout"></i></a>
    <?php endif; ?>
  </div>
</header>
<main class="container">
<?php if (function_exists('current_user_id') && current_user_id()): ?>
  <a href="history.php" class="fab" aria-label="Historial" title="Historial"><i class="iconoir-notes"></i></a>
<?php endif; ?>
