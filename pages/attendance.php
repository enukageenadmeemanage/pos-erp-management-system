<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin', 'manager', 'accountant']);

$page_title = 'Attendance Management';
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Handle Save Attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $date = $_POST['attendance_date'] ?? date('Y-m-d');
    $staff_ids = $_POST['staff_id'] ?? [];
    $statuses = $_POST['status'] ?? [];
    $overtimes = $_POST['overtime_hours'] ?? [];
    $notes = $_POST['notes'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        $stmt_check = $pdo->prepare("SELECT id FROM attendance WHERE staff_id = ? AND attendance_date = ?");
        $stmt_insert = $pdo->prepare("INSERT INTO attendance (staff_id, attendance_date, status, overtime_hours, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_update = $pdo->prepare("UPDATE attendance SET status = ?, overtime_hours = ?, notes = ? WHERE id = ?");
        
        foreach($staff_ids as $index => $sid) {
            $status = $statuses[$index];
            $ot = (float)$overtimes[$index];
            $note = $notes[$index];
            
            // Check if record exists for this date
            $stmt_check->execute([$sid, $date]);
            $existing_id = $stmt_check->fetchColumn();
            
            if ($existing_id) {
                $stmt_update->execute([$status, $ot, $note, $existing_id]);
            } else {
                $stmt_insert->execute([$sid, $date, $status, $ot, $note, $_SESSION['user_id']]);
            }
        }
        
        $pdo->commit();
        set_flash_message('success', "Attendance saved for $date.");
        log_activity($pdo, $_SESSION['user_id'], 'Save Attendance', "Saved attendance for $date");
        
        header("Location: attendance.php?date=" . urlencode($date));
        exit;
    } catch (\PDOException $e) {
        $pdo->rollBack();
        set_flash_message('error', 'Database error: ' . $e->getMessage());
    }
}

// Fetch staff and their attendance for the selected date
$stmt = $pdo->prepare("
    SELECT s.id as staff_id, s.name, s.job_title, a.id as att_id, a.status, a.overtime_hours, a.notes 
    FROM staff s 
    LEFT JOIN attendance a ON s.id = a.staff_id AND a.attendance_date = ? 
    WHERE s.status = 'active'
    ORDER BY s.name
");
$stmt->execute([$selected_date]);
$records = $stmt->fetchAll();

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Mark Attendance</h3>
        <form method="GET" action="" style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="date" name="date" class="form-control" value="<?= esc($selected_date) ?>" onchange="this.form.submit()">
        </form>
    </div>
    <div class="card-body">
        
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="attendance_date" value="<?= esc($selected_date) ?>">
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th style="width: 150px;">Status</th>
                            <th style="width: 100px;">Overtime (Hrs)</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($records)): ?>
                        <tr><td colspan="4" class="text-center">No active staff found.</td></tr>
                        <?php else: ?>
                            <?php foreach($records as $r): ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="staff_id[]" value="<?= $r['staff_id'] ?>">
                                    <div style="font-weight: 500;"><?= esc($r['name']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--secondary);"><?= esc($r['job_title']) ?></div>
                                </td>
                                <td>
                                    <select name="status[]" class="form-control" required>
                                        <option value="present" <?= ($r['status'] ?? 'present') === 'present' ? 'selected' : '' ?>>Present</option>
                                        <option value="absent" <?= ($r['status'] ?? '') === 'absent' ? 'selected' : '' ?>>Absent</option>
                                        <option value="half-day" <?= ($r['status'] ?? '') === 'half-day' ? 'selected' : '' ?>>Half Day</option>
                                        <option value="leave" <?= ($r['status'] ?? '') === 'leave' ? 'selected' : '' ?>>On Leave</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.5" min="0" name="overtime_hours[]" class="form-control text-center" value="<?= esc($r['overtime_hours'] ?? '0') ?>">
                                </td>
                                <td>
                                    <input type="text" name="notes[]" class="form-control" value="<?= esc($r['notes'] ?? '') ?>" placeholder="Optional...">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if(!empty($records)): ?>
            <div class="mt-3 text-right">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Attendance</button>
            </div>
            <?php endif; ?>
        </form>

    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
