<?php
// Get stock statistics
$query = "SELECT 
            type,
            COUNT(*) as total_items,
            SUM(CASE WHEN current_stock <= min_stock THEN 1 ELSE 0 END) as low_stock,
            SUM(CASE WHEN current_stock >= max_stock THEN 1 ELSE 0 END) as high_stock
          FROM items 
          GROUP BY type";
$stmt = $db->prepare($query);
$stmt->execute();
$stockStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all items for the stock table
$query = "SELECT 
            i.*, 
            CASE 
                WHEN i.current_stock <= i.min_stock THEN 'low'
                WHEN i.current_stock >= i.max_stock THEN 'high'
                ELSE 'normal'
            END as stock_status
          FROM items i
          ORDER BY i.type, i.name";
$stmt = $db->prepare($query);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for charts
$labels = [];
$currentStock = [];
$minStock = [];
$maxStock = [];

foreach ($items as $item) {
    $labels[] = $item['name'];
    $currentStock[] = $item['current_stock'];
    $minStock[] = $item['min_stock'];
    $maxStock[] = $item['max_stock'];
}
?>

<!-- Stock Overview Cards -->
<div class="row mb-4">
    <?php foreach ($stockStats as $stat): ?>
    <div class="col-md-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2"><?php echo Language::get($stat['type']); ?></h6>
                        <h3 class="mb-0"><?php echo $stat['total_items']; ?></h3>
                    </div>
                    <div class="icon">
                        <?php
                        $icon = match($stat['type']) {
                            'raw' => 'box',
                            'wip' => 'clock',
                            'finished' => 'check-square',
                            default => 'box'
                        };
                        ?>
                        <i class="fas fa-<?php echo $icon; ?>"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <?php if ($stat['low_stock'] > 0): ?>
                    <span class="badge bg-danger me-2">
                        <?php echo $stat['low_stock']; ?> <?php echo Language::get('low_stock'); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($stat['high_stock'] > 0): ?>
                    <span class="badge bg-warning">
                        <?php echo $stat['high_stock']; ?> <?php echo Language::get('high_stock'); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Stock Level Chart -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><?php echo Language::get('stock_status'); ?></h5>
    </div>
    <div class="card-body">
        <div class="chart-container">
            <canvas id="stockLevelChart"
                    data-labels='<?php echo json_encode($labels); ?>'
                    data-current='<?php echo json_encode($currentStock); ?>'
                    data-min='<?php echo json_encode($minStock); ?>'
                    data-max='<?php echo json_encode($maxStock); ?>'>
            </canvas>
        </div>
    </div>
</div>

<!-- Stock Table -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><?php echo Language::get('current_stock'); ?></h5>
            <div class="d-flex gap-2">
                <input type="text" 
                       class="form-control form-control-sm" 
                       id="stockSearch" 
                       placeholder="<?php echo Language::get('search'); ?>..."
                       style="width: 200px;">
                <button class="btn btn-sm btn-outline-primary" 
                        onclick="exportTableToCSV('stockTable', 'stock-report.csv')">
                    <i class="fas fa-download"></i> <?php echo Language::get('export'); ?>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="stockTable">
                <thead>
                    <tr>
                        <th><?php echo Language::get('item_code'); ?></th>
                        <th><?php echo Language::get('item_name'); ?></th>
                        <th><?php echo Language::get('item_type'); ?></th>
                        <th><?php echo Language::get('current_stock'); ?></th>
                        <th><?php echo Language::get('min_stock'); ?></th>
                        <th><?php echo Language::get('max_stock'); ?></th>
                        <th><?php echo Language::get('unit'); ?></th>
                        <th><?php echo Language::get('status'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['code']); ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo Language::get($item['type']); ?></td>
                        <td class="text-end"><?php echo number_format($item['current_stock'], 2); ?></td>
                        <td class="text-end"><?php echo number_format($item['min_stock'], 2); ?></td>
                        <td class="text-end"><?php echo number_format($item['max_stock'], 2); ?></td>
                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                        <td>
                            <span class="badge stock-<?php echo $item['stock_status']; ?>">
                                <?php 
                                echo match($item['stock_status']) {
                                    'low' => Language::get('low_stock'),
                                    'high' => Language::get('high_stock'),
                                    default => Language::get('normal')
                                };
                                ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    filterTable('stockSearch', 'stockTable');
});
</script>
