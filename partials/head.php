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
    <div class="brandline">
      <img src="assets/brand/Logo-behappier-blanco.png" alt="behappier" class="logo" width="140" height="auto">
      <span class="site-title">Behappier</span>
    </div>
    <?php if (function_exists('current_user_id') && current_user_id()): ?>
      <a href="logout.php" class="icon-btn" aria-label="Cerrar sesión" title="Cerrar sesión">
        <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="currentColor">
          <path d="M12 12H19M19 12L16 15M19 12L16 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
          <path d="M19 6V5C19 3.89543 18.1046 3 17 3H7C5.89543 3 5 3.89543 5 5V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
      </a>
    <?php endif; ?>
  </div>
</header>
<main class="container">
<?php if (function_exists('current_user_id') && current_user_id()): ?>
  <a href="history.php" class="icon-btn history" aria-label="Historial" title="Historial"><i class="iconoir-notes"></i></a>
<?php endif; ?>
