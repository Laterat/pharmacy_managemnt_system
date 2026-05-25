<?php
declare(strict_types=1);

$host = 'localhost';
$dbName = 'pharmacy_inventory';
$dbUser = 'root';
$dbPass = '12qwasZX!@';

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$projectFolder = basename(dirname(__DIR__));
$projectPath = '/' . $projectFolder . '/';
$projectPosition = strpos($scriptName, $projectPath);
$appBasePath = $projectPosition === false ? '' : substr($scriptName, 0, $projectPosition + strlen($projectFolder) + 1);
define('APP_BASE_PATH', rtrim($appBasePath, '/'));

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Check includes/db.php settings.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function app_url(string $path = ''): string
{
    return APP_BASE_PATH . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    if (!preg_match('/^https?:\/\//i', $path)) {
        $path = app_url($path);
    }
    header("Location: {$path}");
    exit;
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect('auth/login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        redirect('dashboard.php?error=admin-only');
    }
}

function current_user_name(): string
{
    return $_SESSION['fullname'] ?? 'User';
}

function current_user_role(): string
{
    return $_SESSION['role'] ?? 'guest';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
