<?php
function get_all_suppliers(mysqli $conn): array
{
    $stmt = mysqli_prepare($conn, 'SELECT id, name, contact_person, phone, email, address FROM suppliers ORDER BY name');
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function get_supplier_by_id(mysqli $conn, int $id): ?array
{
    $stmt = mysqli_prepare($conn, 'SELECT id, name, contact_person, phone, email, address FROM suppliers WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function save_supplier(mysqli $conn, array $data, ?int $id = null): bool
{
    if ($id) {
        $stmt = mysqli_prepare($conn, 'UPDATE suppliers SET name = ?, contact_person = ?, phone = ?, email = ?, address = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'sssssi', $data['name'], $data['contact_person'], $data['phone'], $data['email'], $data['address'], $id);
    } else {
        $stmt = mysqli_prepare($conn, 'INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssss', $data['name'], $data['contact_person'], $data['phone'], $data['email'], $data['address']);
    }

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function delete_supplier(mysqli $conn, int $id): bool
{
    $check = mysqli_prepare(
        $conn,
        'SELECT COUNT(*) FROM medicines WHERE supplier_id = ?'
    );

    mysqli_stmt_bind_param($check, 'i', $id);
    mysqli_stmt_execute($check);
    mysqli_stmt_bind_result($check, $count);
    mysqli_stmt_fetch($check);
    mysqli_stmt_close($check);

    if ($count > 0) {
        return false;
    }


    $stmt = mysqli_prepare(
        $conn,
        'DELETE FROM suppliers WHERE id = ?'
    );

    mysqli_stmt_bind_param($stmt, 'i', $id);

    $ok = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    return $ok;
}
function validate_supplier(array $input): array
{
    $data = [
        'name' => trim($input['name'] ?? ''),
        'contact_person' => trim($input['contact_person'] ?? ''),
        'phone' => trim($input['phone'] ?? ''),
        'email' => trim($input['email'] ?? ''),
        'address' => trim($input['address'] ?? ''),
    ];

    $errors = [];
    if ($data['name'] === '') {
        $errors[] = 'Supplier name is required.';
    }
    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email address is invalid.';
    }

    return [$data, $errors];
}
