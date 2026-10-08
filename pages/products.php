<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin', 'manager']);

$page_title = 'Products Management';

// Handle Delete/Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    
    if ($action === 'delete' && $id > 0) {
        // Soft delete or check constraints
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = ?");
        $stmt->execute([$id]);
        $has_sales = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM purchase_items WHERE product_id = ?");
        $stmt->execute([$id]);
        $has_purchases = $stmt->fetchColumn();
        
        if ($has_sales > 0 || $has_purchases > 0) {
            // Soft delete by setting status to inactive
            $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('warning', 'Product deactivated. It cannot be permanently deleted because it has transaction history.');
            log_activity($pdo, $_SESSION['user_id'], 'Deactivate Product', "Deactivated product ID: $id");
        } else {
            // Hard delete
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'Product deleted successfully.');
            log_activity($pdo, $_SESSION['user_id'], 'Delete Product', "Deleted product ID: $id");
        }
    }
    
    header("Location: products.php");
    exit;
}

// Fetch products
$search = $_GET['search'] ?? '';
$cat_id = $_GET['category_id'] ?? '';

$query = "SELECT p.*, c.name as category_name, s.name as supplier_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN suppliers s ON p.supplier_id = s.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($cat_id)) {
    $query .= " AND p.category_id = ?";
    $params[] = $cat_id;
}

$query .= " ORDER BY p.name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Fetch categories for filter
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Product List</h3>
        <a href="product_form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</a>
    </div>
    <div class="card-body">
        
        <!-- Filter Form -->
        <form method="GET" action="" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                <label class="form-label">Search (Name, SKU, Barcode)</label>
                <input type="text" name="search" class="form-control" value="<?= esc($search) ?>" placeholder="Search...">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach($cats as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $cat_id == $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="products.php" class="btn btn-outline-danger">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Product Details</th>
                        <th>Category</th>
                        <th>Price (Buy / Sell)</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($products)): ?>
                    <tr><td colspan="6" class="text-center">No products found.</td></tr>
                    <?php else: ?>
                        <?php foreach($products as $p): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 500;"><?= esc($p['name']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--secondary);">SKU: <?= esc($p['sku']) ?> | Unit: <?= esc($p['unit_type']) ?></div>
                            </td>
                            <td><?= esc($p['category_name'] ?? '-') ?></td>
                            <td>
                                <span style="color: var(--secondary);"><?= format_currency($p['purchase_price']) ?></span> / <br>
                                <span style="font-weight: 600; color: var(--primary);"><?= format_currency($p['selling_price']) ?></span>
                            </td>
                            <td>
                                <?php 
                                    $stock_color = ($p['stock_quantity'] <= $p['reorder_level']) ? 'color: var(--danger); font-weight: bold;' : '';
                                ?>
                                <span style="<?= $stock_color ?>"><?= number_format($p['stock_quantity'], 2) ?></span>
                                <?php if($p['stock_quantity'] <= $p['reorder_level']): ?>
                                    <i class="fas fa-exclamation-triangle" style="color: var(--danger);" title="Low Stock"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($p['status'] == 'active'): ?>
                                    <span style="background: var(--primary-light); color: var(--primary-dark); padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.75rem;">Active</span>
                                <?php else: ?>
                                    <span style="background: #FEE2E2; color: #991B1B; padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.75rem;">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <a href="product_form.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="" style="display: inline-block;" onsubmit="return confirm('Delete or deactivate this product?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-icon text-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
