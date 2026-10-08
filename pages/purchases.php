<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin', 'manager']);

$page_title = 'Purchase History';

$stmt = $pdo->query("
    SELECT p.*, s.name as supplier_name, u.name as created_by_name 
    FROM purchases p 
    LEFT JOIN suppliers s ON p.supplier_id = s.id 
    LEFT JOIN users u ON p.created_by = u.id 
    ORDER BY p.purchase_date DESC, p.id DESC
");
$purchases = $stmt->fetchAll();

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Purchase / Stock Entries</h3>
        <a href="purchase_form.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Purchase</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice Number</th>
                        <th>Supplier</th>
                        <th>Total Amount</th>
                        <th>Created By</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($purchases)): ?>
                    <tr><td colspan="6" class="text-center">No purchases found.</td></tr>
                    <?php else: ?>
                        <?php foreach($purchases as $p): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($p['purchase_date'])) ?></td>
                            <td style="font-weight: 500; color: var(--primary);"><?= esc($p['invoice_number']) ?></td>
                            <td><?= esc($p['supplier_name']) ?></td>
                            <td style="font-weight: bold;"><?= format_currency($p['total_amount']) ?></td>
                            <td style="font-size: 0.875rem; color: var(--secondary);"><?= esc($p['created_by_name']) ?></td>
                            <td class="text-right">
                                <!-- Future functionality: View Purchase Details -->
                                <button class="btn btn-sm btn-icon" title="View details not implemented in this demo" onclick="alert('Purchase details view would be here.')">
                                    <i class="fas fa-eye"></i>
                                </button>
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
