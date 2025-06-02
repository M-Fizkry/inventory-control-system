<?php
// Check if user has edit access
$canEdit = checkAccess('production', 'edit');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    try {
        $db->beginTransaction();
        
        $planType = $_POST['plan_type'];
        $planCode = $_POST['plan_code'];
        $description = $_POST['description'];
        $items = $_POST['items'] ?? [];
        $quantities = $_POST['quantities'] ?? [];
        
        // Insert production plan
        $stmt = $db->prepare("INSERT INTO production_plans (plan_type, plan_code, description, status) VALUES (?, ?, ?, 'draft')");
        $stmt->execute([$planType, $planCode, $description]);
        $planId = $db->lastInsertId();
        
        // Insert production items
        $stmt = $db->prepare("INSERT INTO production_items (plan_id, item_id, quantity, status) VALUES (?, ?, ?, 'pending')");
        
        foreach ($items as $index => $itemId) {
            if (!empty($itemId) && isset($quantities[$index])) {
                $stmt->execute([$planId, $itemId, $quantities[$index]]);
            }
        }
        
        $db->commit();
        setMessage('success', Language::get('save_success'));
    } catch (Exception $e) {
        $db->rollBack();
        setMessage('error', Language::get('save_failed'));
        error_log($e->getMessage());
    }
    
    redirect('index.php?page=production');
}

// Get all finished items
$query = "SELECT id, code, name FROM items WHERE type = 'finished' ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$finishedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get production plans
$query = "SELECT 
            p.*,
            COUNT(pi.id) as item_count,
            SUM(CASE WHEN pi.status = 'completed' THEN 1 ELSE 0 END) as completed_count
          FROM production_plans p
          LEFT JOIN production_items pi ON pi.plan_id = p.id
          GROUP BY p.id
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define plan codes for each type
$planCodes = [
    '1' => ['9110'],
    '2' => ['9210', '9220', '9230'],
    '3' => ['9310', '9320', '9330']
];
?>

