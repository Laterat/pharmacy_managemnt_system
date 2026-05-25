<?php
require_once __DIR__ . '/db.php';
require_login();

$pageTitle = $pageTitle ?? 'Pharmacy Inventory';
$flash = get_flash();
$flashType = $flash ? preg_replace('/[^a-z0-9_-]/i', '', (string)($flash['type'] ?? 'info')) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Pharmacy Inventory</title>
    <link rel="stylesheet" href="<?= e(app_url('css/style.css')) ?>">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <button class="menu-toggle" type="button" aria-label="Open menu">&#9776;</button>
            <div>
                <h1><?= e($pageTitle) ?></h1>
                <p>Welcome, <?= e(current_user_name()) ?> &middot; <?= e(ucfirst(current_user_role())) ?></p>
            </div>
            <a class="btn btn-danger" href="<?= e(app_url('auth/logout.php')) ?>">Logout</a>
        </header>
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flashType) ?>"><?= e($flash['message'] ?? '') ?></div>
        <?php endif; ?>
