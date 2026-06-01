<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/modules/inventory_logic.php';

requireLogin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } elseif ($action === 'delete') {
        if (!isAdmin()) {
            $error = 'Only admins can delete medicines.';
        } else {
            $message = delete_medicine($conn, (int) $_POST['id']) ? 'Medicine deleted.' : 'Unable to delete medicine.';
        }
    } elseif ($action === 'add_category') {
        if (!isAdmin()) {
            $error = 'Only admins can add categories.';
        } else {
            $categoryName = trim($_POST['category_name'] ?? '');
            if ($categoryName === '') {
                $error = 'Category name is required.';
            } else {
                $stmt = mysqli_prepare($conn, 'INSERT INTO categories (name) VALUES (?)');
                mysqli_stmt_bind_param($stmt, 's', $categoryName);
                $message = mysqli_stmt_execute($stmt) ? 'Category added.' : 'Unable to add category. It may already exist.';
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($action === 'delete_category') {
        if (!isAdmin()) {
            $error = 'Only admins can delete categories.';
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = mysqli_prepare($conn, 'DELETE FROM categories WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            $message = mysqli_stmt_execute($stmt) ? 'Category deleted.' : 'Unable to delete category. It may be used by medicines.';
            mysqli_stmt_close($stmt);
        }
    }
}

$search = trim($_GET['search'] ?? '');
$categoryId = (int) ($_GET['category_id'] ?? 0);
$categories = fetch_categories($conn);

if ($search !== '' || $categoryId > 0) {
    $like = '%' . $search . '%';
    $stmt = mysqli_prepare($conn, 'SELECT m.*, c.name AS category_name, MIN(b.expiry_date) AS nearest_expiry FROM medicines m JOIN categories c ON c.id = m.category_id LEFT JOIN batches b ON b.medicine_id = m.id AND b.quantity > 0 WHERE (? = "" OR m.name LIKE ? OR m.generic_name LIKE ?) AND (? = 0 OR m.category_id = ?) GROUP BY m.id ORDER BY m.name');
    mysqli_stmt_bind_param($stmt, 'sssii', $search, $like, $like, $categoryId, $categoryId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $medicines = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    $medicines = get_all_medicines($conn);
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-header">
    <div>
        <h1>Inventory</h1>
        <p><?php echo isAdmin() ? 'Search, add, update, and remove medicines.' : 'Search and view available medicines.'; ?></p>
    </div>
    <?php if (isAdmin()): ?>
        <a class="btn primary" href="add_medicine.php">Add Medicine</a>
    <?php endif; ?>
</section>

<?php if ($message !== ''): ?><div class="message success-message"><?php echo h($message); ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="message error"><?php echo h($error); ?></div><?php endif; ?>

<?php if (isAdmin()): ?>
    <details class="category-manager">
        <summary class="btn primary">Add Category</summary>
        <section class="panel">
            <form class="inline-form" method="post" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo h(generate_csrf_token()); ?>">
                <input type="hidden" name="action" value="add_category">
                <div class="form-group">
                    <label for="category_name">New Category</label>
                    <input type="text" id="category_name" name="category_name" required>
                </div>
                <button class="btn primary" type="submit">Save Category</button>
            </form>
            <div class="category-list">
                <?php foreach ($categories as $category): ?>
                    <form method="post" class="category-chip">
                        <input type="hidden" name="csrf_token" value="<?php echo h(generate_csrf_token()); ?>">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="id" value="<?php echo h($category['id']); ?>">
                        <span><?php echo h($category['name']); ?></span>
                        <button class="link-danger" type="submit" data-confirm="Delete this category?">Remove</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>
    </details>
<?php endif; ?>

<form class="panel form-grid" method="get">
    <div class="form-group">
        <label for="search">Search</label>
        <input type="text" id="search" name="search" value="<?php echo h($search); ?>">
    </div>
    <div class="form-group">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
            <option value="0">All categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo h($category['id']); ?>" <?php echo $categoryId === (int) $category['id'] ? 'selected' : ''; ?>><?php echo h($category['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>&nbsp;</label>
        <button class="btn secondary" type="submit">Filter</button>
    </div>
</form>

<div class="table-wrap">
    <table>
        <thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Expiry</th><?php if (isAdmin()): ?><th>Actions</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($medicines as $medicine): ?>
            <tr class="<?php echo (int) $medicine['stock_quantity'] <= (int) $medicine['min_stock_level'] ? 'low-stock' : ''; ?>">
                <td><?php echo h($medicine['name']); ?><br><small><?php echo h($medicine['generic_name']); ?></small></td>
                <td><?php echo h($medicine['category_name']); ?></td>
                <td><?php echo h(format_price($medicine['price'])); ?></td>
                <td><?php echo h($medicine['stock_quantity']); ?></td>
                <td><?php echo h($medicine['nearest_expiry'] ?? 'N/A'); ?></td>
                <?php if (isAdmin()): ?>
                    <td class="actions">
                        <a class="btn secondary" href="edit_medicine.php?id=<?php echo h($medicine['id']); ?>">Edit</a>
                        <form method="post" data-validate>
                            <input type="hidden" name="csrf_token" value="<?php echo h(generate_csrf_token()); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo h($medicine['id']); ?>">
                            <button class="danger-btn" type="submit" data-confirm="Delete this medicine?">Delete</button>
                        </form>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
