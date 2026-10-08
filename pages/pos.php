<?php
require_once __DIR__ . '/../includes/auth.php';

// Cashier, Manager, and Admin can access POS
require_permission(['admin', 'manager', 'cashier']);

$page_title = 'POS - Point of Sale';

// Handle POS Save via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_sale') {
    require_csrf();
    
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $amount_paid = (float)($_POST['amount_paid'] ?? 0);
    $global_discount = (float)($_POST['global_discount'] ?? 0);
    
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['price'] ?? []; // selling price at time of sale
    
    if (empty($product_ids)) {
        set_flash_message('error', 'Cart is empty.');
        header("Location: pos.php");
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        $subtotal = 0;
        $total_tax = 0;
        
        // Validation pass
        $items_data = [];
        foreach($product_ids as $index => $pid) {
            $qty = (float)$quantities[$index];
            $price = (float)$prices[$index];
            
            // Get product from DB
            $stmt = $pdo->prepare("SELECT name, stock_quantity, tax_percent, purchase_price FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$pid]);
            $p = $stmt->fetch();
            
            if (!$p) {
                throw new Exception("Product ID $pid not found.");
            }
            if ($qty > $p['stock_quantity']) {
                throw new Exception("Insufficient stock for {$p['name']}. Available: {$p['stock_quantity']}");
            }
            
            $line_sub = $qty * $price;
            $tax_amt = $line_sub * ($p['tax_percent'] / 100);
            $line_total = $line_sub + $tax_amt;
            
            $subtotal += $line_sub;
            $total_tax += $tax_amt;
            
            $items_data[] = [
                'id' => $pid,
                'qty' => $qty,
                'price' => $price,
                'purchase_price' => $p['purchase_price'], // for profit report
                'tax' => $tax_amt,
                'total' => $line_total
            ];
        }
        
        $grand_total = $subtotal + $total_tax - $global_discount;
        $balance = $amount_paid - $grand_total;
        
        $invoice_number = 'SALE-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Insert Sale
        $stmt_sale = $pdo->prepare("INSERT INTO sales (invoice_number, sale_date, subtotal, tax_amount, discount_amount, grand_total, payment_method, amount_paid, balance_amount, cashier_id) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_sale->execute([$invoice_number, $subtotal, $total_tax, $global_discount, $grand_total, $payment_method, $amount_paid, $balance, $_SESSION['user_id']]);
        $sale_id = $pdo->lastInsertId();
        
        // Insert Sale Items & Deduct Stock
        $stmt_item = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, purchase_price_snapshot, tax_amount, line_total) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_stock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        
        foreach($items_data as $item) {
            $stmt_item->execute([$sale_id, $item['id'], $item['qty'], $item['price'], $item['purchase_price'], $item['tax'], $item['total']]);
            $stmt_stock->execute([$item['qty'], $item['id']]);
        }
        
        $pdo->commit();
        log_activity($pdo, $_SESSION['user_id'], 'POS Sale', "Created sale $invoice_number");
        
        // Redirect to print receipt
        header("Location: ../print/receipt.php?id=" . $sale_id);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash_message('error', $e->getMessage());
        header("Location: pos.php");
        exit;
    }
}

// Fetch active products for JS Search
$stmt = $pdo->query("SELECT id, name, sku, barcode, selling_price, stock_quantity, tax_percent FROM products WHERE status = 'active'");
$products_json = json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

include_once __DIR__ . '/../includes/header.php';
?>

