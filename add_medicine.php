<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/modules/inventory_logic.php';

requireAdmin();

$categories = fetch_categories($conn);
$suppliers = fetch_suppliers($conn);
$errors = [];
$data = [
    'name' => '', 'generic_name' => '', 'category_id' => 0, 'supplier_id' => 0,
    'sku' => '', 'price' => '', 'stock_quantity' => '', 'min_stock_level' => '',
    'expiry_date' => '', 'batch_number' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        [$data, $errors] = validate_medicine($_POST);
        if (!$errors && add_medicine($conn, $data)) {
            header('Location: inventory.php?created=1');
            exit;
        }
        if (!$errors) {
            $errors[] = 'Unable to add medicine. Check that the SKU is unique.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-header"><h1>Add Medicine</h1></section>
<?php foreach ($errors as $error): ?><div class="message error"><?php echo h($error); ?></div><?php endforeach; ?>
<form class="panel" method="post" data-validate>
    <input type="hidden" name="csrf_token" value="<?php echo h(generate_csrf_token()); ?>">
    <div class="form-grid">
        <div class="form-group"><label>Name</label><input name="name" required value="<?php echo h($data['name']); ?>"></div>
        <div class="form-group"><label>Generic Name</label><input name="generic_name" required value="<?php echo h($data['generic_name']); ?>"></div>
        <div class="form-group"><label>SKU</label><input name="sku" required value="<?php echo h($data['sku']); ?>"></div>
        <div class="form-group"><label>Category</label><select name="category_id" required><option value="">Select</option><?php foreach ($categories as $category): ?><option value="<?php echo h($category['id']); ?>" <?php echo (int) $data['category_id'] === (int) $category['id'] ? 'selected' : ''; ?>><?php echo h($category['name']); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Supplier</label><select name="supplier_id" required><option value="">Select</option><?php foreach ($suppliers as $supplier): ?><option value="<?php echo h($supplier['id']); ?>" <?php echo (int) $data['supplier_id'] === (int) $supplier['id'] ? 'selected' : ''; ?>><?php echo h($supplier['name']); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Price</label><input type="number" step="0.01" min="0" name="price" required value="<?php echo h((string) $data['price']); ?>"></div>
        <div class="form-group"><label>Stock Quantity</label><input type="number" min="0" name="stock_quantity" required value="<?php echo h((string) $data['stock_quantity']); ?>"></div>
        <div class="form-group"><label>Min Stock Level</label><input type="number" min="0" name="min_stock_level" required value="<?php echo h((string) $data['min_stock_level']); ?>"></div>
        <div class="form-group"><label>Expiry Date</label><input type="date" name="expiry_date" required value="<?php echo h($data['expiry_date']); ?>"></div>
        <div class="form-group"><label>Batch Number</label><input name="batch_number" value="<?php echo h($data['batch_number']); ?>"></div>
    </div>
    <div class="actions"><button class="btn primary" type="submit">Save Medicine</button><a class="btn secondary" href="inventory.php">Cancel</a></div>
</form>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
