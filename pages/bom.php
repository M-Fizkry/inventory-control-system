<?php
// Check if user has edit access
$canEdit = checkAccess('bom', 'edit');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    try {
        $db->beginTransaction();
        
        $finishedItemId = (int)$_POST['finished_item_id'];
        $componentIds = $_POST['component_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $units = $_POST['unit'] ?? [];
        
        // Delete existing BOM entries for this finished item
        $stmt = $db->prepare("DELETE FROM bom WHERE finished_item_id = ?");
        $stmt->execute([$finishedItemId]);
        
        // Insert new BOM entries
        $stmt = $db->prepare("INSERT INTO bom (finished_item_id, component_item_id, quantity, unit) VALUES (?, ?, ?, ?)");
        
        foreach ($componentIds as $index => $componentId) {
            if (!empty($componentId) && !empty($quantities[$index])) {
                $stmt->execute([
                    $finishedItemId,
                    $componentId,
                    $quantities[$index],
                    $units[$index]
                ]);
            }
        }
        
        $db->commit();
        setMessage('success', Language::get('save_success'));
    } catch (Exception $e) {
        $db->rollBack();
        setMessage('error', Language::get('save_failed'));
        error_log($e->getMessage());
    }
    
    redirect('index.php?page=bom');
}

// Get all finished goods
$query = "SELECT id, code, name FROM items WHERE type = 'finished' ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$finishedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all potential components (raw materials and WIP)
$query = "SELECT id, code, name, type, unit FROM items WHERE type IN ('raw', 'wip') ORDER BY type, name";
$stmt = $db->prepare($query);
$stmt->execute();
$components = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected item's BOM if specified
$selectedItemId = $_GET['item_id'] ?? null;
$bomItems = [];

if ($selectedItemId) {
    $query = "SELECT 
                b.*,
                i.code as component_code,
                i.name as component_name,
                i.type as component_type
              FROM bom b
              JOIN items i ON i.id = b.component_item_id
              WHERE b.finished_item_id = ?
              ORDER BY i.type, i.name";
    $stmt = $db->prepare($query);
    $stmt->execute([$selectedItemId]);
    $bomItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><?php echo Language::get('bom'); ?></h5>
    </div>
    <div class="card-body">
        <!-- Item Selection -->
        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label"><?php echo Language::get('select_finished_item'); ?></label>
                <select class="form-select" id="itemSelector" onchange="window.location.href='?page=bom&item_id=' + this.value">
                    <option value=""><?php echo Language::get('select_item'); ?></option>
                    <?php foreach ($finishedItems as $item): ?>
                    <option value="<?php echo $item['id']; ?>" 
                            <?php echo $selectedItemId == $item['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($item['code'] . ' - ' . $item['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if ($selectedItemId): ?>
        <form method="POST" action="" id="bomForm">
            <input type="hidden" name="finished_item_id" value="<?php echo $selectedItemId; ?>">
            
            <div class="table-responsive">
                <table class="table table-hover" id="bomTable">
                    <thead>
                        <tr>
                            <th><?php echo Language::get('component'); ?></th>
                            <th><?php echo Language::get('quantity'); ?></th>
                            <th><?php echo Language::get('unit'); ?></th>
                            <?php if ($canEdit): ?>
                            <th width="50"><?php echo Language::get('action'); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bomItems)): ?>
                        <tr id="emptyRow">
                            <td colspan="<?php echo $canEdit ? 4 : 3; ?>" class="text-center">
                                <?php echo Language::get('no_components'); ?>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($bomItems as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($canEdit): ?>
                                    <select name="component_id[]" class="form-select" required>
                                        <option value=""><?php echo Language::get('select_component'); ?></option>
                                        <?php foreach ($components as $component): ?>
                                        <option value="<?php echo $component['id']; ?>"
                                                data-unit="<?php echo htmlspecialchars($component['unit']); ?>"
                                                <?php echo $component['id'] == $item['component_item_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($component['code'] . ' - ' . $component['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php else: ?>
                                    <?php echo htmlspecialchars($item['component_code'] . ' - ' . $item['component_name']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($canEdit): ?>
                                    <input type="number" name="quantity[]" class="form-control" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           step="0.01" min="0.01" required>
                                    <?php else: ?>
                                    <?php echo number_format($item['quantity'], 2); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($canEdit): ?>
                                    <input type="text" name="unit[]" class="form-control" 
                                           value="<?php echo htmlspecialchars($item['unit']); ?>" required>
                                    <?php else: ?>
                                    <?php echo htmlspecialchars($item['unit']); ?>
                                    <?php endif; ?>
                                </td>
                                <?php if ($canEdit): ?>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($canEdit): ?>
            <div class="mt-3">
                <button type="button" class="btn btn-secondary" onclick="addRow()">
                    <i class="fas fa-plus"></i> <?php echo Language::get('add_component'); ?>
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo Language::get('save'); ?>
                </button>
            </div>
            <?php endif; ?>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function addRow() {
    const tbody = document.querySelector('#bomTable tbody');
    const emptyRow = document.getElementById('emptyRow');
    if (emptyRow) emptyRow.remove();
    
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <select name="component_id[]" class="form-select" required>
                <option value=""><?php echo Language::get('select_component'); ?></option>
                <?php foreach ($components as $component): ?>
                <option value="<?php echo $component['id']; ?>" 
                        data-unit="<?php echo htmlspecialchars($component['unit']); ?>">
                    <?php echo htmlspecialchars($component['code'] . ' - ' . $component['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <input type="number" name="quantity[]" class="form-control" 
                   step="0.01" min="0.01" required>
        </td>
        <td>
            <input type="text" name="unit[]" class="form-control" required>
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
    
    // Set up component change handler for the new row
    const select = row.querySelector('select');
    select.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const unit = option.getAttribute('data-unit');
        if (unit) {
            const unitInput = this.closest('tr').querySelector('input[name="unit[]"]');
            unitInput.value = unit;
        }
    });
}

function removeRow(button) {
    const row = button.closest('tr');
    row.remove();
    
    // Show empty message if no rows left
    const tbody = document.querySelector('#bomTable tbody');
    if (tbody.children.length === 0) {
        tbody.innerHTML = `
            <tr id="emptyRow">
                <td colspan="4" class="text-center"><?php echo Language::get('no_components'); ?></td>
            </tr>
        `;
    }
}

// Set up component change handlers for existing rows
document.querySelectorAll('select[name="component_id[]"]').forEach(select => {
    select.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const unit = option.getAttribute('data-unit');
        if (unit) {
            const unitInput = this.closest('tr').querySelector('input[name="unit[]"]');
            unitInput.value = unit;
        }
    });
});
</script>
