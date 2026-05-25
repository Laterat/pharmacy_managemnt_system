<?php
require_once __DIR__ . '/../includes/db.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM medicines WHERE category_id = ?');
$countStmt->execute([$id]);

if ((int)$countStmt->fetchColumn() > 0) {
    flash('danger', 'Cannot delete a category that has medicines.');
} else {
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    flash('success', 'Category deleted.');
}
redirect('categories/index.php');
