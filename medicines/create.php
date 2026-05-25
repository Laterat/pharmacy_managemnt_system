<?php
$pageTitle = 'Stock Entry';
require_once __DIR__ . '/../includes/header.php';

$categories = $pdo->query('SELECT * FROM categories ORDER BY category_name')->fetchAll();
$suppliers = $pdo->query('SELECT * FROM suppliers ORDER BY supplier_name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        INSERT INTO medicines
        (medicine_name, generic_name, category_id, batch_no, quantity, buying_price, selling_price, supplier_id, manufacture_date, expiry_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        trim($_POST['medicine_name']),
        trim($_POST['generic_name']),
        (int)$_POST['category_id'],
        trim($_POST['batch_no']),
        (int)$_POST['quantity'],
        (float)$_POST['buying_price'],
        (float)$_POST['selling_price'],
        $_POST['supplier_id'] !== '' ? (int)$_POST['supplier_id'] : null,
        $_POST['manufacture_date'] ?: null,
        $_POST['expiry_date'],
    ]);
    flash('success', 'Medicine added successfully.');
    redirect('medicines/index.php');
}

$buttonText = 'Add Medicine';
?>
<section class="panel">
    <div class="panel-head"><h2>Add Medicine</h2></div>
    <?php include __DIR__ . '/form.php'; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
