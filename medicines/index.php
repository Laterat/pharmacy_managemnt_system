<?php
$pageTitle = 'Medicine Inventory';
require_once __DIR__ . '/../includes/header.php';

$categoryId = (int)($_GET['category_id'] ?? 0);
$search = trim($_GET['search'] ?? '');
$categories = $pdo->query('SELECT * FROM categories ORDER BY category_name')->fetchAll();

$sql = "
    SELECT m.*, c.category_name, s.supplier_name
    FROM medicines m
    JOIN categories c ON c.id = m.category_id
    LEFT JOIN suppliers s ON s.id = m.supplier_id
    WHERE 1 = 1
";
$params = [];
if ($categoryId > 0) {
    $sql .= ' AND m.category_id = ?';
    $params[] = $categoryId;
}
if ($search !== '') {
    $sql .= ' AND (m.medicine_name LIKE ? OR m.generic_name LIKE ? OR m.batch_no LIKE ?)';
    $term = "%{$search}%";
    array_push($params, $term, $term, $term);
}
$sql .= ' ORDER BY m.medicine_name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicines = $stmt->fetchAll();
?>
<section class="panel">
    <div class="panel-head">
        <h2>Stock List</h2>
        <a class="btn btn-primary" href="<?= e(app_url('medicines/create.php')) ?>">Add Medicine</a>
    </div>
    <form class="filters" method="GET">
        <input type="search" name="search" value="<?= e($search) ?>" placeholder="Search medicine, generic name, batch">
        <select name="category_id" class="auto-submit">
            <option value="0">All categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id'] ?>" <?= $categoryId === (int)$category['id'] ? 'selected' : '' ?>>
                    <?= e($category['category_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-light" type="submit">Filter</button>
    </form>
    <div class="table-wrap">
        <table class="searchable-table">
            <thead>
                <tr>
                    <th>Medicine</th><th>Category</th><th>Batch</th><th>Qty</th><th>Selling</th><th>Expiry</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($medicines as $medicine): ?>
                <?php
                $expiryClass = 'safe';
                $expiryText = 'Safe';
                if (strtotime($medicine['expiry_date']) < strtotime(date('Y-m-d'))) {
                    $expiryClass = 'danger';
                    $expiryText = 'Expired';
                } elseif (strtotime($medicine['expiry_date']) <= strtotime('+30 days')) {
                    $expiryClass = 'orange';
                    $expiryText = 'Expiring';
                }
                ?>
                <tr>
                    <td><strong><?= e($medicine['medicine_name']) ?></strong><small><?= e($medicine['generic_name']) ?></small></td>
                    <td><?= e($medicine['category_name']) ?></td>
                    <td><?= e($medicine['batch_no']) ?></td>
                    <td><?= (int)$medicine['quantity'] ?></td>
                    <td><?= number_format((float)$medicine['selling_price'], 2) ?></td>
                    <td><?= e($medicine['expiry_date']) ?></td>
                    <td><span class="badge <?= $expiryClass ?>"><?= $expiryText ?></span></td>
                    <td class="actions">
                        <a class="btn btn-light" href="<?= e(app_url('medicines/edit.php?id=' . (int)$medicine['id'])) ?>">Edit</a>
                        <?php if (current_user_role() === 'admin'): ?>
                            <a class="btn btn-danger confirm-delete" href="<?= e(app_url('medicines/delete.php?id=' . (int)$medicine['id'])) ?>">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$medicines): ?>
                <tr><td colspan="8" class="empty">No medicines found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
