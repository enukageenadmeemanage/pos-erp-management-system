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
if (!$id) die("Invalid Sale ID.");

$pdo = getDBConnection();
$settings = load_settings($pdo);

// Fetch Sale
$stmt = $pdo->prepare("
    SELECT s.*, u.name as cashier_name 
    FROM sales s 
    LEFT JOIN users u ON s.cashier_id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) die("Sale not found.");

// Fetch Items
$stmt = $pdo->prepare("
    SELECT si.*, p.name as product_name 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    WHERE si.sale_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$store_name = $settings['store_name'] ?? 'FreshMart';
$store_address = $settings['store_address'] ?? '';
$store_phone = $settings['store_phone'] ?? '';
$receipt_footer = $settings['receipt_footer'] ?? 'Thank you!';
$currency = $settings['currency_symbol'] ?? '$';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= esc($sale['invoice_number']) ?></title>
    <style>
        @page { margin: 0; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 10px;
            width: 300px; /* Thermal printer width */
            margin: 0 auto;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        .divider-double { border-top: 2px dashed #000; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 2px 0; vertical-align: top; }
        .item-name { display: block; max-width: 150px; }
        
        .no-print { margin-top: 20px; text-align: center; }
        .btn { padding: 8px 15px; font-family: sans-serif; cursor: pointer; }
        
        @media print {
            .no-print { display: none; }
            body { width: 100%; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="text-center">
        <h2 style="margin: 0; font-size: 16px;"><?= esc($store_name) ?></h2>
        <?php if($store_address): ?><div><?= nl2br(esc($store_address)) ?></div><?php endif; ?>
        <?php if($store_phone): ?><div>Tel: <?= esc($store_phone) ?></div><?php endif; ?>
    </div>

    <div class="divider"></div>
    
    <div>
        <div>Bill No: <?= esc($sale['invoice_number']) ?></div>
        <div>Date: <?= date('d/m/Y H:i', strtotime($sale['sale_date'])) ?></div>
        <div>Cashier: <?= esc($sale['cashier_name']) ?></div>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Item</th>
                <th style="text-align: right;">Qty</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td>
                    <span class="item-name"><?= esc($item['product_name']) ?></span>
                    <small>@ <?= number_format($item['unit_price'], 2) ?></small>
                </td>
                <td class="text-right"><?= (float)$item['quantity'] ?></td>
                <td class="text-right"><?= number_format($item['line_total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <table style="font-weight: bold;">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right"><?= number_format($sale['subtotal'], 2) ?></td>
        </tr>
        <tr>
            <td>Tax:</td>
            <td class="text-right"><?= number_format($sale['tax_amount'], 2) ?></td>
        </tr>
        <?php if($sale['discount_amount'] > 0): ?>
        <tr>
            <td>Discount:</td>
            <td class="text-right">-<?= number_format($sale['discount_amount'], 2) ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <div class="divider-double"></div>

    <table style="font-size: 14px; font-weight: bold;">
        <tr>
            <td>GRAND TOTAL:</td>
            <td class="text-right"><?= $currency . ' ' . number_format($sale['grand_total'], 2) ?></td>
        </tr>
    </table>

    <div class="divider"></div>
    
    <table>
        <tr>
            <td>Paid (<?= ucfirst(esc($sale['payment_method'])) ?>):</td>
            <td class="text-right"><?= number_format($sale['amount_paid'], 2) ?></td>
        </tr>
        <tr>
            <td>Change:</td>
            <td class="text-right"><?= number_format($sale['balance_amount'], 2) ?></td>
        </tr>
    </table>

    <div class="divider"></div>
    
    <div class="text-center" style="margin-top: 10px;">
        <?= nl2br(esc($receipt_footer)) ?>
    </div>

    <!-- Print / Navigation Controls (Hidden during print) -->
    <div class="no-print">
        <button class="btn" onclick="window.print()">Print Receipt</button>
        <br><br>
        <a href="../pages/pos.php" style="text-decoration: none; color: blue; font-family: sans-serif;">&larr; Back to POS</a>
    </div>

</body>
</html>
