<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Dashboard';

// Fetch quick stats
// 1. Today's Sales
$stmt = $pdo->prepare("SELECT COALESCE(SUM(grand_total), 0) FROM sales WHERE DATE(sale_date) = CURDATE() AND status = 'completed'");
$stmt->execute();
$today_sales = $stmt->fetchColumn();

// 2. This Month's Sales
$stmt = $pdo->prepare("SELECT COALESCE(SUM(grand_total), 0) FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE()) AND status = 'completed'");
$stmt->execute();
$month_sales = $stmt->fetchColumn();

// 3. Number of Products
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
$product_count = $stmt->fetchColumn();

// 4. Low-stock Items
$low_stock_threshold = $settings['low_stock_threshold'] ?? 10;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE stock_quantity <= reorder_level AND status = 'active'");
$stmt->execute();
$low_stock_count = $stmt->fetchColumn();

// 5. Total Staff
$stmt = $pdo->query("SELECT COUNT(*) FROM staff WHERE status = 'active'");
$staff_count = $stmt->fetchColumn();

// 6. Recent Bills
$stmt = $pdo->query("SELECT invoice_number, grand_total, payment_method, sale_date FROM sales ORDER BY id DESC LIMIT 5");
$recent_bills = $stmt->fetchAll();

// Chart Data (Last 7 Days Sales)
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('D, M d', strtotime($date));
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(grand_total), 0) FROM sales WHERE DATE(sale_date) = ? AND status = 'completed'");
    $stmt->execute([$date]);
    $chart_data[] = (float)$stmt->fetchColumn();
}

include_once __DIR__ . '/../includes/header.php';
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    <!-- Today's Sales -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body">
            <h3 style="color: var(--secondary); font-size: 0.875rem; margin-bottom: 0.5rem; font-weight: 500;">Today's Sales</h3>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);"><?= format_currency($today_sales) ?></div>
        </div>
    </div>
    
    <!-- This Month Sales -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body">
            <h3 style="color: var(--secondary); font-size: 0.875rem; margin-bottom: 0.5rem; font-weight: 500;">This Month</h3>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--info);"><?= format_currency($month_sales) ?></div>
        </div>
    </div>

    <!-- Products -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body">
            <h3 style="color: var(--secondary); font-size: 0.875rem; margin-bottom: 0.5rem; font-weight: 500;">Total Products</h3>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--dark);"><?= esc($product_count) ?></div>
        </div>
    </div>

    <!-- Low Stock -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body">
            <h3 style="color: var(--secondary); font-size: 0.875rem; margin-bottom: 0.5rem; font-weight: 500;">Low Stock Items</h3>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--danger);"><?= esc($low_stock_count) ?></div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Sales (Last 7 Days)</h3>
        </div>
        <div class="card-body">
            <canvas id="salesChart" height="100"></canvas>
        </div>
    </div>

    <!-- Recent Bills -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Bills</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if(empty($recent_bills)): ?>
                <div style="padding: 1.5rem; color: var(--secondary);">No recent bills.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_bills as $bill): ?>
                            <tr>
                                <td><?= esc($bill['invoice_number']) ?></td>
                                <td style="font-weight: 600;"><?= format_currency($bill['grand_total']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('salesChart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Sales Amount',
                data: <?= json_encode($chart_data) ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
