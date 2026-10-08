<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin', 'manager']);

$page_title = 'New Purchase Entry';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $supplier_id = $_POST['supplier_id'] ?? null;
    $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
    $invoice_number = trim($_POST['invoice_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['purchase_price'] ?? [];
    
    if (empty($invoice_number) || empty($supplier_id) || empty($product_ids)) {
        set_flash_message('error', 'Please fill all required fields and add at least one product.');
    } else {
        try {
            $pdo->beginTransaction();
            
            // Calculate total
            $total_amount = 0;
            foreach($product_ids as $index => $pid) {
                $qty = (float)$quantities[$index];
                $price = (float)$prices[$index];
                $total_amount += ($qty * $price);
            }
            
            // Insert Purchase
            $stmt = $pdo->prepare("INSERT INTO purchases (supplier_id, purchase_date, invoice_number, total_amount, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$supplier_id, $purchase_date, $invoice_number, $total_amount, $notes, $_SESSION['user_id']]);
            $purchase_id = $pdo->lastInsertId();
            
            // Insert Items & Update Stock
            $stmt_item = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, purchase_price, total) VALUES (?, ?, ?, ?, ?)");
            $stmt_stock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ?, purchase_price = ? WHERE id = ?");
            
            foreach($product_ids as $index => $pid) {
                $qty = (float)$quantities[$index];
                $price = (float)$prices[$index];
                $line_total = $qty * $price;
                
                // insert line item
                $stmt_item->execute([$purchase_id, $pid, $qty, $price, $line_total]);
                
                // update stock and update the system's recorded purchase price to the latest one
                $stmt_stock->execute([$qty, $price, $pid]);
            }
            
            $pdo->commit();
            set_flash_message('success', 'Purchase recorded and stock updated successfully.');
            log_activity($pdo, $_SESSION['user_id'], 'Add Purchase', "Recorded purchase invoice: $invoice_number");
            
            header("Location: purchases.php");
            exit;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                set_flash_message('error', 'Invoice number already exists.');
            } else {
                set_flash_message('error', 'Database error: ' . $e->getMessage());
            }
        }
    }
}

// Generate auto invoice number
$auto_invoice = 'PUR-' . date('Ymd') . '-' . rand(1000, 9999);

$suppliers = $pdo->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll();
$products = $pdo->query("SELECT id, name, purchase_price FROM products WHERE status='active' ORDER BY name")->fetchAll();

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= esc($page_title) ?></h3>
        <a href="purchases.php" class="btn btn-outline-danger btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="" id="purchaseForm">
            <?= csrf_field() ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div class="form-group">
                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id" class="form-control" required>
                        <option value="">-- Select Supplier --</option>
                        <?php foreach($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                    <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Invoice Number <span class="text-danger">*</span></label>
                    <input type="text" name="invoice_number" class="form-control" value="<?= esc($auto_invoice) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="Optional notes...">
                </div>
            </div>

            <h4 style="margin-bottom: 1rem; border-bottom: 1px solid #E5E7EB; padding-bottom: 0.5rem;">Purchase Items</h4>
            
            <div class="table-responsive" style="overflow: visible;">
                <table id="itemsTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="width: 150px;">Unit Price</th>
                            <th style="width: 150px;">Quantity</th>
                            <th style="width: 150px;">Total</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <!-- Dynamic Rows -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right" style="font-weight: bold; font-size: 1.1rem;">Grand Total:</td>
                            <td style="font-weight: bold; font-size: 1.1rem; color: var(--primary);" id="grandTotalText">0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-danger" onclick="addRow()"><i class="fas fa-plus"></i> Add Row</button>
                <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit"><i class="fas fa-save"></i> Save Purchase & Update Stock</button>
            </div>
        </form>
    </div>
</div>

<script>
const products = <?= json_encode($products) ?>;

function createProductSelect() {
    let html = '<select name="product_id[]" class="form-control" required onchange="updatePrice(this)"><option value="">Select Product...</option>';
    products.forEach(p => {
        html += `<option value="${p.id}" data-price="${p.purchase_price}">${p.name}</option>`;
    });
    html += '</select>';
    return html;
}

function updatePrice(selectElem) {
    const row = selectElem.closest('tr');
    const priceInput = row.querySelector('.item-price');
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    
    if(selectedOption.value) {
        priceInput.value = selectedOption.getAttribute('data-price');
    } else {
        priceInput.value = '';
    }
    calculateTotals();
}

function calculateTotals() {
    let grandTotal = 0;
    const rows = document.querySelectorAll('#itemsBody tr');
    
    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const total = qty * price;
        
        row.querySelector('.item-total').innerText = total.toFixed(2);
        grandTotal += total;
    });
    
    document.getElementById('grandTotalText').innerText = grandTotal.toFixed(2);
    
    // Disable submit if no items
    document.getElementById('btnSubmit').disabled = (rows.length === 0);
}

function addRow() {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    
    tr.innerHTML = `
        <td>${createProductSelect()}</td>
        <td><input type="number" step="0.01" name="purchase_price[]" class="form-control item-price" required oninput="calculateTotals()"></td>
        <td><input type="number" step="0.01" name="quantity[]" class="form-control item-qty" required value="1" oninput="calculateTotals()"></td>
        <td class="item-total" style="font-weight: 500; vertical-align: middle;">0.00</td>
        <td><button type="button" class="btn btn-sm btn-icon text-danger" onclick="this.closest('tr').remove(); calculateTotals();"><i class="fas fa-times"></i></button></td>
    `;
    tbody.appendChild(tr);
    calculateTotals();
}

// Add one row by default on load
document.addEventListener('DOMContentLoaded', () => {
    addRow();
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
