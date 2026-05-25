<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$salesStmt = $pdo->prepare("
    SELECT s.*, m.medicine_name, u.fullname
    FROM sales s
    JOIN medicines m ON m.id = s.medicine_id
    JOIN users u ON u.id = s.sold_by
    WHERE DATE(s.sold_at) BETWEEN ? AND ?
    ORDER BY s.sold_at DESC
");
$salesStmt->execute([$from, $to]);
$sales = $salesStmt->fetchAll();

$salesTotal = array_sum(array_map(fn($sale) => (float)$sale['total_price'], $sales));
$lowStockItems = $pdo->query('SELECT medicine_name, quantity FROM medicines WHERE quantity < 10 ORDER BY quantity ASC')->fetchAll();
$expiredItems = $pdo->query('SELECT medicine_name, batch_no, expiry_date FROM medicines WHERE expiry_date < CURDATE() ORDER BY expiry_date')->fetchAll();
$expiringItems = $pdo->query("SELECT medicine_name, batch_no, expiry_date FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY expiry_date")->fetchAll();
?>
<section class="panel report-controls">
    <form method="GET" class="filters">
        <label>From <input type="date" name="from" value="<?= e($from) ?>"></label>
        <label>To <input type="date" name="to" value="<?= e($to) ?>"></label>
        <button class="btn btn-primary" type="submit">Apply</button>
        <button class="btn btn-light print-btn" type="button">Print</button>
    </form>
</section>

<section class="stats-grid">
    <article class="stat-card"><span>Sales in Range</span><strong><?= count($sales) ?></strong></article>
    <article class="stat-card"><span>Sales Total</span><strong><?= number_format($salesTotal, 2) ?></strong></article>
    <article class="stat-card warning"><span>Low Stock</span><strong><?= count($lowStockItems) ?></strong></article>
    <article class="stat-card danger"><span>Expired</span><strong><?= count($expiredItems) ?></strong></article>
</section>

<section class="panel">
    <div class="panel-head"><h2>Sales Report</h2></div>
    <div class="table-wrap">
        <table class="searchable-table">
            <thead><tr><th>Date</th><th>Medicine</th><th>Qty</th><th>Unit</th><th>Total</th><th>Staff</th></tr></thead>
            <tbody>
            <?php foreach ($sales as $sale): ?>
                <tr>
                    <td><?= e($sale['sold_at']) ?></td>
                    <td><?= e($sale['medicine_name']) ?></td>
                    <td><?= (int)$sale['quantity_sold'] ?></td>
                    <td><?= number_format((float)$sale['unit_price'], 2) ?></td>
                    <td><?= number_format((float)$sale['total_price'], 2) ?></td>
                    <td><?= e($sale['fullname']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$sales): ?><tr><td colspan="6" class="empty">No sales in this range.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="report-grid">
    <div class="panel">
        <div class="panel-head"><h2>Low Stock Report</h2></div>
        <ul class="report-list">
            <?php foreach ($lowStockItems as $item): ?><li><?= e($item['medicine_name']) ?><span><?= (int)$item['quantity'] ?> left</span></li><?php endforeach; ?>
            <?php if (!$lowStockItems): ?><li class="empty">No low stock items.</li><?php endif; ?>
        </ul>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Expiry Report</h2></div>
        <ul class="report-list">
            <?php foreach (array_merge($expiredItems, $expiringItems) as $item): ?>
                <li><?= e($item['medicine_name']) ?><span><?= e($item['expiry_date']) ?></span></li>
            <?php endforeach; ?>
            <?php if (!$expiredItems && !$expiringItems): ?><li class="empty">No expiry warnings.</li><?php endif; ?>
        </ul>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
