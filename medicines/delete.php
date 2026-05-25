<?php
require_once __DIR__ . '/../includes/db.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('DELETE FROM medicines WHERE id = ?');
$stmt->execute([$id]);
flash('success', 'Medicine deleted.');
redirect('medicines/index.php');
