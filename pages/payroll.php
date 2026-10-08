<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin', 'accountant']);

$page_title = 'Payroll Management';

// Fetch payroll records
$stmt = $pdo->query("
    SELECT p.*, s.name as staff_name, s.job_title 
    FROM payroll p 
    JOIN staff s ON p.staff_id = s.id 
    ORDER BY p.year DESC, p.month DESC, s.name ASC
");
$payrolls = $stmt->fetchAll();

// Handle Mark as Paid
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    
    if ($action === 'mark_paid' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE payroll SET payment_status = 'paid', payment_date = CURDATE() WHERE id = ?");
        $stmt->execute([$id]);
        set_flash_message('success', 'Payroll marked as paid.');
        log_activity($pdo, $_SESSION['user_id'], 'Payroll Paid', "Marked payroll ID $id as paid");
        header("Location: payroll.php");
        exit;
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Payroll Records</h3>
        <a href="payroll_form.php" class="btn btn-primary"><i class="fas fa-calculator"></i> Generate Payroll</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Staff Member</th>
                        <th class="text-right">Net Salary</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($payrolls)): ?>
                    <tr><td colspan="5" class="text-center">No payroll records found.</td></tr>
                    <?php else: ?>
                        <?php foreach($payrolls as $p): ?>
                        <tr>
                            <td>
                                <strong><?= date('F Y', mktime(0, 0, 0, $p['month'], 1, $p['year'])) ?></strong>
                            </td>
                            <td>
                                <div style="font-weight: 500;"><?= esc($p['staff_name']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--secondary);"><?= esc($p['job_title']) ?></div>
                            </td>
                            <td class="text-right font-weight-bold" style="font-weight: bold;">
                                <?= format_currency($p['net_salary']) ?>
                            </td>
                            <td>
                                <?php if($p['payment_status'] === 'paid'): ?>
                                    <span style="background: var(--primary-light); color: var(--primary-dark); padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.75rem;">Paid on <?= date('d M Y', strtotime($p['payment_date'])) ?></span>
                                <?php else: ?>
                                    <span style="background: #FEF3C7; color: #92400E; padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.75rem;">Unpaid</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <a href="../print/payslip.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-icon text-primary" title="Print Payslip" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                                <?php if($p['payment_status'] === 'unpaid'): ?>
                                    <form method="POST" action="" style="display: inline-block;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="mark_paid">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-icon text-success" title="Mark as Paid" onclick="return confirm('Mark this salary as paid?');">
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
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
