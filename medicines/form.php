<?php
$medicine = $medicine ?? [
    'medicine_name' => '', 'generic_name' => '', 'category_id' => '', 'batch_no' => '',
    'quantity' => '', 'buying_price' => '', 'selling_price' => '', 'supplier_id' => '',
    'manufacture_date' => '', 'expiry_date' => ''
];
?>
<form method="POST" class="form grid-form validate-form">
    <label>Medicine Name <input type="text" name="medicine_name" required value="<?= e($medicine['medicine_name']) ?>"></label>
    <label>Generic Name <input type="text" name="generic_name" value="<?= e($medicine['generic_name']) ?>"></label>
    <label>Category
        <select name="category_id" required>
            <option value="">Select category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id'] ?>" <?= (int)$medicine['category_id'] === (int)$category['id'] ? 'selected' : '' ?>>
                    <?= e($category['category_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Batch Number <input type="text" name="batch_no" required value="<?= e($medicine['batch_no']) ?>"></label>
    <label>Quantity <input type="number" name="quantity" min="0" required value="<?= e((string)$medicine['quantity']) ?>"></label>
    <label>Buying Price <input type="number" step="0.01" min="0" name="buying_price" required value="<?= e((string)$medicine['buying_price']) ?>"></label>
    <label>Selling Price <input type="number" step="0.01" min="0" name="selling_price" required value="<?= e((string)$medicine['selling_price']) ?>"></label>
    <label>Supplier
        <select name="supplier_id">
            <option value="">No supplier</option>
            <?php foreach ($suppliers as $supplier): ?>
                <option value="<?= $supplier['id'] ?>" <?= (int)$medicine['supplier_id'] === (int)$supplier['id'] ? 'selected' : '' ?>>
                    <?= e($supplier['supplier_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Manufacture Date <input type="date" name="manufacture_date" value="<?= e($medicine['manufacture_date']) ?>"></label>
    <label>Expiry Date <input type="date" name="expiry_date" required value="<?= e($medicine['expiry_date']) ?>"></label>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= e($buttonText ?? 'Save Medicine') ?></button>
        <a class="btn btn-light" href="<?= e(app_url('medicines/index.php')) ?>">Cancel</a>
    </div>
</form>
