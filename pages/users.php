<?php
// Check if user has required access
if (!checkAccess('users', 'view')) {
    setMessage('error', Language::get('access_denied'));
    redirect('index.php');
}

$canEdit = checkAccess('users', 'edit');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    $action = $_POST['action'] ?? '';
    
    try {
        $db->beginTransaction();
        
        switch ($action) {
            case 'create':
                // Create new user
                $username = cleanInput($_POST['username']);
                $password = cleanInput($_POST['password']);
                $role = cleanInput($_POST['role']);
                
                // Check if username exists
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    throw new Exception(Language::get('username_exists'));
                }
                
                // Insert user
                $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$username, generateHash($password), $role]);
                
                $userId = $db->lastInsertId();
                
                // Set access rights
                $menus = ['dashboard', 'bom', 'production', 'users', 'settings'];
                $stmt = $db->prepare("INSERT INTO user_access (user_id, menu_id, can_view, can_edit) VALUES (?, ?, ?, ?)");
                
                foreach ($menus as $menu) {
                    $canView = isset($_POST["view_$menu"]) ? 1 : 0;
                    $canEdit = isset($_POST["edit_$menu"]) ? 1 : 0;
                    $stmt->execute([$userId, $menu, $canView, $canEdit]);
                }
                
                setMessage('success', Language::get('user_created'));
                break;
                
            case 'update':
                // Update existing user
                $userId = (int)$_POST['user_id'];
                $role = cleanInput($_POST['role']);
                $status = (int)$_POST['status'];
                
                // Update user
                $stmt = $db->prepare("UPDATE users SET role = ?, status = ? WHERE id = ?");
                $stmt->execute([$role, $status, $userId]);
                
                // Update password if provided
                if (!empty($_POST['password'])) {
                    $password = cleanInput($_POST['password']);
                    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([generateHash($password), $userId]);
                }
                
                // Update access rights
                $menus = ['dashboard', 'bom', 'production', 'users', 'settings'];
                $stmt = $db->prepare("UPDATE user_access SET can_view = ?, can_edit = ? WHERE user_id = ? AND menu_id = ?");
                
                foreach ($menus as $menu) {
                    $canView = isset($_POST["view_$menu"]) ? 1 : 0;
                    $canEdit = isset($_POST["edit_$menu"]) ? 1 : 0;
                    $stmt->execute([$canView, $canEdit, $userId, $menu]);
                }
                
                setMessage('success', Language::get('user_updated'));
                break;
        }
        
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        setMessage('error', $e->getMessage());
    }
    
    redirect('index.php?page=users');
}

