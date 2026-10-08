<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin']); // Only admin can manage users

$page_title = 'User Accounts';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'cashier';
        $staff_id = $_POST['staff_id'] ?: null;

        if (empty($name) || empty($email) || empty($password)) {
            set_flash_message('error', 'Name, Email, and Password are required.');
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, staff_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hash, $role, $staff_id]);
                set_flash_message('success', 'User account created.');
                log_activity($pdo, $_SESSION['user_id'], 'Add User', "Created user account for $email");
            } catch (\PDOException $e) {
                if ($e->getCode() == 23000) {
                    set_flash_message('error', 'Email already exists.');
                } else {
                    set_flash_message('error', 'Database error.');
                }
            }
        }
    } elseif ($action === 'change_password' && $id > 0) {
        $password = $_POST['password'] ?? '';
        if (empty($password)) {
            set_flash_message('error', 'Password cannot be empty.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $id]);
            set_flash_message('success', 'Password updated successfully.');
            log_activity($pdo, $_SESSION['user_id'], 'Change Password', "Changed password for user ID: $id");
        }
    } elseif ($action === 'toggle_status' && $id > 0) {
        if ($id == $_SESSION['user_id']) {
            set_flash_message('error', 'You cannot deactivate your own account.');
        } else {
            $stmt = $pdo->prepare("UPDATE users SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'User status toggled.');
            log_activity($pdo, $_SESSION['user_id'], 'Toggle User Status', "Toggled status for user ID: $id");
        }
    }
    header("Location: users.php");
    exit;
}

// Fetch users
$stmt = $pdo->query("SELECT u.*, s.name as staff_name FROM users u LEFT JOIN staff s ON u.staff_id = s.id ORDER BY u.name");
$users = $stmt->fetchAll();

// Fetch staff for dropdown
$staff_list = $pdo->query("SELECT id, name FROM staff WHERE status = 'active' ORDER BY name")->fetchAll();

include_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
    <!-- Add User Form -->
    <div style="flex: 1; min-width: 300px; max-width: 400px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Add New User</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-control">
                            <option value="cashier">Cashier</option>
                            <option value="manager">Manager</option>
                            <option value="accountant">Accountant</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Link to Staff Profile (Optional)</label>
                        <select name="staff_id" class="form-control">
                            <option value="">-- No Staff Link --</option>
                            <?php foreach($staff_list as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mt-3 w-100">Create Account</button>
                </form>
            </div>
        </div>

        <!-- Change Password Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Change User Password</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label class="form-label">Select User</label>
                        <select name="id" class="form-control" required>
                            <option value="">-- Select User --</option>
                            <?php foreach($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?> (<?= esc($u['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">Update Password</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Users List -->
    <div style="flex: 2; min-width: 400px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">System Users</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name / Email</th>
                                <th>Role</th>
                                <th>Staff Link</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500;"><?= esc($u['name']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--secondary);"><?= esc($u['email']) ?></div>
                                </td>
                                <td><span style="text-transform: capitalize; font-weight: 500;"><?= esc($u['role']) ?></span></td>
                                <td><?= $u['staff_name'] ? esc($u['staff_name']) : '<span style="color:var(--secondary)">None</span>' ?></td>
                                <td>
                                    <?php if($u['status'] == 'active'): ?>
                                        <span style="color: var(--primary-dark); font-size: 0.875rem;"><i class="fas fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span style="color: #991B1B; font-size: 0.875rem;"><i class="fas fa-times-circle"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" action="" style="display: inline-block;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $u['status'] == 'active' ? 'btn-outline-danger' : 'btn-primary' ?>" onclick="return confirm('Change status for this user?');">
                                            <?= $u['status'] == 'active' ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
