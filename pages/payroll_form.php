<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin', 'accountant']);

$page_title = 'Generate Payroll';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $staff_id = $_POST['staff_id'] ?? 0;
    $month = $_POST['month'] ?? date('n');
    $year = $_POST['year'] ?? date('Y');
    
    $working_days = (int)($_POST['working_days'] ?? 0);
    $bonus = (float)($_POST['bonus'] ?? 0);
    $advance = (float)($_POST['advance'] ?? 0);
    $other_deductions = (float)($_POST['other_deductions'] ?? 0);
    
    try {
        // Fetch staff details
        $stmt = $pdo->prepare("SELECT basic_salary, salary_type FROM staff WHERE id = ?");
        $stmt->execute([$staff_id]);
        $staff = $stmt->fetch();
        
        if (!$staff) throw new Exception("Staff member not found.");
        
        // Calculate attendance stats for the month
        $stmt = $pdo->prepare("
            SELECT 
                SUM(IF(status='present', 1, 0)) as present_days,
                SUM(IF(status='half-day', 0.5, 0)) as half_days,
                SUM(IF(status='absent', 1, 0)) as absent_days,
                SUM(overtime_hours) as total_ot
            FROM attendance 
            WHERE staff_id = ? AND MONTH(attendance_date) = ? AND YEAR(attendance_date) = ?
        ");
        $stmt->execute([$staff_id, $month, $year]);
        $att = $stmt->fetch();
        
        $present_total = (float)$att['present_days'] + (float)$att['half_days'];
        $absent_total = (float)$att['absent_days'];
        $ot_hours = (float)$att['total_ot'];
        
        $basic_salary = (float)$staff['basic_salary'];
        $gross_salary = 0;
        $ot_amount = 0;
        
        // Simple salary calculation logic
        if ($staff['salary_type'] === 'monthly') {
            $daily_rate = $working_days > 0 ? ($basic_salary / $working_days) : 0;
            // Deduct for absences
            $absence_deduction = $absent_total * $daily_rate;
            // Assuming OT rate is 1.5x hourly rate (assuming 8 hour workday)
            $hourly_rate = $daily_rate / 8;
            $ot_amount = $ot_hours * $hourly_rate * 1.5;
            
            $gross_salary = $basic_salary + $ot_amount + $bonus;
            $total_deductions = $absence_deduction + $advance + $other_deductions;
            
        } elseif ($staff['salary_type'] === 'daily') {
            $gross_salary = ($present_total * $basic_salary) + $bonus;
            $total_deductions = $advance + $other_deductions;
        } else {
            // hourly
            $gross_salary = ($present_total * 8 * $basic_salary) + ($ot_hours * $basic_salary * 1.5) + $bonus;
            $total_deductions = $advance + $other_deductions;
        }
        
        $net_salary = $gross_salary - $total_deductions;
        if ($net_salary < 0) $net_salary = 0;
        
        // Insert or Update Payroll Record
        $sql = "INSERT INTO payroll (staff_id, month, year, basic_salary, working_days, present_days, absent_days, overtime_hours, overtime_amount, bonus, advance, other_deductions, gross_salary, total_deductions, net_salary, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                basic_salary=VALUES(basic_salary), working_days=VALUES(working_days), present_days=VALUES(present_days), absent_days=VALUES(absent_days), overtime_hours=VALUES(overtime_hours), overtime_amount=VALUES(overtime_amount), bonus=VALUES(bonus), advance=VALUES(advance), other_deductions=VALUES(other_deductions), gross_salary=VALUES(gross_salary), total_deductions=VALUES(total_deductions), net_salary=VALUES(net_salary), created_by=VALUES(created_by)";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $staff_id, $month, $year, $basic_salary, $working_days, $present_total, $absent_total, 
            $ot_hours, $ot_amount, $bonus, $advance, $other_deductions, 
            $gross_salary, $total_deductions, $net_salary, $_SESSION['user_id']
        ]);
        
        $payroll_id = $pdo->lastInsertId();
        if($payroll_id == 0) {
            // fetch id if it was an update
            $stmt = $pdo->prepare("SELECT id FROM payroll WHERE staff_id=? AND month=? AND year=?");
            $stmt->execute([$staff_id, $month, $year]);
            $payroll_id = $stmt->fetchColumn();
        }
        
        set_flash_message('success', 'Payroll generated successfully.');
        log_activity($pdo, $_SESSION['user_id'], 'Generate Payroll', "Generated payroll for staff ID: $staff_id, Month: $month/$year");
        
        header("Location: ../print/payslip.php?id=" . $payroll_id);
        exit;
        
    } catch (Exception $e) {
        set_flash_message('error', $e->getMessage());
    }
}

$staff_list = $pdo->query("SELECT id, name, basic_salary, salary_type FROM staff WHERE status='active' ORDER BY name")->fetchAll();

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Calculate & Save Payroll</h3>
        <a href="payroll.php" class="btn btn-outline-danger btn-sm">Cancel</a>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> This tool automatically fetches attendance data for the selected month to calculate deductions and overtime.
        </div>
        
        <form method="POST" action="">
            <?= csrf_field() ?>
            
            <div class="form-group">
                <label class="form-label">Select Staff Member</label>
                <select name="staff_id" class="form-control" required>
                    <option value="">-- Select Staff --</option>
                    <?php foreach($staff_list as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?> (<?= esc($s['salary_type']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-control" required>
                        <?php for($m=1; $m<=12; $m++): ?>
                            <option value="<?= $m ?>" <?= date('n') == $m ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" class="form-control" value="<?= date('Y') ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Total Working Days in Month (for Monthly Salary deduction calculation)</label>
                <input type="number" name="working_days" class="form-control" value="26">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem; border-top: 1px solid #eee; padding-top: 1rem;">
                <div class="form-group">
                    <label class="form-label">Additions: Bonus</label>
                    <input type="number" step="0.01" name="bonus" class="form-control" value="0.00">
                </div>
                <div></div>
                <div class="form-group">
                    <label class="form-label">Deductions: Advance Taken</label>
                    <input type="number" step="0.01" name="advance" class="form-control" value="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Other Deductions</label>
                    <input type="number" step="0.01" name="other_deductions" class="form-control" value="0.00">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mt-3"><i class="fas fa-calculator"></i> Calculate & Generate Payslip</button>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