// Get all users
$query = "SELECT u.*, 
          MAX(CASE WHEN ua.menu_id = 'dashboard' THEN ua.can_view END) as view_dashboard,
          MAX(CASE WHEN ua.menu_id = 'dashboard' THEN ua.can_edit END) as edit_dashboard,
          MAX(CASE WHEN ua.menu_id = 'bom' THEN ua.can_view END) as view_bom,
          MAX(CASE WHEN ua.menu_id = 'bom' THEN ua.can_edit END) as edit_bom,
          MAX(CASE WHEN ua.menu_id = 'production' THEN ua.can_view END) as view_production,
          MAX(CASE WHEN ua.menu_id = 'production' THEN ua.can_edit END) as edit_production,
          MAX(CASE WHEN ua.menu_id = 'users' THEN ua.can_view END) as view_users,
          MAX(CASE WHEN ua.menu_id = 'users' THEN ua.can_edit END) as edit_users,
          MAX(CASE WHEN ua.menu_id = 'settings' THEN ua.can_view END) as view_settings,
          MAX(CASE WHEN ua.menu_id = 'settings' THEN ua.can_edit END) as edit_settings
          FROM users u
          LEFT JOIN user_access ua ON ua.user_id = u.id
          GROUP BY u.id
          ORDER BY u.username";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><?php echo Language::get('user_management'); ?></h5>
            <?php if ($canEdit): ?>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newUserModal">
                <i class="fas fa-plus"></i> <?php echo Language::get('new_user'); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?php echo Language::get('username'); ?></th>
                        <th><?php echo Language::get('role'); ?></th>
                        <th><?php echo Language::get('status'); ?></th>
                        <th><?php echo Language::get('last_login'); ?></th>
                        <th><?php echo Language::get('access_rights'); ?></th>
                        <?php if ($canEdit): ?>
                        <th><?php echo Language::get('action'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-2">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $user['status'] ? 'success' : 'danger'; ?>">
                                <?php echo $user['status'] ? Language::get('active') : Language::get('inactive'); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $user['last_login'] ? formatDateTime($user['last_login']) : '-'; ?>
                        </td>
                        <td>
                            <?php
                            $access = [];
                            foreach (['dashboard', 'bom', 'production', 'users', 'settings'] as $menu) {
                                if ($user["view_$menu"]) {
                                    $access[] = Language::get($menu) . 
                                              ($user["edit_$menu"] ? ' (' . Language::get('edit') . ')' : '');
                                }
                            }
                            echo implode(', ', $access);
                            ?>
                        </td>
                        <?php if ($canEdit): ?>
                        <td>
                            <button class="btn btn-info btn-sm" 
                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<!-- New User Modal -->
<div class="modal fade" id="newUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" id="newUserForm">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo Language::get('new_user'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('username'); ?></label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('password'); ?></label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('role'); ?></label>
                        <input type="text" class="form-control" name="role" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('access_rights'); ?></label>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th><?php echo Language::get('menu'); ?></th>
                                        <th><?php echo Language::get('view'); ?></th>
                                        <th><?php echo Language::get('edit'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (['dashboard', 'bom', 'production', 'users', 'settings'] as $menu): ?>
                                    <tr>
                                        <td><?php echo Language::get($menu); ?></td>
                                        <td>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" 
                                                       name="view_<?php echo $menu; ?>" 
                                                       id="new_view_<?php echo $menu; ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" 
                                                       name="edit_<?php echo $menu; ?>" 
                                                       id="new_edit_<?php echo $menu; ?>">
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?php echo Language::get('cancel'); ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <?php echo Language::get('create'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" id="editUserForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo Language::get('edit_user'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('username'); ?></label>
                        <input type="text" class="form-control" id="edit_username" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('new_password'); ?></label>
                        <input type="password" class="form-control" name="password" 
                               placeholder="<?php echo Language::get('leave_blank'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('role'); ?></label>
                        <input type="text" class="form-control" name="role" id="edit_role" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('status'); ?></label>
                        <select class="form-select" name="status" id="edit_status">
                            <option value="1"><?php echo Language::get('active'); ?></option>
                            <option value="0"><?php echo Language::get('inactive'); ?></option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('access_rights'); ?></label>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th><?php echo Language::get('menu'); ?></th>
                                        <th><?php echo Language::get('view'); ?></th>
                                        <th><?php echo Language::get('edit'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (['dashboard', 'bom', 'production', 'users', 'settings'] as $menu): ?>
                                    <tr>
                                        <td><?php echo Language::get($menu); ?></td>
                                        <td>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" 
                                                       name="view_<?php echo $menu; ?>" 
                                                       id="edit_view_<?php echo $menu; ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" 
                                                       name="edit_<?php echo $menu; ?>" 
                                                       id="edit_edit_<?php echo $menu; ?>">
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?php echo Language::get('cancel'); ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <?php echo Language::get('save'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_status').value = user.status;
    
    // Set access rights
    ['dashboard', 'bom', 'production', 'users', 'settings'].forEach(menu => {
        document.getElementById(`edit_view_${menu}`).checked = user[`view_${menu}`] == 1;
        document.getElementById(`edit_edit_${menu}`).checked = user[`edit_${menu}`] == 1;
    });
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

// Link view/edit checkboxes
document.querySelectorAll('input[type="checkbox"][id^="edit_edit_"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            const viewCheckbox = document.getElementById(this.id.replace('edit_', 'view_'));
            viewCheckbox.checked = true;
        }
    });
});

document.querySelectorAll('input[type="checkbox"][id^="edit_view_"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        if (!this.checked) {
            const editCheckbox = document.getElementById(this.id.replace('view_', 'edit_'));
            editCheckbox.checked = false;
        }
    });
});

// Same for new user form
document.querySelectorAll('input[type="checkbox"][id^="new_edit_"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            const viewCheckbox = document.getElementById(this.id.replace('edit_', 'view_'));
            viewCheckbox.checked = true;
        }
    });
});

document.querySelectorAll('input[type="checkbox"][id^="new_view_"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        if (!this.checked) {
            const editCheckbox = document.getElementById(this.id.replace('view_', 'edit_'));
            editCheckbox.checked = false;
        }
    });
});
</script>
<?php endif; ?>
