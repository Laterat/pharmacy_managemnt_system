<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/modules/supplier_logic.php';

requireAdmin();

$errors = [];
$message = '';
$editId = (int) ($_GET['edit'] ?? 0);
$data = $editId ? get_supplier_by_id($conn, $editId) : null;
$data = $data ?: ['name' => '', 'contact_person' => '', 'phone' => '', 'email' => '', 'address' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? 'save';
        if ($action === 'delete') {
            $message = delete_supplier($conn, (int) $_POST['id']) ? 'Supplier deleted.' : 'Unable to delete supplier.';
        } else {
            [$data, $errors] = validate_supplier($_POST);
            $id = (int) ($_POST['id'] ?? 0);
            if (!$errors && save_supplier($conn, $data, $id ?: null)) {
                $message = $id ? 'Supplier updated.' : 'Supplier added.';
                $editId = 0;
                $data = ['name' => '', 'contact_person' => '', 'phone' => '', 'email' => '', 'address' => ''];
            } elseif (!$errors) {
                $errors[] = 'Unable to save supplier.';
            }
        }
    }
}

$suppliers = get_all_suppliers($conn);
require_once __DIR__ . '/includes/header.php';
?>
<section class="page-header"><h1>Suppliers</h1></section>
<?php if ($message !== ''): ?><div class="message success-message"><?php echo h($message); ?></div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="message error"><?php echo h($error); ?></div><?php endforeach; ?>

<form class="panel" method="post" data-validate>
    <input type="hidden" name="csrf_token" value="<?php echo h(generate_csrf_token()); ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?php echo h((string) $editId); ?>">
    <h2><?php echo $editId ? 'Edit Supplier' : 'Add Supplier'; ?></h2>
    <div class="form-grid">
        <div class="form-group"><label>Name</label><input name="name" required value="<?php echo h($data['name']); ?>"></div>
        <div class="form-group"><label>Contact Person</label><input name="contact_person" value="<?php echo h($data['contact_person']); ?>"></div>
        <div class="form-group"><label>Phone</label><input name="phone" value="<?php echo h($data['phone']); ?>"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo h($data['email']); ?>"></div>
        <div class="form-group"><label>Address</label><textarea name="address"><?php echo h($data['address']); ?></textarea></div>
    </div>
    <button class="btn primary" type="submit">Save Supplier</button>
</form>

<div class="table-wrap">
    <table>
        <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Email</th><th>Address</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($suppliers as $supplier): ?>
            <tr>
                <td><?php echo h($supplier['name']); ?></td><td><?php echo h($supplier['contact_person']); ?></td><td><?php echo h($supplier['phone']); ?></td><td><?php echo h($supplier['email']); ?></td><td><?php echo h($supplier['address']); ?></td>
                <td class="actions"><a class="btn secondary" href="suppliers.php?edit=<?php echo h($supplier['id']); ?>">Edit</a><form method="post"><input type="hidden" name="csrf_token" value="<?php echo h(generate_csrf_token()); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo h($supplier['id']); ?>"><button class="danger-btn" data-confirm="Delete this supplier?" type="submit">Delete</button></form></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
