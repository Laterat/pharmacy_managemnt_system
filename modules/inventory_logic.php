<?php
function add_medicine(mysqli $conn, array $data): bool
{
    mysqli_begin_transaction($conn);

    try {
        $stmt = mysqli_prepare($conn, 'INSERT INTO medicines (name, generic_name, category_id, supplier_id, sku, price, stock_quantity, min_stock_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'ssiisdii', $data['name'], $data['generic_name'], $data['category_id'], $data['supplier_id'], $data['sku'], $data['price'], $data['stock_quantity'], $data['min_stock_level']);
        mysqli_stmt_execute($stmt);
        $medicineId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        $batchNumber = $data['batch_number'] ?: 'BATCH-' . $medicineId . '-' . date('YmdHis');
        $stmt = mysqli_prepare($conn, 'INSERT INTO batches (medicine_id, quantity, expiry_date, batch_number) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iiss', $medicineId, $data['stock_quantity'], $data['expiry_date'], $batchNumber);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        return true;
    } catch (Throwable $exception) {
        mysqli_rollback($conn);
        error_log($exception->getMessage());
        return false;
    }
}

function update_medicine(mysqli $conn, int $id, array $data): bool
{
    mysqli_begin_transaction($conn);

    try {
        $stmt = mysqli_prepare($conn, 'UPDATE medicines SET name = ?, generic_name = ?, category_id = ?, supplier_id = ?, sku = ?, price = ?, stock_quantity = ?, min_stock_level = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'ssiisdiii', $data['name'], $data['generic_name'], $data['category_id'], $data['supplier_id'], $data['sku'], $data['price'], $data['stock_quantity'], $data['min_stock_level'], $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!empty($data['expiry_date'])) {
            $stmt = mysqli_prepare($conn, 'SELECT id FROM batches WHERE medicine_id = ? ORDER BY expiry_date ASC LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $batch = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($batch) {
                $stmt = mysqli_prepare($conn, 'UPDATE batches SET expiry_date = ?, quantity = ? WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'sii', $data['expiry_date'], $data['stock_quantity'], $batch['id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            } else {
                $batchNumber = $data['batch_number'] ?: 'BATCH-' . $id . '-' . date('YmdHis');
                $stmt = mysqli_prepare($conn, 'INSERT INTO batches (medicine_id, quantity, expiry_date, batch_number) VALUES (?, ?, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'iiss', $id, $data['stock_quantity'], $data['expiry_date'], $batchNumber);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }

        mysqli_commit($conn);
        return true;
    } catch (Throwable $exception) {
        mysqli_rollback($conn);
        error_log($exception->getMessage());
        return false;
    }
}

function delete_medicine(mysqli $conn, int $id): bool
{
    $stmt = mysqli_prepare($conn, 'DELETE FROM medicines WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function get_all_medicines(mysqli $conn): array
{
    $stmt = mysqli_prepare($conn, 'SELECT m.*, c.name AS category_name, s.name AS supplier_name, MIN(b.expiry_date) AS nearest_expiry FROM medicines m JOIN categories c ON c.id = m.category_id JOIN suppliers s ON s.id = m.supplier_id LEFT JOIN batches b ON b.medicine_id = m.id AND b.quantity > 0 GROUP BY m.id ORDER BY m.name');
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function get_medicine_by_id(mysqli $conn, int $id): ?array
{
    $stmt = mysqli_prepare($conn, 'SELECT m.*, MIN(b.expiry_date) AS expiry_date FROM medicines m LEFT JOIN batches b ON b.medicine_id = m.id WHERE m.id = ? GROUP BY m.id');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function validate_medicine(array $input): array
{
    $data = [
        'name' => trim($input['name'] ?? ''),
        'generic_name' => trim($input['generic_name'] ?? ''),
        'category_id' => (int) ($input['category_id'] ?? 0),
        'supplier_id' => (int) ($input['supplier_id'] ?? 0),
        'sku' => trim($input['sku'] ?? ''),
        'price' => (float) ($input['price'] ?? -1),
        'stock_quantity' => (int) ($input['stock_quantity'] ?? -1),
        'min_stock_level' => (int) ($input['min_stock_level'] ?? -1),
        'expiry_date' => trim($input['expiry_date'] ?? ''),
        'batch_number' => trim($input['batch_number'] ?? ''),
    ];

    $errors = [];
    foreach (['name', 'generic_name', 'sku', 'expiry_date'] as $field) {
        if ($data[$field] === '') {
            $errors[] = str_replace('_', ' ', ucfirst($field)) . ' is required.';
        }
    }
    if ($data['category_id'] <= 0) {
        $errors[] = 'Category is required.';
    }
    if ($data['supplier_id'] <= 0) {
        $errors[] = 'Supplier is required.';
    }
    if ($data['price'] < 0 || $data['stock_quantity'] < 0 || $data['min_stock_level'] < 0) {
        $errors[] = 'Price and stock values cannot be negative.';
    }

    return [$data, $errors];
}
