<?php
$pageTitle = 'Sales / Dispense';
require_once __DIR__ . '/../includes/header.php';

$selectedId = (int)($_GET['medicine_id'] ?? 0);
$receipt = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicineId = (int)($_POST['medicine_id'] ?? 0);
    $quantity = (int)($_POST['quantity_sold'] ?? 0);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT * FROM medicines WHERE id = ? FOR UPDATE');
        $stmt->execute([$medicineId]);
        $medicine = $stmt->fetch();

        if (!$medicine) {
            throw new RuntimeException('Medicine not found.');
        }
        if ($quantity <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }
        if ((int)$medicine['quantity'] < $quantity) {
            throw new RuntimeException('Cannot dispense more than available stock.');
        }
        if (strtotime($medicine['expiry_date']) < strtotime(date('Y-m-d'))) {
            throw new RuntimeException('Cannot dispense expired medicine.');
        }

        $total = $quantity * (float)$medicine['selling_price'];
        $saleStmt = $pdo->prepare('INSERT INTO sales (medicine_id, quantity_sold, unit_price, total_price, sold_by) VALUES (?, ?, ?, ?, ?)');
        $saleStmt->execute([$medicineId, $quantity, $medicine['selling_price'], $total, $_SESSION['user_id']]);

        $updateStmt = $pdo->prepare('UPDATE medicines SET quantity = quantity - ? WHERE id = ?');
        $updateStmt->execute([$quantity, $medicineId]);

        $receipt = [
            'sale_id' => $pdo->lastInsertId(),
            'medicine_name' => $medicine['medicine_name'],
            'quantity' => $quantity,
            'unit_price' => $medicine['selling_price'],
            'total' => $total,
            'staff' => current_user_name(),
            'date' => date('Y-m-d H:i'),
        ];
        $pdo->commit();
        flash('success', 'Medicine dispensed successfully.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

$medicines = $pdo->query("
    SELECT id, medicine_name, batch_no, quantity, selling_price, expiry_date
    FROM medicines
    WHERE quantity > 0
    ORDER BY medicine_name
")->fetchAll();
?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<section class="split-layout">
    <div class="panel">
        <div class="panel-head"><h2>Dispense Medicine</h2></div>
        <form method="POST" class="form validate-form sale-form">
            <label>Search Medicine
                <input type="search" class="client-search" data-target="#medicineSelect" placeholder="Type to filter medicines">
            </label>
            <label>Medicine
                <select name="medicine_id" id="medicineSelect" required>
                    <option value="">Select medicine</option>
                    <?php foreach ($medicines as $medicine): ?>
                        <option
                            value="<?= (int)$medicine['id'] ?>"
                            data-price="<?= e((string)$medicine['selling_price']) ?>"
                            data-stock="<?= (int)$medicine['quantity'] ?>"
                            <?= $selectedId === (int)$medicine['id'] ? 'selected' : '' ?>
                        >
                            <?= e($medicine['medicine_name']) ?> &middot; Batch <?= e($medicine['batch_no']) ?> &middot; Stock <?= (int)$medicine['quantity'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Quantity
                <input type="number" name="quantity_sold" min="1" required class="sale-qty">
            </label>
            <div class="summary-box">
                <span>Available: <strong class="available-stock">0</strong></span>
                <span>Unit Price: <strong class="unit-price">0.00</strong></span>
                <span>Total: <strong class="sale-total">0.00</strong></span>
            </div>
            <button class="btn btn-primary" type="submit">Dispense</button>
        </form>
    </div>

    <div class="panel receipt-panel">
        <div class="panel-head">
            <h2>Receipt</h2>
            <button class="btn btn-light print-btn" type="button">Print</button>
        </div>
        <?php if ($receipt): ?>
            <div class="receipt">
                <h3>PharmaStock Receipt</h3>
                <p>Receipt #: <?= e((string)$receipt['sale_id']) ?></p>
                <p>Date: <?= e($receipt['date']) ?></p>
                <hr>
                <p>Medicine: <?= e($receipt['medicine_name']) ?></p>
                <p>Quantity: <?= (int)$receipt['quantity'] ?></p>
                <p>Unit Price: <?= number_format((float)$receipt['unit_price'], 2) ?></p>
                <h3>Total: <?= number_format((float)$receipt['total'], 2) ?></h3>
                <p>Served by: <?= e($receipt['staff']) ?></p>
            </div>
        <?php else: ?>
            <p class="empty">A receipt appears here after dispensing.</p>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