<div class="row mb-4">
    <!-- Production Plans List -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><?php echo Language::get('production_plans'); ?></h5>
                    <?php if ($canEdit): ?>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newPlanModal">
                        <i class="fas fa-plus"></i> <?php echo Language::get('new_plan'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php echo Language::get('plan_code'); ?></th>
                                <th><?php echo Language::get('description'); ?></th>
                                <th><?php echo Language::get('progress'); ?></th>
                                <th><?php echo Language::get('status'); ?></th>
                                <th><?php echo Language::get('created_at'); ?></th>
                                <th><?php echo Language::get('action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($plans)): ?>
                            <tr>
                                <td colspan="6" class="text-center"><?php echo Language::get('no_plans'); ?></td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($plans as $plan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($plan['plan_code']); ?></td>
                                    <td><?php echo htmlspecialchars($plan['description']); ?></td>
                                    <td>
                                        <?php if ($plan['item_count'] > 0): ?>
                                        <div class="progress" style="height: 20px;">
                                            <?php 
                                            $progress = ($plan['completed_count'] / $plan['item_count']) * 100;
                                            $progressClass = match(true) {
                                                $progress >= 100 => 'bg-success',
                                                $progress >= 50 => 'bg-info',
                                                default => 'bg-warning'
                                            };
                                            ?>
                                            <div class="progress-bar <?php echo $progressClass; ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $progress; ?>%"
                                                 aria-valuenow="<?php echo $progress; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?php echo number_format($progress, 0); ?>%
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted"><?php echo Language::get('no_items'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge status-<?php echo $plan['status']; ?>">
                                            <?php echo Language::get($plan['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($plan['created_at']); ?></td>
                                    <td>
                                        <a href="?page=production&view=<?php echo $plan['id']; ?>" 
                                           class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($canEdit && $plan['status'] === 'draft'): ?>
                                        <button type="button" 
                                                class="btn btn-danger btn-sm"
                                                onclick="deletePlan(<?php echo $plan['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
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

    <!-- Production Statistics -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo Language::get('statistics'); ?></h5>
            </div>
            <div class="card-body">
                <?php
                $stats = [
                    'draft' => 0,
                    'active' => 0,
                    'completed' => 0,
                    'cancelled' => 0
                ];
                
                foreach ($plans as $plan) {
                    $stats[$plan['status']]++;
                }
                ?>
                <div class="list-group">
                    <?php foreach ($stats as $status => $count): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo Language::get($status); ?>
                        <span class="badge bg-primary rounded-pill"><?php echo $count; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<!-- New Plan Modal -->
<div class="modal fade" id="newPlanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" id="newPlanForm">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo Language::get('new_production_plan'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('plan_type'); ?></label>
                        <select class="form-select" name="plan_type" id="planType" required>
                            <option value=""><?php echo Language::get('select_type'); ?></option>
                            <option value="1">Plan 1</option>
                            <option value="2">Plan 2</option>
                            <option value="3">Plan 3</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('plan_code'); ?></label>
                        <select class="form-select" name="plan_code" id="planCode" required>
                            <option value=""><?php echo Language::get('select_code'); ?></option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('description'); ?></label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo Language::get('items'); ?></label>
                        <div id="itemsContainer">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <select class="form-select" name="items[]" required>
                                        <option value=""><?php echo Language::get('select_item'); ?></option>
                                        <?php foreach ($finishedItems as $item): ?>
                                        <option value="<?php echo $item['id']; ?>">
                                            <?php echo htmlspecialchars($item['code'] . ' - ' . $item['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="quantities[]" 
                                           placeholder="<?php echo Language::get('quantity'); ?>"
                                           min="1" step="1" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-sm w-100" 
                                            onclick="removeItem(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addItem()">
                            <i class="fas fa-plus"></i> <?php echo Language::get('add_item'); ?>
                        </button>
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

<script>
// Plan type and code handling
const planCodes = <?php echo json_encode($planCodes); ?>;
const planType = document.getElementById('planType');
const planCode = document.getElementById('planCode');

planType.addEventListener('change', function() {
    planCode.innerHTML = '<option value=""><?php echo Language::get('select_code'); ?></option>';
    
    if (this.value) {
        planCodes[this.value].forEach(code => {
            const option = document.createElement('option');
            option.value = code;
            option.textContent = code;
            planCode.appendChild(option);
        });
    }
});

// Item management
function addItem() {
    const container = document.getElementById('itemsContainer');
    const row = document.createElement('div');
    row.className = 'row mb-2';
    row.innerHTML = `
        <div class="col-md-6">
            <select class="form-select" name="items[]" required>
                <option value=""><?php echo Language::get('select_item'); ?></option>
                <?php foreach ($finishedItems as $item): ?>
                <option value="<?php echo $item['id']; ?>">
                    <?php echo htmlspecialchars($item['code'] . ' - ' . $item['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <input type="number" class="form-control" name="quantities[]" 
                   placeholder="<?php echo Language::get('quantity'); ?>"
                   min="1" step="1" required>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger btn-sm w-100" 
                    onclick="removeItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
}

function removeItem(button) {
    const row = button.closest('.row');
    if (document.getElementById('itemsContainer').children.length > 1) {
        row.remove();
    }
}

// Plan deletion
function deletePlan(planId) {
    if (confirm('<?php echo Language::get('delete_confirm'); ?>')) {
        window.location.href = `?page=production&delete=${planId}`;
    }
}
</script>
<?php endif; ?>

<?php
// Handle plan deletion
if ($canEdit && isset($_GET['delete'])) {
    $planId = (int)$_GET['delete'];
    
    try {
        $db->beginTransaction();
        
        // Delete production items first
        $stmt = $db->prepare("DELETE FROM production_items WHERE plan_id = ?");
        $stmt->execute([$planId]);
        
        // Delete the plan
        $stmt = $db->prepare("DELETE FROM production_plans WHERE id = ? AND status = 'draft'");
        $stmt->execute([$planId]);
        
        $db->commit();
        setMessage('success', Language::get('delete_success'));
    } catch (Exception $e) {
        $db->rollBack();
        setMessage('error', Language::get('delete_failed'));
        error_log($e->getMessage());
    }
    
    redirect('index.php?page=production');
}

// Handle plan view
if (isset($_GET['view'])) {
    $planId = (int)$_GET['view'];
    
    // Get plan details
    $query = "SELECT p.*, 
                     pi.id as item_id,
                     pi.item_id as product_id,
                     pi.quantity,
                     pi.status as item_status,
                     i.code as product_code,
                     i.name as product_name
              FROM production_plans p
              LEFT JOIN production_items pi ON pi.plan_id = p.id
              LEFT JOIN items i ON i.id = pi.item_id
              WHERE p.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$planId]);
    $planDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($planDetails)):
        $plan = $planDetails[0];
    ?>
    <div class="card mt-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <?php echo Language::get('plan_details'); ?> - 
                    <?php echo htmlspecialchars($plan['plan_code']); ?>
                </h5>
                <span class="badge status-<?php echo $plan['status']; ?>">
                    <?php echo Language::get($plan['status']); ?>
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong><?php echo Language::get('description'); ?>:</strong><br>
                    <?php echo nl2br(htmlspecialchars($plan['description'])); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong><?php echo Language::get('created_at'); ?>:</strong><br>
                    <?php echo formatDateTime($plan['created_at']); ?></p>
                </div>
            </div>
            
            <h6><?php echo Language::get('production_items'); ?></h6>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?php echo Language::get('product'); ?></th>
                            <th><?php echo Language::get('quantity'); ?></th>
                            <th><?php echo Language::get('status'); ?></th>
                            <?php if ($canEdit && $plan['status'] === 'active'): ?>
                            <th><?php echo Language::get('action'); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($planDetails as $item): ?>
                        <?php if ($item['product_id']): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_code'] . ' - ' . $item['product_name']); ?></td>
                            <td><?php echo number_format($item['quantity']); ?></td>
                            <td>
                                <span class="badge status-<?php echo $item['item_status']; ?>">
                                    <?php echo Language::get($item['item_status']); ?>
                                </span>
                            </td>
                            <?php if ($canEdit && $plan['status'] === 'active'): ?>
                            <td>
                                <?php if ($item['item_status'] === 'pending'): ?>
                                <button type="button" 
                                        class="btn btn-success btn-sm"
                                        onclick="updateItemStatus(<?php echo $item['item_id']; ?>, 'completed')">
                                    <?php echo Language::get('mark_completed'); ?>
                                </button>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($canEdit): ?>
            <div class="mt-4">
                <?php if ($plan['status'] === 'draft'): ?>
                <button type="button" 
                        class="btn btn-success"
                        onclick="updatePlanStatus(<?php echo $plan['id']; ?>, 'active')">
                    <i class="fas fa-play"></i> <?php echo Language::get('start_production'); ?>
                </button>
                <?php elseif ($plan['status'] === 'active'): ?>
                <button type="button" 
                        class="btn btn-success"
                        onclick="updatePlanStatus(<?php echo $plan['id']; ?>, 'completed')">
                    <i class="fas fa-check"></i> <?php echo Language::get('complete_production'); ?>
                </button>
                <button type="button" 
                        class="btn btn-danger"
                        onclick="updatePlanStatus(<?php echo $plan['id']; ?>, 'cancelled')">
                    <i class="fas fa-times"></i> <?php echo Language::get('cancel_production'); ?>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canEdit): ?>
    <script>
    function updatePlanStatus(planId, status) {
        if (confirm('<?php echo Language::get('status_change_confirm'); ?>')) {
            window.location.href = `?page=production&view=${planId}&status=${status}`;
        }
    }

    function updateItemStatus(itemId, status) {
        if (confirm('<?php echo Language::get('item_status_change_confirm'); ?>')) {
            window.location.href = `?page=production&view=<?php echo $planId; ?>&item=${itemId}&status=${status}`;
        }
    }
    </script>
    <?php endif; ?>
    <?php
    endif;
}

// Handle status updates
if ($canEdit && isset($_GET['view'])) {
    $planId = (int)$_GET['view'];
    
    // Update plan status
    if (isset($_GET['status'])) {
        $newStatus = $_GET['status'];
        if (in_array($newStatus, ['active', 'completed', 'cancelled'])) {
            try {
                $stmt = $db->prepare("UPDATE production_plans SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $planId]);
                setMessage('success', Language::get('status_updated'));
            } catch (Exception $e) {
                setMessage('error', Language::get('update_failed'));
                error_log($e->getMessage());
            }
        }
    }
    
    // Update item status
    if (isset($_GET['item']) && isset($_GET['status'])) {
        $itemId = (int)$_GET['item'];
        $newStatus = $_GET['status'];
        if (in_array($newStatus, ['completed'])) {
            try {
                $stmt = $db->prepare("UPDATE production_items SET status = ? WHERE id = ? AND plan_id = ?");
                $stmt->execute([$newStatus, $itemId, $planId]);
                setMessage('success', Language::get('item_status_updated'));
            } catch (Exception $e) {
                setMessage('error', Language::get('update_failed'));
                error_log($e->getMessage());
            }
        }
    }
    
    redirect("index.php?page=production&view=$planId");
}
?>
