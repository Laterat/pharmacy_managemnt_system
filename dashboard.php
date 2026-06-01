<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$totalMedicines = 0;
$lowStock = get_low_stock_count($conn);
$expiringSoon = get_expiring_count($conn);
$todaySales = 0.00;
$view = $_GET['view'] ?? 'medicines';
$allowedViews = ['medicines', 'low_stock', 'expiring', 'sales'];

if (!in_array($view, $allowedViews, true)) {
    $view = 'medicines';
}

$stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM medicines');
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$totalMedicines = (int) ($row['total'] ?? 0);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, 'SELECT COALESCE(SUM(total_amount), 0) AS total FROM sales WHERE DATE(sale_date) = CURDATE()');
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$todaySales = (float) ($row['total'] ?? 0);
mysqli_stmt_close($stmt);

if ($view === 'low_stock') {
    $detailTitle = 'Low Stock Medicines';
    $stmt = mysqli_prepare($conn, 'SELECT name, generic_name, stock_quantity, min_stock_level FROM medicines WHERE stock_quantity <= min_stock_level ORDER BY stock_quantity ASC, name ASC');
    mysqli_stmt_execute($stmt);
    $detailRows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} elseif ($view === 'expiring') {
    $detailTitle = 'Expiring Within 30 Days';
    $stmt = mysqli_prepare($conn, 'SELECT m.name, m.generic_name, b.batch_number, b.quantity, b.expiry_date FROM batches b JOIN medicines m ON m.id = b.medicine_id WHERE b.quantity > 0 AND b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY b.expiry_date ASC');
    mysqli_stmt_execute($stmt);
    $detailRows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} elseif ($view === 'sales') {
    $detailTitle = "Today's Sales";
    $stmt = mysqli_prepare($conn, 'SELECT s.id, s.total_amount, s.sale_date, u.username FROM sales s JOIN users u ON u.id = s.user_id WHERE DATE(s.sale_date) = CURDATE() ORDER BY s.sale_date DESC');
    mysqli_stmt_execute($stmt);
    $detailRows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    $detailTitle = 'All Medicines';
    $stmt = mysqli_prepare($conn, 'SELECT m.name, m.generic_name, c.name AS category_name, m.stock_quantity, m.price FROM medicines m JOIN categories c ON c.id = m.category_id ORDER BY m.name ASC');
    mysqli_stmt_execute($stmt);
    $detailRows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-header">
    <h1>Dashboard</h1>
    <p>Inventory health and sales activity at a glance.</p>
</section>

<section class="dashboard-layout">
    <aside class="dashboard-menu">
        <a class="stat-card <?php echo $view === 'medicines' ? 'active' : ''; ?>" href="dashboard.php?view=medicines">
            <span>Total Medicines</span>
            <strong><?php echo h((string) $totalMedicines); ?></strong>
        </a>
        <a class="stat-card danger <?php echo $view === 'low_stock' ? 'active' : ''; ?>" href="dashboard.php?view=low_stock">
            <span>Low Stock</span>
            <strong><?php echo h((string) $lowStock); ?></strong>
        </a>
        <a class="stat-card warning <?php echo $view === 'expiring' ? 'active' : ''; ?>" href="dashboard.php?view=expiring">
            <span>Expiring Soon</span>
            <strong><?php echo h((string) $expiringSoon); ?></strong>
        </a>
        <a class="stat-card <?php echo $view === 'sales' ? 'active' : ''; ?>" href="dashboard.php?view=sales">
            <span>Today's Sales</span>
            <strong><?php echo h(format_price($todaySales)); ?></strong>
        </a>
    </aside>

    <section class="dashboard-detail panel">
        <h2><?php echo h($detailTitle); ?></h2>
        <div class="table-wrap">
            <table>
                <?php if ($view === 'low_stock'): ?>
                    <thead><tr><th>Medicine</th><th>Generic</th><th>Stock</th><th>Minimum</th></tr></thead>
                    <tbody><?php foreach ($detailRows as $row): ?><tr><td><?php echo h($row['name']); ?></td><td><?php echo h($row['generic_name']); ?></td><td><?php echo h($row['stock_quantity']); ?></td><td><?php echo h($row['min_stock_level']); ?></td></tr><?php endforeach; ?></tbody>
                <?php elseif ($view === 'expiring'): ?>
                    <thead><tr><th>Medicine</th><th>Generic</th><th>Batch</th><th>Quantity</th><th>Expiry</th></tr></thead>
                    <tbody><?php foreach ($detailRows as $row): ?><tr><td><?php echo h($row['name']); ?></td><td><?php echo h($row['generic_name']); ?></td><td><?php echo h($row['batch_number']); ?></td><td><?php echo h($row['quantity']); ?></td><td><?php echo h($row['expiry_date']); ?></td></tr><?php endforeach; ?></tbody>
                <?php elseif ($view === 'sales'): ?>
                    <thead><tr><th>Sale ID</th><th>Cashier</th><th>Total</th><th>Time</th></tr></thead>
                    <tbody><?php foreach ($detailRows as $row): ?><tr><td>#<?php echo h($row['id']); ?></td><td><?php echo h($row['username']); ?></td><td><?php echo h(format_price($row['total_amount'])); ?></td><td><?php echo h($row['sale_date']); ?></td></tr><?php endforeach; ?></tbody>
                <?php else: ?>
                    <thead><tr><th>Medicine</th><th>Generic</th><th>Category</th><th>Stock</th><th>Price</th></tr></thead>
                    <tbody><?php foreach ($detailRows as $row): ?><tr><td><?php echo h($row['name']); ?></td><td><?php echo h($row['generic_name']); ?></td><td><?php echo h($row['category_name']); ?></td><td><?php echo h($row['stock_quantity']); ?></td><td><?php echo h(format_price($row['price'])); ?></td></tr><?php endforeach; ?></tbody>
                <?php endif; ?>
            </table>
        </div>
        <?php if (!$detailRows): ?>
            <p class="empty-state">No records found.</p>
        <?php endif; ?>
    </section>
</section>

<section class="quick-actions">
    <a class="btn primary" href="inventory.php">View Inventory</a>
    <?php if (isPharmacist()): ?>
        <a class="btn success" href="pos.php">Point of Sale</a>
    <?php endif; ?>
    <?php if (isAdmin()): ?>
        <a class="btn secondary" href="reports.php">Reports</a>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
