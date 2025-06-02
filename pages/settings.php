<?php
// Check if user has required access
if (!checkAccess('settings', 'view')) {
    setMessage('error', Language::get('access_denied'));
    redirect('index.php');
}

$canEdit = checkAccess('settings', 'edit');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    try {
        $db->beginTransaction();
        
        // Update system name
        if (isset($_POST['system_name'])) {
            $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'system_name'");
            $stmt->execute([cleanInput($_POST['system_name'])]);
        }
        
        // Handle logo upload
        if (isset($_FILES['system_logo']) && $_FILES['system_logo']['error'] === UPLOAD_ERR_OK) {
            $logoPath = 'assets/images/';
            if (!file_exists($logoPath)) {
                mkdir($logoPath, 0777, true);
            }
            
            $logo = uploadFile($_FILES['system_logo'], $logoPath, ['image/jpeg', 'image/png']);
            if ($logo) {
                $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'system_logo'");
                $stmt->execute([$logo]);
            }
        }
        
        // Update default language
        if (isset($_POST['default_language'])) {
            $newLang = cleanInput($_POST['default_language']);
            if (array_key_exists($newLang, $GLOBALS['available_languages'])) {
                $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'default_language'");
                $stmt->execute([$newLang]);
            }
        }
        
        $db->commit();
        setMessage('success', Language::get('settings_updated'));
    } catch (Exception $e) {
        $db->rollBack();
        setMessage('error', Language::get('update_failed'));
        error_log($e->getMessage());
    }
    
    redirect('index.php?page=settings');
}

// Get current settings
$query = "SELECT setting_key, setting_value FROM settings";
$stmt = $db->prepare($query);
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="row">
    <!-- System Settings -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo Language::get('system_settings'); ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- System Name -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('system_name'); ?></label>
                        <input type="text" class="form-control" name="system_name" 
                               value="<?php echo htmlspecialchars($settings['system_name'] ?? ''); ?>"
                               <?php echo $canEdit ? '' : 'readonly'; ?>>
                    </div>
                    
                    <!-- System Logo -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('system_logo'); ?></label>
                        <?php if (!empty($settings['system_logo'])): ?>
                        <div class="mb-2">
                            <img src="assets/images/<?php echo htmlspecialchars($settings['system_logo']); ?>" 
                                 alt="System Logo" class="img-thumbnail" style="max-height: 100px;">
                        </div>
                        <?php endif; ?>
                        <?php if ($canEdit): ?>
                        <input type="file" class="form-control" name="system_logo" accept="image/jpeg,image/png">
                        <small class="form-text text-muted">
                            <?php echo Language::get('logo_requirements'); ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Default Language -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('default_language'); ?></label>
                        <select class="form-select" name="default_language" 
                                <?php echo $canEdit ? '' : 'disabled'; ?>>
                            <?php foreach ($GLOBALS['available_languages'] as $code => $name): ?>
                            <option value="<?php echo $code; ?>" 
                                    <?php echo ($settings['default_language'] ?? '') === $code ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if ($canEdit): ?>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo Language::get('save'); ?>
                    </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Language Settings -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo Language::get('language_settings'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?php echo Language::get('language'); ?></th>
                                <th><?php echo Language::get('status'); ?></th>
                                <th><?php echo Language::get('strings_count'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($GLOBALS['available_languages'] as $code => $name): ?>
                            <?php
                            // Get string count for each language
                            $stmt = $db->prepare("SELECT COUNT(*) FROM language_strings WHERE language_code = ?");
                            $stmt->execute([$code]);
                            $stringCount = $stmt->fetchColumn();
                            ?>
                            <tr>
                                <td><?php echo $name; ?></td>
                                <td>
                                    <span class="badge bg-success">
                                        <?php echo Language::get('active'); ?>
                                    </span>
                                </td>
                                <td><?php echo $stringCount; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($canEdit): ?>
                <div class="mt-3">
                    <button type="button" class="btn btn-secondary" onclick="initializeStrings()">
                        <i class="fas fa-sync"></i> <?php echo Language::get('reinitialize_strings'); ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<script>
function initializeStrings() {
    if (confirm('<?php echo Language::get('reinitialize_confirm'); ?>')) {
        fetch('ajax/initialize_strings.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('<?php echo Language::get('strings_initialized'); ?>', 'success');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showNotification('<?php echo Language::get('initialization_failed'); ?>', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('<?php echo Language::get('system_error'); ?>', 'error');
        });
    }
}
</script>
<?php endif; ?>