<style>
/* Custom POS Layout */
.wrapper {
    height: 100vh;
    overflow: hidden;
}
.sidebar {
    display: none; /* Hide sidebar in POS for more space */
}
.main-content {
    height: 100vh;
}
.pos-container {
    display: flex;
    height: calc(100vh - var(--topbar-height) - 40px); /* 40px for content padding */
    gap: 1.5rem;
}
.pos-products {
    flex: 3;
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
}
.pos-search {
    padding: 1rem;
    border-bottom: 1px solid #E5E7EB;
}
.pos-product-grid {
    padding: 1rem;
    overflow-y: auto;
    flex: 1;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 1rem;
    align-content: flex-start;
}
.pos-item {
    border: 1px solid #E5E7EB;
    border-radius: var(--radius);
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}
.pos-item:hover {
    border-color: var(--primary);
    background: var(--primary-light);
}
.pos-item.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pos-cart {
    flex: 2;
    min-width: 350px;
    background: #fff;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    display: flex;
    flex-direction: column;
}
.pos-cart-header {
    padding: 1rem;
    border-bottom: 1px solid #E5E7EB;
    font-weight: 600;
}
.pos-cart-items {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}
.cart-table th { background: transparent; padding-top: 0; padding-bottom: 0.5rem; }
.cart-table td { padding: 0.5rem 0; border-bottom: 1px dashed #E5E7EB; vertical-align: middle; }
.qty-btn { width: 24px; height: 24px; border-radius: 50%; border: 1px solid #ccc; background: #fff; cursor: pointer; }

.pos-cart-totals {
    background: #F9FAFB;
    padding: 1rem;
    border-top: 1px solid #E5E7EB;
}
.total-row { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
.grand-total-row { font-size: 1.5rem; font-weight: bold; color: var(--primary); border-top: 2px solid #E5E7EB; padding-top: 0.5rem; margin-top: 0.5rem; }

.pos-checkout {
    padding: 1rem;
    background: #fff;
    border-top: 1px solid #E5E7EB;
}
</style>

<div class="pos-container">
    
    <!-- Left: Products List -->
    <div class="pos-products">
        <div class="pos-search">
            <div style="position: relative;">
                <i class="fas fa-search" style="position: absolute; left: 1rem; top: 0.8rem; color: var(--secondary);"></i>
                <input type="text" id="searchInput" class="form-control" style="padding-left: 2.5rem; font-size: 1.1rem;" placeholder="Search product by Name, SKU, or Barcode..." autofocus>
            </div>
        </div>
        <div class="pos-product-grid" id="productGrid">
            <!-- Products rendered by JS -->
        </div>
    </div>

    <!-- Right: Cart & Checkout -->
    <div class="pos-cart">
        <div class="pos-cart-header d-flex justify-content-between align-items-center">
            <span>Current Sale</span>
            <button class="btn btn-sm btn-outline-danger" onclick="clearCart()">Clear</button>
        </div>
        
        <div class="pos-cart-items">
            <form id="posForm" method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_sale">
                <input type="hidden" name="global_discount" id="input_global_discount" value="0">
                
                <table class="cart-table w-100" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="width: 80px; text-align: center;">Qty</th>
                            <th style="width: 60px; text-align: right;">Total</th>
                            <th style="width: 30px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <!-- Cart items rendered by JS -->
                    </tbody>
                </table>
        </div>
        
        <div class="pos-cart-totals">
            <div class="total-row"><span>Subtotal</span> <span id="txtSubtotal">0.00</span></div>
            <div class="total-row"><span>Tax</span> <span id="txtTax">0.00</span></div>
            <div class="total-row" style="cursor: pointer; color: var(--info);" onclick="setDiscount()">
                <span>Discount <i class="fas fa-edit"></i></span> <span id="txtDiscount">0.00</span>
            </div>
            <div class="total-row grand-total-row"><span>Total</span> <span id="txtGrandTotal">0.00</span></div>
        </div>
        
        <div class="pos-checkout">
            <div class="d-flex gap-2 mb-3">
                <div style="flex: 1;">
                    <label style="font-size: 0.75rem;">Payment Method</label>
                    <select name="payment_method" class="form-control">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="mobile">Mobile App</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 0.75rem;">Amount Paid</label>
                    <input type="number" step="0.01" name="amount_paid" id="input_amount_paid" class="form-control" value="" oninput="calcChange()">
                </div>
            </div>
            
            <div class="total-row mb-3" style="color: var(--secondary);">
                <span>Change/Balance:</span> <span id="txtChange" style="font-weight: bold;">0.00</span>
            </div>
            
            <button type="button" class="btn btn-primary w-100" style="font-size: 1.25rem; padding: 1rem;" onclick="submitSale()">
                <i class="fas fa-check-circle"></i> Complete Sale
            </button>
            </form>
        </div>
    </div>
</div>

<script>
// Expose data to pos.js
const currencySymbol = '<?= $settings['currency_symbol'] ?? '$' ?>';
const productsData = <?= $products_json ?>;

document.addEventListener('DOMContentLoaded', () => {
    // Inject a back button into the topbar
    const topbarLeft = document.querySelector('.topbar-left');
    if (topbarLeft) {
        const backBtn = document.createElement('a');
        backBtn.href = 'dashboard.php';
        backBtn.className = 'btn btn-sm btn-outline-secondary';
        backBtn.innerHTML = '<i class="fas fa-arrow-left"></i> Back to Dashboard';
        backBtn.style.marginLeft = '1rem';
        topbarLeft.appendChild(backBtn);
        
        // Hide the useless sidebar toggle button on POS page
        const toggle = document.getElementById('sidebar-toggle');
        if (toggle) toggle.style.display = 'none';
    }
});
</script>
<script src="<?= BASE_URL ?>/assets/js/pos.js"></script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
