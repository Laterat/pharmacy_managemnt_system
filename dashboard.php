<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$totalMedicines = (int)$pdo->query('SELECT COUNT(*) FROM medicines')->fetchColumn();
$totalCategories = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$totalSales = (int)$pdo->query('SELECT COUNT(*) FROM sales')->fetchColumn();
$lowStock = (int)$pdo->query('SELECT COUNT(*) FROM medicines WHERE quantity < 10')->fetchColumn();
$expired = (int)$pdo->query('SELECT COUNT(*) FROM medicines WHERE expiry_date < CURDATE()')->fetchColumn();
$expiringSoon = (int)$pdo->query('SELECT COUNT(*) FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)')->fetchColumn();

$alerts = $pdo->query("
    SELECT m.*, c.category_name
    FROM medicines m
    JOIN categories c ON c.id = m.category_id
    WHERE m.quantity < 10 OR m.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY m.expiry_date ASC, m.quantity ASC
    LIMIT 8
")->fetchAll();
?>
<section class="stats-grid">
    <article class="stat-card"><span>Total Medicines</span><strong><?= $totalMedicines ?></strong></article>
    <article class="stat-card"><span>Categories</span><strong><?= $totalCategories ?></strong></article>
    <article class="stat-card"><span>Total Sales</span><strong><?= $totalSales ?></strong></article>
    <article class="stat-card warning"><span>Low Stock</span><strong><?= $lowStock ?></strong></article>
    <article class="stat-card danger"><span>Expired</span><strong><?= $expired ?></strong></article>
    <article class="stat-card orange"><span>Expiring Soon</span><strong><?= $expiringSoon ?></strong></article>
</section>

<section class="panel">
    <div class="panel-head">
        <h2>Important Alerts</h2>
        <a class="btn btn-light" href="<?= e(app_url('reports/index.php')) ?>">View Reports</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Expiry</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alerts as $item): ?>
                    <?php
                    $status = 'Safe';
                    $class = 'safe';
                    if ((int)$item['quantity'] < 10) {
                        $status = 'Low stock';
                        $class = 'danger';
                    }
                    if (strtotime($item['expiry_date']) < strtotime(date('Y-m-d'))) {
                        $status = 'Expired';
                        $class = 'danger';
                    } elseif (strtotime($item['expiry_date']) <= strtotime('+30 days')) {
                        $status = $status === 'Low stock' ? 'Low + expiring' : 'Expiring soon';
                        $class = 'orange';
                    }
                    ?>
                    <tr>
                        <td><?= e($item['medicine_name']) ?></td>
                        <td><?= e($item['category_name']) ?></td>
                        <td><?= (int)$item['quantity'] ?></td>
                        <td><?= e($item['expiry_date']) ?></td>
                        <td><span class="badge <?= $class ?>"><?= e($status) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$alerts): ?>
                    <tr><td colspan="5" class="empty">No urgent alerts today.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
