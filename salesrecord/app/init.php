<?php
// ---------- Runtime mode ----------
$IS_PROD = true; // flip to false on local dev
if ($IS_PROD) {
  ini_set('display_errors', 0);
  ini_set('log_errors', 1);
  ini_set('error_log', __DIR__ . '/../storage/php_errors.log');
  error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
  ini_set('display_errors', 1);
  ini_set('log_errors', 1);
  ini_set('error_log', __DIR__ . '/../storage/php_errors.log');
  error_reporting(E_ALL);
}

// Sensible defaults
ini_set('default_charset', 'UTF-8');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');

// ---------- Load config FIRST ----------
$config = require __DIR__.'/../config/config.php';

// ---------- PHP timezone (affects PHP date()/strtotime()) ----------
date_default_timezone_set('Asia/Karachi');

// Ensure base_url is absolute in prod for robust cookie scoping
if ($IS_PROD && (!isset($config['base_url']) || !preg_match('~^https?://~i', $config['base_url'] ?? ''))) {
  // Fallback: try to build absolute URL from current request
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $path   = rtrim($config['base_url'] ?? '', '/'); // might be a folder like /salesrecord
  $config['base_url'] = $scheme.'://'.$host.$path;
}

// ---------- HTTPS detector (handles reverse proxies) ----------
function is_https_request(): bool {
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
  $xfp = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
  if (is_string($xfp) && strtolower($xfp) === 'https') return true;
  $xfs = $_SERVER['HTTP_X_FORWARDED_SSL'] ?? '';
  if (is_string($xfs) && strtolower($xfs) === 'on') return true;
  return false;
}

// ---------- Session isolation (avoid conflicts with main site) ----------
$baseUrl = rtrim($config['base_url'] ?? '', '/'); // absolute in prod; can be http://localhost/... in dev
$u = parse_url($baseUrl);
$cookiePath   = isset($u['path']) && $u['path'] !== '' ? $u['path'] : '/';
$cookieDomain = $u['host'] ?? ($_SERVER['HTTP_HOST'] ?? '');

// Give this app its own session name
session_name('salesrecord_sid');

// Scope cookie to the subfolder, secure if HTTPS, HTTP-only, SameSite=Lax
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => $cookiePath,
  'domain'   => $cookieDomain,
  'secure'   => is_https_request(),
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();

// ---------- Upload paths (ALL receipts go in /public/proofs) ----------
$config['upload_dir'] = $config['upload_dir'] ?? (__DIR__ . '/../public/proofs');
$config['upload_url'] = $config['upload_url'] ?? ($baseUrl . '/public/proofs');

// Ensure folders exist (also an error log dir)
@is_dir($config['upload_dir']) || @mkdir($config['upload_dir'], 0775, true);
@is_dir(__DIR__.'/../storage') || @mkdir(__DIR__.'/../storage', 0775, true);

// ---------- DB (PDO) ----------
// Build PDO BEFORE any $pdo->exec() calls
try {
  $pdo = new PDO(
    $config['db']['dsn'],
    $config['db']['user'],
    $config['db']['pass'],
    [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ]
  );
} catch (Throwable $e) {
  if ($IS_PROD) {
    error_log('DB connection error: '.$e->getMessage());
    http_response_code(500);
    exit('Service temporarily unavailable.');
  } else {
    exit('DB connection error: '.$e->getMessage());
  }
}

// Align MySQL session timezone (affects CURDATE(), NOW(), etc. in SQL)
$pdo->exec("SET time_zone = '+05:00'");
$pdo->exec("SET NAMES utf8mb4");

// ---------- Load helpers AFTER PDO exists ----------
require_once __DIR__ . '/helpers/date_window.php';

// ---------- URL helpers ----------
function base_url($path=''){
  global $config;
  return rtrim($config['base_url'],'/').'/'.ltrim($path,'/');
}
function redirect($path){
  header('Location: '.base_url($path));
  exit;
}

// ---------- Auth helpers ----------
function is_logged_in(){ return !empty($_SESSION['user']); }
function user(){ return $_SESSION['user'] ?? null; }
function require_role($role){
  if (!is_logged_in() || (user()['role'] ?? null) !== $role) {
    http_response_code(403); exit('Forbidden');
  }
}

// ---------- CSRF ----------
function csrf_token(){
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}
function csrf_check($token){ return hash_equals($_SESSION['csrf'] ?? '', $token); }

// ---------- Sanitization ----------
function clean($v){ return trim((string)$v); }
