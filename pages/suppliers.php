<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin', 'manager']);

$page_title = 'Supplier Management';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    
    if ($action === 'delete' && $id > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE supplier_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            set_flash_message('error', 'Cannot delete supplier: Products are currently assigned to this supplier.');
        } else {
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'Supplier deleted successfully.');
            log_activity($pdo, $_SESSION['user_id'], 'Delete Supplier', "Deleted supplier ID: $id");
        }
    } else {
        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact_person'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($action === 'add' && !empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $contact, $phone, $email, $address, $notes]);
            set_flash_message('success', 'Supplier added successfully.');
            log_activity($pdo, $_SESSION['user_id'], 'Add Supplier', "Added supplier: $name");
            
        } elseif ($action === 'edit' && !empty($name) && $id > 0) {
            $stmt = $pdo->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=?, notes=? WHERE id=?");
            $stmt->execute([$name, $contact, $phone, $email, $address, $notes, $id]);
            set_flash_message('success', 'Supplier updated successfully.');
            log_activity($pdo, $_SESSION['user_id'], 'Edit Supplier', "Edited supplier ID: $id");
        }
    }
    
    header("Location: suppliers.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC");
$suppliers = $stmt->fetchAll();

include_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
    <!-- Add/Edit Form -->
    <div style="flex: 1; min-width: 300px; max-width: 400px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" id="form-title">Add New Supplier</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" id="form-action" value="add">
                    <input type="hidden" name="id" id="sup-id" value="">
                    
                    <div class="form-group">
                        <label class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sup-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Person</label>
                        <input type="text" class="form-control" id="sup-contact" name="contact_person">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" id="sup-phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="sup-email" name="email">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" id="sup-address" name="address" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="sup-notes" name="notes" rows="2"></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="btn-save">Save Supplier</button>
                        <button type="button" class="btn btn-outline-danger" id="btn-cancel" style="display:none;" onclick="resetForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Suppliers List -->
    <div style="flex: 2; min-width: 400px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Supplier List</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($suppliers)): ?>
                            <tr><td colspan="4" class="text-center">No suppliers found.</td></tr>
                            <?php else: ?>
                                <?php foreach($suppliers as $sup): ?>
                                <tr>
                                    <td style="font-weight: 500;">
                                        <?= esc($sup['name']) ?><br>
                                        <small style="color: var(--secondary); font-weight: normal;"><?= esc($sup['email']) ?></small>
                                    </td>
                                    <td><?= esc($sup['contact_person']) ?></td>
                                    <td><?= esc($sup['phone']) ?></td>
                                    <td class="text-right">
                                        <button class="btn btn-sm btn-icon" onclick="editSupplier(<?= htmlspecialchars(json_encode($sup), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="" style="display: inline-block;" onsubmit="return confirm('Delete this supplier?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $sup['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-icon text-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editSupplier(sup) {
    document.getElementById('form-title').innerText = 'Edit Supplier';
    document.getElementById('form-action').value = 'edit';
    document.getElementById('sup-id').value = sup.id;
    document.getElementById('sup-name').value = sup.name;
    document.getElementById('sup-contact').value = sup.contact_person || '';
    document.getElementById('sup-phone').value = sup.phone || '';
    document.getElementById('sup-email').value = sup.email || '';
    document.getElementById('sup-address').value = sup.address || '';
    document.getElementById('sup-notes').value = sup.notes || '';
    
    document.getElementById('btn-save').innerText = 'Update Supplier';
    document.getElementById('btn-cancel').style.display = 'inline-block';
    window.scrollTo(0, 0);
}

function resetForm() {
    document.getElementById('form-title').innerText = 'Add New Supplier';
    document.getElementById('form-action').value = 'add';
    document.getElementById('sup-id').value = '';
    document.getElementById('sup-name').value = '';
    document.getElementById('sup-contact').value = '';
    document.getElementById('sup-phone').value = '';
    document.getElementById('sup-email').value = '';
    document.getElementById('sup-address').value = '';
    document.getElementById('sup-notes').value = '';
    
    document.getElementById('btn-save').innerText = 'Save Supplier';
    document.getElementById('btn-cancel').style.display = 'none';
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
