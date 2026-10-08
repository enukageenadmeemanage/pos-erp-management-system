<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin', 'manager', 'accountant']);

$page_title = 'Sales History';

// Search and filter
$search = $_GET['search'] ?? '';

$query = "
    SELECT s.*, u.name as cashier_name 
    FROM sales s 
    LEFT JOIN users u ON s.cashier_id = u.id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND s.invoice_number LIKE ?";
    $params[] = "%$search%";
}

$query .= " ORDER BY s.sale_date DESC LIMIT 100"; // limit to recent 100 for performance in this demo
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll();

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Recent Sales</h3>
        <form method="GET" action="" style="display: flex; gap: 0.5rem; max-width: 300px;">
            <input type="text" name="search" class="form-control" placeholder="Search Invoice #" value="<?= esc($search) ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Invoice Number</th>
                        <th>Cashier</th>
                        <th class="text-right">Total Amount</th>
                        <th>Payment Method</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($sales)): ?>
                    <tr><td colspan="6" class="text-center">No sales found.</td></tr>
                    <?php else: ?>
                        <?php foreach($sales as $s): ?>
                        <tr>
                            <td><?= date('M d, Y h:i A', strtotime($s['sale_date'])) ?></td>
                            <td style="font-weight: 500; color: var(--primary);"><?= esc($s['invoice_number']) ?></td>
                            <td style="font-size: 0.875rem; color: var(--secondary);"><?= esc($s['cashier_name']) ?></td>
                            <td class="text-right" style="font-weight: bold;"><?= format_currency($s['grand_total']) ?></td>
                            <td style="text-transform: capitalize;">
                                <?php if($s['payment_method'] == 'cash'): ?>
                                    <span style="color: var(--success);"><i class="fas fa-money-bill-wave"></i> Cash</span>
                                <?php elseif($s['payment_method'] == 'card'): ?>
                                    <span style="color: var(--info);"><i class="fas fa-credit-card"></i> Card</span>
                                <?php else: ?>
                                    <span style="color: var(--primary);"><i class="fas fa-mobile-alt"></i> Mobile</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <a href="../print/receipt.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-icon text-primary" title="Print Receipt" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
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
