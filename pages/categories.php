<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin', 'manager']);

$page_title = 'Categories Management';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($action === 'add' && !empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        set_flash_message('success', 'Category added successfully.');
        log_activity($pdo, $_SESSION['user_id'], 'Add Category', "Added category: $name");
        
    } elseif ($action === 'edit' && !empty($name) && $id > 0) {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $id]);
        set_flash_message('success', 'Category updated successfully.');
        log_activity($pdo, $_SESSION['user_id'], 'Edit Category', "Edited category ID: $id");
        
    } elseif ($action === 'delete' && $id > 0) {
        // Check if category is used by products
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            set_flash_message('error', 'Cannot delete category: It is currently assigned to one or more products.');
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'Category deleted successfully.');
            log_activity($pdo, $_SESSION['user_id'], 'Delete Category', "Deleted category ID: $id");
        }
    }
    
    // Redirect to refresh page
    header("Location: categories.php");
    exit;
}

// Fetch all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

include_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
    <!-- Add/Edit Form -->
    <div style="flex: 1; min-width: 300px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" id="form-title">Add New Category</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" id="form-action" value="add">
                    <input type="hidden" name="id" id="cat-id" value="">
                    
                    <div class="form-group">
                        <label class="form-label" for="cat-name">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cat-name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="cat-desc">Description</label>
                        <textarea class="form-control" id="cat-desc" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="btn-save">Save Category</button>
                        <button type="button" class="btn btn-outline-danger" id="btn-cancel" style="display:none;" onclick="resetForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Category List -->
    <div style="flex: 2; min-width: 400px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Category List</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($categories)): ?>
                            <tr><td colspan="3" class="text-center">No categories found.</td></tr>
                            <?php else: ?>
                                <?php foreach($categories as $cat): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?= esc($cat['name']) ?></td>
                                    <td style="color: var(--secondary);"><?= esc($cat['description']) ?></td>
                                    <td class="text-right">
                                        <button class="btn btn-sm btn-icon" onclick="editCategory(<?= $cat['id'] ?>, '<?= esc(addslashes($cat['name'])) ?>', '<?= esc(addslashes($cat['description'] ?? '')) ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
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
function editCategory(id, name, desc) {
    document.getElementById('form-title').innerText = 'Edit Category';
    document.getElementById('form-action').value = 'edit';
    document.getElementById('cat-id').value = id;
    document.getElementById('cat-name').value = name;
    document.getElementById('cat-desc').value = desc;
    document.getElementById('btn-save').innerText = 'Update Category';
    document.getElementById('btn-cancel').style.display = 'inline-block';
    document.getElementById('cat-name').focus();
}

function resetForm() {
    document.getElementById('form-title').innerText = 'Add New Category';
    document.getElementById('form-action').value = 'add';
    document.getElementById('cat-id').value = '';
    document.getElementById('cat-name').value = '';
    document.getElementById('cat-desc').value = '';
    document.getElementById('btn-save').innerText = 'Save Category';
    document.getElementById('btn-cancel').style.display = 'none';
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
