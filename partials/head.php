<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>behappier</title>
  <link rel="icon" href="assets/brand/favicon.png" sizes="32x32">
  <link rel="apple-touch-icon" href="assets/brand/Logo-behappier-180.png" sizes="180x180">
  <link rel="manifest" href="assets/manifest.webmanifest">
  <meta name="theme-color" content="#4A3F35">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/iconoir@latest/css/iconoir.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Patrick+Hand&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css?v=<?= filemtime(__DIR__ . '/../assets/styles.css') ?>">
<?php
  $hasUser = (function_exists('current_user_id') && current_user_id());
  $currentPath = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
?>
</head>
<body class="<?= $hasUser ? 'with-bottom-nav' : '' ?>">
<header class="site-header">
  <div class="container header-inner">
    <div class="brandline">
      <img src="assets/brand/Logo-behappier-blanco.png" alt="behappier" class="logo" width="140" height="auto">
      <span class="site-title">Behappier</span>
    </div>
    <?php if (function_exists('current_user_id') && current_user_id()): ?>
      <a href="account.php" class="icon-btn" aria-label="Mi cuenta" title="Mi cuenta"><i class="iconoir-user"></i></a>
    <?php endif; ?>
  </div>
</header>
<main class="container">
<?php if ($hasUser): ?>
  <nav class="bottom-nav" role="navigation" aria-label="Navegación primaria">
    <a href="home.php" class="nav-item <?= $currentPath === 'home.php' || $currentPath === '' || $currentPath === 'index.php' ? 'active' : '' ?>" aria-current="<?= ($currentPath === 'home.php' || $currentPath === '' || $currentPath === 'index.php') ? 'page' : 'false' ?>">
      <i class="iconoir-home" aria-hidden="true"></i>
      <span class="label">Inicio</span>
    </a>
    <a href="history.php" class="nav-item <?= $currentPath === 'history.php' ? 'active' : '' ?>" aria-current="<?= ($currentPath === 'history.php') ? 'page' : 'false' ?>">
      <i class="iconoir-notes" aria-hidden="true"></i>
      <span class="label">Historial</span>
    </a>
    <a href="account.php" class="nav-item <?= $currentPath === 'account.php' ? 'active' : '' ?>" aria-current="<?= ($currentPath === 'account.php') ? 'page' : 'false' ?>">
      <i class="iconoir-user" aria-hidden="true"></i>
      <span class="label">Cuenta</span>
    </a>
  </nav>
<?php endif; ?>
