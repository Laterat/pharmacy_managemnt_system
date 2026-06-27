<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';
$lowStockBadge = isset($conn) ? get_low_stock_count($conn) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> ASD | Pharmacy System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/favicon.ico">
</head>

<body>
    <nav class="navbar">
        <a class="brand" href="dashboard.php">Pharmacy System</a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="inventory.php">Inventory <span class="badge"><?php echo h((string) $lowStockBadge); ?></span></a>
            <?php if (isPharmacist()): ?>
                <a href="pos.php">POS</a>
            <?php endif; ?>
            <?php if (isAdmin()): ?>
                <a href="suppliers.php">Suppliers</a>
                <a href="reports.php">Reports</a>
                <a href="users.php">Users</a>
            <?php endif; ?>
        </div>
        <div class="nav-user">
            <a href="profile.php">
                <?php echo h($_SESSION['full_name'] ?? $_SESSION['username'] ?? ''); ?>
            </a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>
    <main class="container">