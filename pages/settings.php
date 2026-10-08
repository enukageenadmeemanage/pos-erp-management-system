<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin']); // Only admin can change system settings

$page_title = 'System Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    // Settings to process
    $keys = [
        'store_name', 'store_address', 'store_phone', 'currency_symbol', 'timezone', 'receipt_footer'
    ];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $stmt->execute([trim($_POST[$key]), $key]);
            }
        }
        
        $pdo->commit();
        set_flash_message('success', 'Settings updated successfully.');
        log_activity($pdo, $_SESSION['user_id'], 'Update Settings', 'Updated system configurations');
        
        // Refresh settings in memory
        $settings = load_settings($pdo);
        
        header("Location: settings.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash_message('error', 'Database error occurred.');
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cogs"></i> General Store Settings</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?= csrf_field() ?>
            
            <div class="form-group">
                <label class="form-label">Store Name</label>
                <input type="text" name="store_name" class="form-control" value="<?= esc($settings['store_name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Store Address (Appears on Receipt)</label>
                <textarea name="store_address" class="form-control" rows="3"><?= esc($settings['store_address'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Store Phone</label>
                <input type="text" name="store_phone" class="form-control" value="<?= esc($settings['store_phone'] ?? '') ?>">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Currency Symbol</label>
                    <input type="text" name="currency_symbol" class="form-control" value="<?= esc($settings['currency_symbol'] ?? '$') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Timezone</label>
                    <select name="timezone" class="form-control" required>
                        <option value="Asia/Colombo" <?= ($settings['timezone'] ?? '') === 'Asia/Colombo' ? 'selected' : '' ?>>Asia/Colombo (Sri Lanka)</option>
                        <option value="Asia/Kolkata" <?= ($settings['timezone'] ?? '') === 'Asia/Kolkata' ? 'selected' : '' ?>>Asia/Kolkata (India)</option>
                        <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                        <option value="America/New_York" <?= ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>America/New_York</option>
                        <option value="Europe/London" <?= ($settings['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' ?>>Europe/London</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Receipt Footer Message</label>
                <textarea name="receipt_footer" class="form-control" rows="2"><?= esc($settings['receipt_footer'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mt-3"><i class="fas fa-save"></i> Save Settings</button>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
