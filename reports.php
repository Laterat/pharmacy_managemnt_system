<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAdmin();

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$csv = $_GET['export'] ?? '';

$stmt = mysqli_prepare($conn, 'SELECT DATE(sale_date) AS period, SUM(total_amount) AS total FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? GROUP BY DATE(sale_date) ORDER BY period DESC');
mysqli_stmt_bind_param($stmt, 'ss', $from, $to);
mysqli_stmt_execute($stmt);
$daily = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, 'SELECT YEARWEEK(sale_date, 1) AS period, SUM(total_amount) AS total FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? GROUP BY YEARWEEK(sale_date, 1) ORDER BY period DESC');
mysqli_stmt_bind_param($stmt, 'ss', $from, $to);
mysqli_stmt_execute($stmt);
$weekly = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, 'SELECT DATE_FORMAT(sale_date, "%Y-%m") AS period, SUM(total_amount) AS total FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? GROUP BY DATE_FORMAT(sale_date, "%Y-%m") ORDER BY period DESC');
mysqli_stmt_bind_param($stmt, 'ss', $from, $to);
mysqli_stmt_execute($stmt);
$monthly = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, 'SELECT m.name, SUM(si.quantity) AS sold, SUM(si.quantity * si.unit_price) AS revenue, SUM(si.quantity * si.unit_price) AS profit FROM sale_items si JOIN sales s ON s.id = si.sale_id JOIN medicines m ON m.id = si.medicine_id WHERE DATE(s.sale_date) BETWEEN ? AND ? GROUP BY m.id ORDER BY sold DESC LIMIT 10');
mysqli_stmt_bind_param($stmt, 'ss', $from, $to);
mysqli_stmt_execute($stmt);
$topMedicines = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, 'SELECT m.name, b.batch_number, b.quantity, b.expiry_date FROM batches b JOIN medicines m ON m.id = b.medicine_id WHERE b.quantity > 0 AND b.expiry_date < CURDATE() ORDER BY b.expiry_date ASC');
mysqli_stmt_execute($stmt);
$expired = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

if ($csv === 'sales') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales-report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Period', 'Total']);
    foreach ($daily as $row) {
        fputcsv($out, [$row['period'], $row['total']]);
    }
    fclose($out);
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-header"><h1>Reports</h1><a class="btn success" href="reports.php?from=<?php echo h($from); ?>&to=<?php echo h($to); ?>&export=sales">Export CSV</a></section>
<form class="panel form-grid" method="get">
    <div class="form-group"><label>From</label><input type="date" name="from" value="<?php echo h($from); ?>"></div>
    <div class="form-group"><label>To</label><input type="date" name="to" value="<?php echo h($to); ?>"></div>
    <div class="form-group"><label>&nbsp;</label><button class="btn secondary" type="submit">Apply</button></div>
</form>

<section class="stats-grid">
    <article class="stat-card"><span>Daily Rows</span><strong><?php echo h((string) count($daily)); ?></strong></article>
    <article class="stat-card"><span>Weekly Rows</span><strong><?php echo h((string) count($weekly)); ?></strong></article>
    <article class="stat-card"><span>Monthly Rows</span><strong><?php echo h((string) count($monthly)); ?></strong></article>
</section>

<div class="panel"><h2>Daily Sales</h2><div class="table-wrap"><table><thead><tr><th>Date</th><th>Total</th></tr></thead><tbody><?php foreach ($daily as $row): ?><tr><td><?php echo h($row['period']); ?></td><td><?php echo h(format_price($row['total'])); ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<div class="panel"><h2>Weekly Sales</h2><div class="table-wrap"><table><thead><tr><th>Week</th><th>Total</th></tr></thead><tbody><?php foreach ($weekly as $row): ?><tr><td><?php echo h($row['period']); ?></td><td><?php echo h(format_price($row['total'])); ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<div class="panel"><h2>Monthly Sales</h2><div class="table-wrap"><table><thead><tr><th>Month</th><th>Total</th></tr></thead><tbody><?php foreach ($monthly as $row): ?><tr><td><?php echo h($row['period']); ?></td><td><?php echo h(format_price($row['total'])); ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<div class="panel"><h2>Top Selling Medicines</h2><div class="table-wrap"><table><thead><tr><th>Medicine</th><th>Sold</th><th>Revenue</th><th>Profit</th></tr></thead><tbody><?php foreach ($topMedicines as $row): ?><tr><td><?php echo h($row['name']); ?></td><td><?php echo h($row['sold']); ?></td><td><?php echo h(format_price($row['revenue'])); ?></td><td><?php echo h(format_price($row['profit'])); ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<div class="panel"><h2>Expired Stock</h2><div class="table-wrap"><table><thead><tr><th>Medicine</th><th>Batch</th><th>Quantity</th><th>Expiry</th></tr></thead><tbody><?php foreach ($expired as $row): ?><tr><td><?php echo h($row['name']); ?></td><td><?php echo h($row['batch_number']); ?></td><td><?php echo h($row['quantity']); ?></td><td><?php echo h($row['expiry_date']); ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
