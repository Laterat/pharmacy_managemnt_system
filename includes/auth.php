<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']);
}

function isAdmin(): bool
{
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function isPharmacist(): bool
{
    return isLoggedIn() && $_SESSION['role'] === 'pharmacist';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}

function requirePharmacist(): void
{
    requireLogin();

    if (!isPharmacist()) {
        header('Location: dashboard.php');
        exit;
    }
}
