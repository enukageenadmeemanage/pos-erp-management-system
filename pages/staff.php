<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin', 'manager', 'accountant']);

$page_title = 'Staff Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    
    if ($action === 'deactivate' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE staff SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);
        
        // Also deactivate linked user account if exists
        $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE staff_id = ?");
        $stmt->execute([$id]);
        
        set_flash_message('success', 'Staff member and their login access (if any) deactivated.');
        log_activity($pdo, $_SESSION['user_id'], 'Deactivate Staff', "Deactivated staff ID: $id");
    } elseif ($action === 'activate' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE staff SET status = 'active' WHERE id = ?");
        $stmt->execute([$id]);
        set_flash_message('success', 'Staff member activated.');
        log_activity($pdo, $_SESSION['user_id'], 'Activate Staff', "Activated staff ID: $id");
    }
    
    header("Location: staff.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM staff ORDER BY name ASC");
$staff = $stmt->fetchAll();

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Staff List</h3>
        <?php if (has_permission(['admin', 'manager'])): ?>
            <a href="staff_form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Staff</a>
        <?php endif; ?>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Job Title</th>
                        <th>Salary Details</th>
                        <th>Status</th>
                        <?php if (has_permission(['admin', 'manager'])): ?>
                            <th class="text-right">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($staff)): ?>
                    <tr><td colspan="5" class="text-center">No staff found.</td></tr>
                    <?php else: ?>
                        <?php foreach($staff as $s): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 500;"><?= esc($s['name']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--secondary);"><i class="fas fa-phone fa-fw"></i> <?= esc($s['phone']) ?></div>
                            </td>
                            <td><?= esc($s['job_title']) ?></td>
                            <td>
                                <div><?= format_currency($s['basic_salary']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--secondary); text-transform: capitalize;">(<?= esc($s['salary_type']) ?>)</div>
                            </td>
                            <td>
                                <?php if($s['status'] == 'active'): ?>
                                    <span style="background: var(--primary-light); color: var(--primary-dark); padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.75rem;">Active</span>
                                <?php else: ?>
                                    <span style="background: #FEE2E2; color: #991B1B; padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.75rem;">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <?php if (has_permission(['admin', 'manager'])): ?>
                                <td class="text-right">
                                    <a href="staff_form.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-icon" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="" style="display: inline-block;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <?php if($s['status'] == 'active'): ?>
                                            <input type="hidden" name="action" value="deactivate">
                                            <button type="submit" class="btn btn-sm btn-icon text-danger" title="Deactivate" onclick="return confirm('Deactivate this staff member?');">
                                                <i class="fas fa-user-slash"></i>
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="btn btn-sm btn-icon text-primary" title="Activate" onclick="return confirm('Activate this staff member?');">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
