<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized.");
}

$id = $_GET['id'] ?? 0;
if (!$id) die("Invalid Payroll ID.");

$pdo = getDBConnection();
$settings = load_settings($pdo);

// Fetch Payroll & Staff
$stmt = $pdo->prepare("
    SELECT p.*, s.name as staff_name, s.job_title, s.salary_type 
    FROM payroll p 
    JOIN staff s ON p.staff_id = s.id 
    WHERE p.id = ?
");
$stmt->execute([$id]);
$pay = $stmt->fetch();

if (!$pay) die("Payroll record not found.");

$store_name = $settings['store_name'] ?? 'FreshMart';
$store_address = $settings['store_address'] ?? '';
$currency = $settings['currency_symbol'] ?? '$';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - <?= esc($pay['staff_name']) ?></title>
    <style>
        body { font-family: sans-serif; background: #f3f4f6; padding: 20px; }
        .payslip-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #10B981; }
        .header p { margin: 5px 0 0 0; color: #666; }
        .title { text-align: center; font-size: 1.2rem; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; font-size: 0.9rem; }
        .info-box { background: #f9f9f9; padding: 10px; border-radius: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; font-size: 0.9rem; }
        th { background: #f9f9f9; }
        .text-right { text-align: right; }
        .amount { font-weight: bold; }
        
        .net-pay { text-align: right; font-size: 1.25rem; font-weight: bold; background: #f0fdf4; padding: 15px; border: 1px solid #10B981; color: #065F46; }
        
        .footer { margin-top: 30px; font-size: 0.8rem; color: #666; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
        
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn { padding: 8px 15px; background: #10B981; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block;}
        
        @media print {
            body { background: white; padding: 0; }
            .payslip-container { box-shadow: none; padding: 0; max-width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn" onclick="window.print()">Print Payslip</button>
        <a href="../pages/payroll.php" class="btn" style="background: #6B7280; margin-left: 10px;">Back</a>
    </div>

    <div class="payslip-container">
        <div class="header">
            <h1><?= esc($store_name) ?></h1>
            <p><?= esc($store_address) ?></p>
        </div>
        
        <div class="title">Payslip for <?= date('F Y', mktime(0,0,0,$pay['month'], 1, $pay['year'])) ?></div>
        
        <div class="info-grid">
            <div class="info-box">
                <strong>Employee Name:</strong> <?= esc($pay['staff_name']) ?><br>
                <strong>Designation:</strong> <?= esc($pay['job_title']) ?><br>
                <strong>Salary Type:</strong> <span style="text-transform: capitalize;"><?= esc($pay['salary_type']) ?></span>
            </div>
            <div class="info-box text-right">
                <strong>Payment Status:</strong> <?= strtoupper(esc($pay['payment_status'])) ?><br>
                <?php if($pay['payment_status'] === 'paid'): ?>
                <strong>Payment Date:</strong> <?= date('d M Y', strtotime($pay['payment_date'])) ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="info-grid" style="grid-template-columns: repeat(4, 1fr); text-align: center; background: #f9f9f9; padding: 10px;">
            <div><strong>Working Days</strong><br><?= $pay['working_days'] ?></div>
            <div><strong>Present</strong><br><?= $pay['present_days'] ?></div>
            <div><strong>Absent</strong><br><?= $pay['absent_days'] ?></div>
            <div><strong>Overtime Hrs</strong><br><?= $pay['overtime_hours'] ?></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Earnings</th>
                    <th class="text-right">Amount (<?= esc($currency) ?>)</th>
                    <th>Deductions</th>
                    <th class="text-right">Amount (<?= esc($currency) ?>)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary</td>
                    <td class="text-right amount"><?= number_format($pay['basic_salary'], 2) ?></td>
                    <td>Absence Deduction</td>
                    <!-- Calculate absence deduction = Total Deductions - Advance - Other Deductions -->
                    <td class="text-right amount"><?= number_format($pay['total_deductions'] - $pay['advance'] - $pay['other_deductions'], 2) ?></td>
                </tr>
                <tr>
                    <td>Overtime Pay</td>
                    <td class="text-right amount"><?= number_format($pay['overtime_amount'], 2) ?></td>
                    <td>Advance</td>
                    <td class="text-right amount"><?= number_format($pay['advance'], 2) ?></td>
                </tr>
                <tr>
                    <td>Bonus</td>
                    <td class="text-right amount"><?= number_format($pay['bonus'], 2) ?></td>
                    <td>Other Deductions</td>
                    <td class="text-right amount"><?= number_format($pay['other_deductions'], 2) ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold; background: #f9f9f9;">Gross Earnings</td>
                    <td class="text-right amount" style="background: #f9f9f9;"><?= number_format($pay['gross_salary'], 2) ?></td>
                    <td style="font-weight: bold; background: #f9f9f9;">Total Deductions</td>
                    <td class="text-right amount" style="background: #f9f9f9;"><?= number_format($pay['total_deductions'], 2) ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="net-pay">
            NET SALARY: <?= esc($currency) ?> <?= number_format($pay['net_salary'], 2) ?>
        </div>
        
        <div class="footer">
            This is a computer-generated document. No signature is required.
            <br>
            Generated on: <?= date('d M Y H:i:s') ?>
        </div>
    </div>
</body>
</html>
