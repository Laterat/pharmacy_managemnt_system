<?php
function get_all_users(mysqli $conn): array
{
    $stmt = mysqli_prepare($conn, 'SELECT id, username, role, is_active, created_at FROM users ORDER BY created_at DESC');
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function create_user(mysqli $conn, string $username, string $password, string $role): bool
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, 'INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'sss', $username, $hash, $role);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function set_user_status(mysqli $conn, int $id, int $status): bool
{
    $stmt = mysqli_prepare($conn, 'UPDATE users SET is_active = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $status, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function delete_user(mysqli $conn, int $id): bool
{
    $stmt = mysqli_prepare($conn, 'DELETE FROM users WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}
