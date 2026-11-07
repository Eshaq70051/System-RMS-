<?php
/**
 * Test POS Receipt Printing & PDF options
 */

require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Models/Database.php';

use App\Models\Database;

echo "====================================================\n";
echo "    POS Receipt Auto-Print & PDF Test               \n";
echo "====================================================\n\n";

$db = Database::getInstance();

// 1. Verify pos/index.php does not have container.appendChild(emptyState)
$posViewContent = file_get_contents(__DIR__ . '/../views/pos/index.php');
assert(strpos($posViewContent, 'container.appendChild(emptyState)') === false, "Old broken appendChild(emptyState) must be completely eliminated");
echo "[PASS] 'appendChild' on cartEmptyState bug completely eliminated!\n";

// 2. Verify receipt view includes auto-print and Save as PDF options
$dummyOrder = [
    'id' => 999,
    'order_number' => 'ORD-TEST-0001',
    'branch_id' => 1,
    'branch_name' => 'الفرع الرئيسي',
    'created_at' => date('Y-m-d H:i:s'),
    'total_amount' => 150.00,
    'tax_amount' => 19.57,
    'paid_amount' => 150.00,
    'change_amount' => 0.00,
    'payment_method' => 'cash'
];
$order = $dummyOrder;
$items = [
    [
        'product_name' => 'وجبة تجريبية',
        'quantity' => 2,
        'unit_price' => 75.00,
        'total_price' => 150.00
    ]
];

ob_start();
require __DIR__ . '/../views/pos/receipt.php';
$receiptHtml = ob_get_clean();

assert(strpos($receiptHtml, 'طباعة الفاتورة') !== false, "Receipt must contain print button");
assert(strpos($receiptHtml, 'حفظ كـ PDF') !== false, "Receipt must contain Save as PDF option");
assert(strpos($receiptHtml, 'Save as PDF') !== false, "Receipt must contain Save as PDF guide");
assert(strpos($receiptHtml, 'autoprint') !== false, "Receipt must contain autoprint trigger");
assert(strpos($receiptHtml, '80mm') !== false, "Receipt must be formatted for 80mm thermal paper");
echo "[PASS] Thermal Receipt View verified: Auto-Print, PDF fallback, and 80mm sizing!\n";

// 3. Verify shift report print also includes PDF fallback
$shift = [
    'id' => 1,
    'opened_at' => date('Y-m-d H:i:s', time() - 3600),
    'closed_at' => date('Y-m-d H:i:s'),
    'status' => 'closed',
    'cashier_name' => 'الكاشير',
    'opening_cash' => 500,
    'total_sales' => 150,
    'total_cash_sales' => 150,
    'total_card_sales' => 0,
    'total_returns' => 0,
    'closing_cash_actual' => 650,
    'cash_difference' => 0,
    'notes' => ''
];
$orders = [];
$hoursWorked = 1;
$minutesWorked = 0;
$currencySymbol = 'ر.س';

ob_start();
require __DIR__ . '/../views/pos/shift_report_print.php';
$shiftHtml = ob_get_clean();

assert(strpos($shiftHtml, 'طباعة التقرير') !== false, "Shift report must contain print button");
assert(strpos($shiftHtml, 'حفظ كـ PDF') !== false, "Shift report must contain PDF option");
assert(strpos($shiftHtml, 'autoprint') !== false, "Shift report must contain autoprint trigger");
echo "[PASS] Shift Report Print View verified: Auto-Print and PDF fallback!\n";

echo "\n----------------------------------------------------\n";
echo "All Receipt & Printing Tests Passed Successfully!\n";
echo "----------------------------------------------------\n";
