<?php
declare(strict_types=1);

// behappier — includes/auth.php
// Manejo de usuarios, login, registro y "Recordarme".

require_once __DIR__ . '/bootstrap.php';

const REMEMBER_COOKIE = 'remember';

function current_user_id(): ?int {
  return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
}

function require_login(): void {
  if (!current_user_id()) {
    redirect('index.php');
  }
}

function find_user_by_email(PDO $pdo, string $email): ?array {
  $st = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
  $st->execute([$email]);
  $u = $st->fetch();
  return $u ?: null;
}

function register_user(PDO $pdo, string $email, string $password): int {
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $st = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
  $st->execute([$email, $hash]);
  return (int)$pdo->lastInsertId();
}

function verify_user(PDO $pdo, string $email, string $password): ?array {
  $u = find_user_by_email($pdo, $email);
  if ($u && password_verify($password, $u['password_hash'])) {
    return $u;
  }
  return null;
}

function random_token(int $bytes = 32): string { return bin2hex(random_bytes($bytes)); }

function create_remember_token(PDO $pdo, int $userId, int $days): string {
  $selector = bin2hex(random_bytes(6)); // 12 hex chars
  $validator = bin2hex(random_bytes(32)); // 64 hex chars
  $hash = hash('sha256', $validator);
  $expires = (new DateTimeImmutable('+' . $days . ' days'))->format('Y-m-d H:i:s');
  $st = $pdo->prepare('INSERT INTO auth_tokens (user_id, selector, validator_hash, expires_at) VALUES (?,?,?,?)');
  $st->execute([$userId, $selector, $hash, $expires]);
  // Cookie valor: selector:validator
  return $selector . ':' . $validator;
}

function delete_token_by_selector(PDO $pdo, string $selector): void {
  $st = $pdo->prepare('DELETE FROM auth_tokens WHERE selector = ?');
  $st->execute([$selector]);
}

function set_remember_cookie(string $token, int $days, bool $secure): void {
  $expires = time() + 60 * 60 * 24 * $days;
  setcookie(REMEMBER_COOKIE, $token, [
    'expires' => $expires,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}

function clear_remember_cookie(): void {
  setcookie(REMEMBER_COOKIE, '', [ 'expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax' ]);
}

function attempt_remembered_login(PDO $pdo): void {
  if (current_user_id()) { return; }
  if (empty($_COOKIE[REMEMBER_COOKIE])) { return; }
  $parts = explode(':', (string)$_COOKIE[REMEMBER_COOKIE], 2);
  if (count($parts) !== 2) { return; }
  [$selector, $validator] = $parts;
  $st = $pdo->prepare('SELECT * FROM auth_tokens WHERE selector = ? AND expires_at > NOW() LIMIT 1');
  $st->execute([$selector]);
  $row = $st->fetch();
  if (!$row) { return; }
  $hash = hash('sha256', $validator);
  if (!hash_equals($row['validator_hash'], $hash)) { return; }
  // Éxito: inicia sesión y rota token
  $_SESSION['uid'] = (int)$row['user_id'];
  delete_token_by_selector($pdo, $selector);
  $token = create_remember_token($pdo, (int)$row['user_id'], (int)($_SESSION['REMEMBER_ME_DAYS'] ?? 30));
  $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  set_remember_cookie($token, (int)($_SESSION['REMEMBER_ME_DAYS'] ?? 30), $secure);
}

function login_user(PDO $pdo, string $email, string $password, bool $remember, int $rememberDays): bool {
  $u = verify_user($pdo, $email, $password);
  if (!$u) { return false; }
  $_SESSION['uid'] = (int)$u['id'];
  $_SESSION['REMEMBER_ME_DAYS'] = $rememberDays;
  if ($remember) {
    $token = create_remember_token($pdo, (int)$u['id'], $rememberDays);
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    set_remember_cookie($token, $rememberDays, $secure);
  }
  return true;
}

function logout_user(PDO $pdo): void {
  // Invalidar token actual si existe
  if (!empty($_COOKIE[REMEMBER_COOKIE])) {
    $parts = explode(':', (string)$_COOKIE[REMEMBER_COOKIE], 2);
    if (count($parts) === 2) { delete_token_by_selector($pdo, $parts[0]); }
  }
  clear_remember_cookie();
  $_SESSION = [];
  if (session_status() === PHP_SESSION_ACTIVE) { session_destroy(); }
}
