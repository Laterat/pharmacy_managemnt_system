<?php
$pageTitle = 'Categories';
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['category_name'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($name === '') {
        flash('danger', 'Category name is required.');
    } elseif ($id > 0) {
        $stmt = $pdo->prepare('UPDATE categories SET category_name = ? WHERE id = ?');
        $stmt->execute([$name, $id]);
        flash('success', 'Category updated.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO categories (category_name) VALUES (?)');
        $stmt->execute([$name]);
        flash('success', 'Category added.');
    }
    redirect('categories/index.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$categories = $pdo->query("
    SELECT c.*, COUNT(m.id) AS medicine_count
    FROM categories c
    LEFT JOIN medicines m ON m.category_id = c.id
    GROUP BY c.id
    ORDER BY c.category_name
")->fetchAll();
?>
<section class="split-layout">
    <div class="panel">
        <div class="panel-head"><h2><?= $edit ? 'Edit Category' : 'Add Category' ?></h2></div>
        <form method="POST" class="form validate-form">
            <input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
            <label>Category Name
                <input type="text" name="category_name" required value="<?= $edit ? e($edit['category_name']) : '' ?>">
            </label>
            <button class="btn btn-primary" type="submit"><?= $edit ? 'Update Category' : 'Add Category' ?></button>
        </form>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Category List</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Medicines</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= e($category['category_name']) ?></td>
                        <td><?= (int)$category['medicine_count'] ?></td>
                        <td class="actions">
                            <a class="btn btn-light" href="<?= e(app_url('categories/index.php?edit=' . (int)$category['id'])) ?>">Edit</a>
                            <?php if (current_user_role() === 'admin' && (int)$category['medicine_count'] === 0): ?>
                                <a class="btn btn-danger confirm-delete" href="<?= e(app_url('categories/delete.php?id=' . (int)$category['id'])) ?>">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
