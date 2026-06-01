<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requirePharmacist();

$search = trim($_GET['search'] ?? '');
$like = '%' . $search . '%';
$stmt = mysqli_prepare($conn, 'SELECT m.id, m.name, m.generic_name, m.price, m.stock_quantity, MIN(b.expiry_date) AS nearest_expiry FROM medicines m JOIN batches b ON b.medicine_id = m.id AND b.quantity > 0 AND b.expiry_date >= CURDATE() WHERE m.stock_quantity > 0 AND (? = "" OR m.name LIKE ? OR m.generic_name LIKE ?) GROUP BY m.id ORDER BY m.name LIMIT 30');
mysqli_stmt_bind_param($stmt, 'sss', $search, $like, $like);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$medicines = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-header"><h1>Point of Sale</h1></section>
<?php if (($_GET['success'] ?? '') === '1'): ?><div class="message success-message">Sale completed successfully.</div><?php endif; ?>
<?php if (isset($_GET['error'])): ?><div class="message error">Sale could not be completed.</div><?php endif; ?>

<form class="panel form-grid" method="get">
    <div class="form-group"><label>Find Medicine</label><input name="search" value="<?php echo h($search); ?>"></div>
    <div class="form-group"><label>&nbsp;</label><button class="btn secondary" type="submit">Search</button></div>
</form>

<div class="table-wrap panel">
    <table>
        <thead><tr><th>Medicine</th><th>Price</th><th>Stock</th><th>Expiry</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($medicines as $medicine): ?>
            <tr>
                <td><?php echo h($medicine['name']); ?><br><small><?php echo h($medicine['generic_name']); ?></small></td>
                <td><?php echo h(format_price($medicine['price'])); ?></td>
                <td><?php echo h($medicine['stock_quantity']); ?></td>
                <td><?php echo h($medicine['nearest_expiry']); ?></td>
                <td><button type="button" class="btn success" data-add-cart data-id="<?php echo h($medicine['id']); ?>" data-name="<?php echo h($medicine['name']); ?>" data-price="<?php echo h($medicine['price']); ?>" data-stock="<?php echo h($medicine['stock_quantity']); ?>">Add</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<form class="panel" method="post" action="modules/sales_process.php" data-checkout-form>
    <input type="hidden" name="csrf_token" value="<?php echo h(generate_csrf_token()); ?>">
    <input type="hidden" name="cart" data-cart-input>
    <h2>Cart</h2>
    <div class="table-wrap">
        <table class="cart-table">
            <thead><tr><th>Medicine</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th><th></th></tr></thead>
            <tbody data-cart-body></tbody>
        </table>
    </div>
    <div class="total-line">Total: <span data-cart-total>0.00</span></div>
    <button class="btn primary" type="submit">Checkout</button>
</form>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
