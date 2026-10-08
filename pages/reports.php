<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission(['admin', 'manager', 'accountant']);

$page_title = 'Financial Reports';

// Filters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month

// Fetch Sales Summary
$stmt = $pdo->prepare("
    SELECT 
        COUNT(id) as total_sales,
        SUM(subtotal) as total_subtotal,
        SUM(tax_amount) as total_tax,
        SUM(discount_amount) as total_discount,
        SUM(grand_total) as total_revenue
    FROM sales 
    WHERE DATE(sale_date) >= ? AND DATE(sale_date) <= ?
");
$stmt->execute([$start_date, $end_date]);
$sales = $stmt->fetch();

// Fetch Purchases Summary
$stmt = $pdo->prepare("
    SELECT 
        COUNT(id) as total_purchases,
        SUM(total_amount) as total_expense
    FROM purchases 
    WHERE DATE(purchase_date) >= ? AND DATE(purchase_date) <= ?
");
$stmt->execute([$start_date, $end_date]);
$purchases = $stmt->fetch();

// Calculate Profit (Revenue from items - Cost of those items)
// We use the purchase_price_snapshot stored during the sale to calculate accurate profit
$stmt = $pdo->prepare("
    SELECT 
        SUM((si.unit_price - si.purchase_price_snapshot) * si.quantity) as gross_profit
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    WHERE DATE(s.sale_date) >= ? AND DATE(s.sale_date) <= ?
");
$stmt->execute([$start_date, $end_date]);
$profit_data = $stmt->fetch();
$gross_profit = $profit_data['gross_profit'] ?? 0;

// Deduct discounts from gross profit
$net_profit = $gross_profit - ($sales['total_discount'] ?? 0);

// Fetch daily sales for chart
$stmt = $pdo->prepare("
    SELECT 
        DATE(sale_date) as date,
        SUM(grand_total) as daily_revenue
    FROM sales 
    WHERE DATE(sale_date) >= ? AND DATE(sale_date) <= ?
    GROUP BY DATE(sale_date)
    ORDER BY DATE(sale_date) ASC
");
$stmt->execute([$start_date, $end_date]);
$chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chart_labels = [];
$chart_revenues = [];
foreach ($chart_data as $row) {
    $chart_labels[] = date('M d', strtotime($row['date']));
    $chart_revenues[] = (float)$row['daily_revenue'];
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" style="display: flex; gap: 1rem; align-items: flex-end;">
            <div class="form-group mb-0">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= esc($start_date) ?>" required>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= esc($end_date) ?>" required>
            </div>
            <div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-title">Total Revenue</div>
        <div class="stat-value text-primary"><?= format_currency($sales['total_revenue'] ?? 0) ?></div>
        <div class="stat-desc"><?= number_format($sales['total_sales']) ?> Sales Invoices</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-title">Total Purchases (Expenses)</div>
        <div class="stat-value text-danger"><?= format_currency($purchases['total_expense'] ?? 0) ?></div>
        <div class="stat-desc"><?= number_format($purchases['total_purchases']) ?> Purchase Invoices</div>
    </div>

    <div class="stat-card">
        <div class="stat-title">Gross Profit (Before Discounts)</div>
        <div class="stat-value text-info"><?= format_currency($gross_profit) ?></div>
        <div class="stat-desc">Based on item cost vs selling price</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-title">Net Profit (After Discounts)</div>
        <div class="stat-value text-success"><?= format_currency($net_profit) ?></div>
        <div class="stat-desc">Actual realized profit</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Sales Breakdown -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Sales Breakdown</h3>
        </div>
        <div class="card-body">
            <table class="table w-100" style="width: 100%;">
                <tr>
                    <td style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">Subtotal</td>
                    <td style="padding: 0.5rem 0; border-bottom: 1px solid #eee; text-align: right; font-weight: bold;"><?= format_currency($sales['total_subtotal'] ?? 0) ?></td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">Tax Collected</td>
                    <td style="padding: 0.5rem 0; border-bottom: 1px solid #eee; text-align: right; font-weight: bold; color: var(--info);"><?= format_currency($sales['total_tax'] ?? 0) ?></td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">Discounts Given</td>
                    <td style="padding: 0.5rem 0; border-bottom: 1px solid #eee; text-align: right; font-weight: bold; color: var(--danger);">-<?= format_currency($sales['total_discount'] ?? 0) ?></td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0; font-weight: bold; font-size: 1.1rem;">Total Revenue</td>
                    <td style="padding: 0.5rem 0; text-align: right; font-weight: bold; font-size: 1.1rem; color: var(--primary);"><?= format_currency($sales['total_revenue'] ?? 0) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daily Revenue Trend</h3>
        </div>
        <div class="card-body" style="position: relative; height: 250px;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    const labels = <?= json_encode($chart_labels) ?>;
    const data = <?= json_encode($chart_revenues) ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Daily Revenue',
                data: data,
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#10B981',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '<?= $settings['currency_symbol'] ?? '$' ?>' + value;
                        }
                    }
                }
            }
        }
    });
});
</script>

<div class="text-center mt-4">
    <button class="btn btn-outline-primary" onclick="window.print()"><i class="fas fa-print"></i> Print Report Page</button>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
