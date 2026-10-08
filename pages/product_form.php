<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin', 'manager']);

$id = $_GET['id'] ?? 0;
$is_edit = $id > 0;
$page_title = $is_edit ? 'Edit Product' : 'Add New Product';

$product = [
    'name' => '', 'sku' => '', 'barcode' => '', 'category_id' => '', 'supplier_id' => '',
    'brand' => '', 'unit_type' => 'pcs', 'purchase_price' => '0.00', 'selling_price' => '0.00',
    'tax_percent' => '0.00', 'discount_percent' => '0.00', 'stock_quantity' => '0.00',
    'reorder_level' => '10.00', 'expiry_date' => '', 'status' => 'active'
];

if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $product = $row;
    } else {
        set_flash_message('error', 'Product not found.');
        header("Location: products.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    // Read and sanitize inputs
    $input = [
        trim($_POST['name'] ?? ''),
        trim($_POST['sku'] ?? ''),
        trim($_POST['barcode'] ?? ''),
        $_POST['category_id'] ?: null,
        $_POST['supplier_id'] ?: null,
        trim($_POST['brand'] ?? ''),
        $_POST['unit_type'] ?? 'pcs',
        (float)($_POST['purchase_price'] ?? 0),
        (float)($_POST['selling_price'] ?? 0),
        (float)($_POST['tax_percent'] ?? 0),
        (float)($_POST['discount_percent'] ?? 0),
        (float)($_POST['stock_quantity'] ?? 0),
        (float)($_POST['reorder_level'] ?? 10),
        !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
        $_POST['status'] ?? 'active'
    ];
    
    if (empty($input[0])) { // Name is required
        set_flash_message('error', 'Product name is required.');
    } else {
        try {
            if ($is_edit) {
                $input[] = $id; // Add ID for update
                $sql = "UPDATE products SET name=?, sku=?, barcode=?, category_id=?, supplier_id=?, brand=?, unit_type=?, 
                        purchase_price=?, selling_price=?, tax_percent=?, discount_percent=?, stock_quantity=?, reorder_level=?, 
                        expiry_date=?, status=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($input);
                set_flash_message('success', 'Product updated successfully.');
                log_activity($pdo, $_SESSION['user_id'], 'Edit Product', "Edited product ID: $id");
            } else {
                $sql = "INSERT INTO products (name, sku, barcode, category_id, supplier_id, brand, unit_type, purchase_price, 
                        selling_price, tax_percent, discount_percent, stock_quantity, reorder_level, expiry_date, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($input);
                set_flash_message('success', 'Product added successfully.');
                log_activity($pdo, $_SESSION['user_id'], 'Add Product', "Added product: {$input[0]}");
            }
            header("Location: products.php");
            exit;
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) { // Integrity constraint violation (Duplicate SKU/Barcode)
                set_flash_message('error', 'SKU or Barcode already exists. Please use unique values.');
            } else {
                set_flash_message('error', 'Database error occurred while saving.');
            }
            // Repopulate form with failed data
            $product = array_merge($product, $_POST);
        }
    }
}

// Fetch categories and suppliers for dropdowns
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$sups = $pdo->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll();

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title"><?= esc($page_title) ?></h3>
        <a href="products.php" class="btn btn-outline-danger btn-sm">Back to List</a>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?= csrf_field() ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <!-- Basic Info -->
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= esc($product['name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">SKU</label>
                    <input type="text" name="sku" class="form-control" value="<?= esc($product['sku']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Barcode</label>
                    <input type="text" name="barcode" class="form-control" value="<?= esc($product['barcode']) ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-control">
                        <option value="">-- Select Category --</option>
                        <?php foreach($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $product['category_id'] == $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-control">
                        <option value="">-- Select Supplier --</option>
                        <?php foreach($sups as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $product['supplier_id'] == $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Brand</label>
                    <input type="text" name="brand" class="form-control" value="<?= esc($product['brand']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Unit Type</label>
                    <select name="unit_type" class="form-control">
                        <?php 
                        $units = ['pcs', 'kg', 'g', 'litre', 'ml', 'packet', 'box'];
                        foreach($units as $u) {
                            $selected = $product['unit_type'] === $u ? 'selected' : '';
                            echo "<option value=\"$u\" $selected>$u</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Pricing & Inventory -->
                <div class="form-group">
                    <label class="form-label">Purchase Price</label>
                    <input type="number" step="0.01" name="purchase_price" class="form-control" value="<?= esc($product['purchase_price']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Selling Price</label>
                    <input type="number" step="0.01" name="selling_price" class="form-control" value="<?= esc($product['selling_price']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tax %</label>
                    <input type="number" step="0.01" name="tax_percent" class="form-control" value="<?= esc($product['tax_percent']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Discount %</label>
                    <input type="number" step="0.01" name="discount_percent" class="form-control" value="<?= esc($product['discount_percent']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Initial Stock Quantity</label>
                    <input type="number" step="0.01" name="stock_quantity" class="form-control" value="<?= esc($product['stock_quantity']) ?>" <?= $is_edit ? 'readonly title="Update stock via Purchase module"' : '' ?>>
                    <?php if($is_edit): ?><small style="color: var(--secondary);">* Stock updates should be done via Purchases.</small><?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Reorder Level (Low Stock Alert)</label>
                    <input type="number" step="0.01" name="reorder_level" class="form-control" value="<?= esc($product['reorder_level']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control" value="<?= esc($product['expiry_date']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mt-3 text-right">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> <?= $is_edit ? 'Update' : 'Save' ?> Product</button>
            </div>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
