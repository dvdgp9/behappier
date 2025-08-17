<?php
declare(strict_types=1);

// behappier — includes/bootstrap.php
// Carga configuración, inicia sesión, expone $pdo y utilidades básicas.

// Mostrar errores en desarrollo (ajustable si se desea)
ini_set('display_errors', '1');
error_reporting(E_ALL);

$root = dirname(__DIR__);
$envPath = $root . '/.env.php';
$env = file_exists($envPath) ? require $envPath : require $root . '/.env.example.php';

// Constantes/config
$REMEMBER_ME_DAYS = (int)($env['REMEMBER_ME_DAYS'] ?? 30);

// Sesión segura
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'domain' => '',
  'secure' => $secure,
  'httponly' => true,
  'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// Conexión PDO
try {
  $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $env['DB_HOST'], $env['DB_NAME']);
  $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (Throwable $e) {
  // Mensaje amable; en producción se podría registrar en log.
  http_response_code(500);
  echo '<!doctype html><meta charset="utf-8"><style>body{font:16px/1.4 system-ui, -apple-system, Segoe UI, Roboto, sans-serif;padding:24px;color:#4A3F35;background:#FFF8F1}</style>';
  echo '<h1>No se pudo conectar a la base de datos</h1>';
  echo '<p>Revisa tus credenciales en <code>.env.php</code>. Si no existe, copia <code>.env.example.php</code> y rellénalo.</p>';
  if (!file_exists($envPath)) {
    echo '<p><strong>Nota:</strong> No se encontró <code>.env.php</code>; se está usando <code>.env.example.php</code> como plantilla.</p>';
  }
  exit;
}

// Utilidades
function redirect(string $path): void { header('Location: ' . $path); exit; }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// CSRF simple (opcional, usable en formularios)
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
  return $_SESSION['csrf'];
}
function csrf_check(?string $token): bool {
  return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$token);
}
