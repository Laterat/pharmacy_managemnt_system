<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

function create_sale(mysqli $conn, int $user_id, array $items)
{
    if (empty($items)) {
        return false;
    }

    mysqli_begin_transaction($conn);

    try {
        $total = 0.00;
        foreach ($items as $item) {
            $total += (int) $item['quantity'] * (float) $item['unit_price'];
        }

        $stmt = mysqli_prepare($conn, 'INSERT INTO sales (user_id, total_amount) VALUES (?, ?)');
        mysqli_stmt_bind_param($stmt, 'id', $user_id, $total);
        mysqli_stmt_execute($stmt);
        $saleId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        foreach ($items as $item) {
            $medicineId = (int) $item['medicine_id'];
            $quantityNeeded = (int) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];

            $stmt = mysqli_prepare(
                $conn,
                'SELECT stock_quantity FROM medicines WHERE id = ? AND stock_quantity >= ?'
            );

            mysqli_stmt_bind_param($stmt, 'ii', $medicineId, $quantityNeeded);
            mysqli_stmt_execute($stmt);
            $stockResult = mysqli_stmt_get_result($stmt);
            $stockRow = mysqli_fetch_assoc($stockResult);
            mysqli_stmt_close($stmt);

            if (!$stockRow || $stockRow['stock_quantity'] < $quantityNeeded) {
                throw new RuntimeException('Insufficient stock.');
            }
            $stmt = mysqli_prepare(
                $conn,
                'SELECT id, quantity 
     FROM batches 
     WHERE medicine_id = ?
     AND quantity > 0
     AND expiry_date >= CURDATE()
     ORDER BY expiry_date ASC, id ASC'
            );

            mysqli_stmt_bind_param($stmt, 'i', $medicineId);
            mysqli_stmt_execute($stmt);
            $batchResult = mysqli_stmt_get_result($stmt);

            while ($quantityNeeded > 0 && ($batch = mysqli_fetch_assoc($batchResult))) {
                $deduct = min($quantityNeeded, (int) $batch['quantity']);
                $newBatchQty = (int) $batch['quantity'] - $deduct;
                $batchId = (int) $batch['id'];

                $updateBatch = mysqli_prepare($conn, 'UPDATE batches SET quantity = ? WHERE id = ?');
                mysqli_stmt_bind_param($updateBatch, 'ii', $newBatchQty, $batchId);
                mysqli_stmt_execute($updateBatch);
                mysqli_stmt_close($updateBatch);

                $quantityNeeded -= $deduct;
            }
            mysqli_stmt_close($stmt);

            if ($quantityNeeded > 0) {
                throw new RuntimeException('No valid unexpired batch has enough stock.');
            }

            $soldQuantity = (int) $item['quantity'];
            $stmt = mysqli_prepare($conn, 'INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'iiid', $saleId, $medicineId, $soldQuantity, $unitPrice);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($conn, 'UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'ii', $soldQuantity, $medicineId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        mysqli_commit($conn);
        return $saleId;
    } catch (Throwable $exception) {
        mysqli_rollback($conn);
        error_log($exception->getMessage());
        return false;
    }
}

if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'sales_process.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Location: ../pos.php?error=csrf');
        exit;
    }

    $cart = json_decode($_POST['cart'] ?? '[]', true);
    if (!is_array($cart)) {
        header('Location: ../pos.php?error=cart');
        exit;
    }

    $items = [];
    foreach ($cart as $row) {
        $medicineId = (int) ($row['medicine_id'] ?? 0);
        $quantity = (int) ($row['quantity'] ?? 0);
        $unitPrice = (float) ($row['unit_price'] ?? 0);
        if ($medicineId > 0 && $quantity > 0 && $unitPrice >= 0) {
            $items[] = [
                'medicine_id' => $medicineId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ];
        }
    }

    $saleId = create_sale($conn, (int) $_SESSION['user_id'], $items);
    header('Location: ../pos.php?' . ($saleId ? 'success=1' : 'error=sale'));
    exit;
}
