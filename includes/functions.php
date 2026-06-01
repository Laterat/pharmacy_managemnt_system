<?php
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function format_price($price): string
{
    return number_format((float) $price, 2);
}

function get_total_stock(mysqli $conn): int
{
    $stmt = mysqli_prepare($conn, 'SELECT COALESCE(SUM(stock_quantity), 0) AS total FROM medicines');
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return (int) ($row['total'] ?? 0);
}

function get_low_stock_count(mysqli $conn): int
{
    $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM medicines WHERE stock_quantity <= min_stock_level');
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return (int) ($row['total'] ?? 0);
}

function get_expiring_count(mysqli $conn): int
{
    $stmt = mysqli_prepare($conn, 'SELECT COUNT(DISTINCT medicine_id) AS total FROM batches WHERE quantity > 0 AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)');
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return (int) ($row['total'] ?? 0);
}

function generate_csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function fetch_categories(mysqli $conn): array
{
    $stmt = mysqli_prepare($conn, 'SELECT id, name FROM categories ORDER BY name');
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function fetch_suppliers(mysqli $conn): array
{
    $stmt = mysqli_prepare($conn, 'SELECT id, name FROM suppliers ORDER BY name');
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}
