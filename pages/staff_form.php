<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin', 'manager']);

$id = $_GET['id'] ?? 0;
$is_edit = $id > 0;
$page_title = $is_edit ? 'Edit Staff' : 'Add New Staff';

$staff = [
    'name' => '', 'phone' => '', 'email' => '', 'address' => '', 'job_title' => '',
    'salary_type' => 'monthly', 'basic_salary' => '0.00', 'joining_date' => date('Y-m-d'),
    'emergency_contact' => '', 'status' => 'active', 'notes' => ''
];

if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $staff = $row;
    } else {
        set_flash_message('error', 'Staff member not found.');
        header("Location: staff.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $input = [
        trim($_POST['name'] ?? ''),
        trim($_POST['phone'] ?? ''),
        trim($_POST['email'] ?? ''),
        trim($_POST['address'] ?? ''),
        trim($_POST['job_title'] ?? ''),
        $_POST['salary_type'] ?? 'monthly',
        (float)($_POST['basic_salary'] ?? 0),
        !empty($_POST['joining_date']) ? $_POST['joining_date'] : null,
        trim($_POST['emergency_contact'] ?? ''),
        $_POST['status'] ?? 'active',
        trim($_POST['notes'] ?? '')
    ];
    
    if (empty($input[0])) {
        set_flash_message('error', 'Staff name is required.');
    } else {
        try {
            if ($is_edit) {
                $input[] = $id;
                $sql = "UPDATE staff SET name=?, phone=?, email=?, address=?, job_title=?, salary_type=?, 
                        basic_salary=?, joining_date=?, emergency_contact=?, status=?, notes=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($input);
                set_flash_message('success', 'Staff details updated successfully.');
                log_activity($pdo, $_SESSION['user_id'], 'Edit Staff', "Edited staff ID: $id");
            } else {
                $sql = "INSERT INTO staff (name, phone, email, address, job_title, salary_type, basic_salary, 
                        joining_date, emergency_contact, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($input);
                set_flash_message('success', 'Staff member added successfully.');
                log_activity($pdo, $_SESSION['user_id'], 'Add Staff', "Added staff: {$input[0]}");
            }
            header("Location: staff.php");
            exit;
        } catch (\PDOException $e) {
            set_flash_message('error', 'Database error occurred while saving.');
            $staff = array_merge($staff, $_POST);
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title"><?= esc($page_title) ?></h3>
        <a href="staff.php" class="btn btn-outline-danger btn-sm">Back to List</a>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?= csrf_field() ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <!-- Personal Info -->
                <div class="form-group">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= esc($staff['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Job Title</label>
                    <input type="text" name="job_title" class="form-control" value="<?= esc($staff['job_title']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= esc($staff['phone']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= esc($staff['email']) ?>">
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= esc($staff['address']) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Emergency Contact</label>
                    <input type="text" name="emergency_contact" class="form-control" value="<?= esc($staff['emergency_contact']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control" value="<?= esc($staff['joining_date']) ?>">
                </div>

                <!-- Payroll Info -->
                <div class="form-group">
                    <label class="form-label">Salary Type</label>
                    <select name="salary_type" class="form-control">
                        <option value="monthly" <?= $staff['salary_type'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="daily" <?= $staff['salary_type'] === 'daily' ? 'selected' : '' ?>>Daily</option>
                        <option value="hourly" <?= $staff['salary_type'] === 'hourly' ? 'selected' : '' ?>>Hourly</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Basic Salary</label>
                    <input type="number" step="0.01" name="basic_salary" class="form-control" value="<?= esc($staff['basic_salary']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= $staff['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $staff['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"><?= esc($staff['notes']) ?></textarea>
                </div>
            </div>

            <div class="mt-3 text-right">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> <?= $is_edit ? 'Update' : 'Save' ?> Staff</button>
            </div>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
