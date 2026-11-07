<?php
/**
 * Cashier Shift & Daily Performance Report Test
 */

require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Models/Database.php';
require_once __DIR__ . '/../app/Services/OrderService.php';

use App\Models\Database;
use App\Services\OrderService;

echo "====================================================\n";
echo "    Cashier Shift & Daily Performance Report Test   \n";
echo "====================================================\n\n";

$db = Database::getInstance();
$orderService = new OrderService();

// 1. Get Cashier user
$cashier = $db->fetch("SELECT u.*, r.slug as role FROM users u JOIN roles r ON u.role_id = r.id WHERE r.slug = 'cashier' LIMIT 1");
if (!$cashier) {
    die("Cashier not found\n");
}
echo "[PASS] Cashier found: {$cashier['name']} (ID: {$cashier['id']})\n";

// Set session
$_SESSION['user_id'] = $cashier['id'];
$_SESSION['user_role'] = $cashier['role'];
$_SESSION['user_name'] = $cashier['name'];
$_SESSION['branch_id'] = $cashier['branch_id'] ?? 1;

// 2. Open Shift with 500 SAR
$openingCash = 500.00;
$db->query("UPDATE cashier_shifts SET status = 'closed' WHERE user_id = :uid AND status = 'open'", ['uid' => $cashier['id']]);

$shiftId = $db->insert('cashier_shifts', [
    'user_id' => $cashier['id'],
    'branch_id' => $cashier['branch_id'] ?? 1,
    'opening_cash' => $openingCash,
    'status' => 'open',
    'opened_at' => date('Y-m-d H:i:s', time() - 3600 * 3)
]);
echo "[PASS] Shift #{$shiftId} opened with {$openingCash} SAR (3 hours ago)\n";

// 3. Create Cash Order (120 SAR)
$product = $db->fetch("SELECT * FROM products WHERE is_active = 1 LIMIT 1");
$order1 = $orderService->createOrder([
    'branch_id' => 1,
    'user_id' => $cashier['id'],
    'shift_id' => $shiftId,
    'order_type' => 'dine_in',
    'customer_name' => 'عميل تجريبي 1',
    'payment_method' => 'cash',
    'paid_amount' => 120.0,
    'discount_value' => 0
], [
    [
        'product_id' => $product['id'],
        'quantity' => 2,
        'unit_price' => 60.0,
        'cost_price' => 20.0
    ]
]);
echo "[PASS] Cash order created: {$order1['order_number']} (Total: {$order1['grand_total']} SAR)\n";

// 4. Create Card/Transfer Order (80 SAR)
$order2 = $orderService->createOrder([
    'branch_id' => 1,
    'user_id' => $cashier['id'],
    'shift_id' => $shiftId,
    'order_type' => 'takeaway',
    'customer_name' => 'عميل تجريبي 2',
    'payment_method' => 'card',
    'paid_amount' => 80.0,
    'discount_value' => 0
], [
    [
        'product_id' => $product['id'],
        'quantity' => 1,
        'unit_price' => 80.0,
        'cost_price' => 30.0
    ]
]);
echo "[PASS] Card order created: {$order2['order_number']} (Total: {$order2['grand_total']} SAR)\n";

// 5. Query shift metrics directly to test the calculation logic
$shiftData = $db->fetch("SELECT * FROM cashier_shifts WHERE id = :id", ['id' => $shiftId]);

$totalSales = (float)$shiftData['total_sales'];
$cashSales = (float)$shiftData['total_cash_sales'];
$cardSales = (float)$shiftData['total_card_sales'];
$returns = (float)$shiftData['total_returns'];
$expectedCashInDrawer = $openingCash + $cashSales - $returns;

assert($totalSales == 200.0, "Total sales should be 200");
assert($cashSales == 120.0, "Cash sales should be 120");
assert($cardSales == 80.0, "Card sales should be 80");
assert($expectedCashInDrawer == 620.0, "Expected cash in drawer should be 500 + 120 = 620");

echo "[PASS] Total sales: {$totalSales} SAR (Cash: {$cashSales} SAR, Card/Transfer: {$cardSales} SAR)\n";
echo "[PASS] Expected Cash in Drawer: {$expectedCashInDrawer} SAR (Opening {$openingCash} + Cash {$cashSales} - Returns {$returns})\n";

// 6. Test Shortage (العجز) when closing with 600 SAR instead of 620 SAR
$closingCashActual = 600.00;
$difference = $closingCashActual - $expectedCashInDrawer; // -20 SAR (Shortage / عجز)

$db->query(
    "UPDATE cashier_shifts 
     SET closing_cash_actual = :actual, cash_difference = :diff, status = 'closed', closed_at = datetime('now') 
     WHERE id = :id",
    [
        'actual' => $closingCashActual,
        'diff' => $difference,
        'id' => $shiftId
    ]
);

assert($difference == -20.0, "Difference must be -20 (shortage)");
echo "[PASS] Shift closed with {$closingCashActual} SAR. Cash shortage (عجز) accurately calculated: " . abs($difference) . " SAR\n";

// 7. Verify print report template renders without error
ob_start();
$shift = $db->fetch(
    "SELECT cs.*, u.name as cashier_name, b.name as branch_name 
     FROM cashier_shifts cs 
     JOIN users u ON cs.user_id = u.id 
     JOIN branches b ON cs.branch_id = b.id 
     WHERE cs.id = :id",
    ['id' => $shiftId]
);
$startTs = strtotime($shift['opened_at']);
$endTs = strtotime($shift['closed_at']);
$diffSeconds = max(0, $endTs - $startTs);
$hoursWorked = floor($diffSeconds / 3600);
$minutesWorked = floor(($diffSeconds % 3600) / 60);

$orders = $db->fetchAll(
    "SELECT order_number, payment_method, total_amount, created_at 
     FROM orders 
     WHERE shift_id = :sid AND status != 'cancelled' 
     ORDER BY id DESC",
    ['sid' => $shift['id']]
);
$expectedCash = $shift['opening_cash'] + $shift['total_cash_sales'] - $shift['total_returns'];
$currencySymbol = 'ر.س';

require __DIR__ . '/../views/pos/shift_report_print.php';
$html = ob_get_clean();

assert(strpos($html, 'سامي أحمد') !== false, "Print report must contain cashier name");
assert(strpos($html, 'عجز بالصندوق') !== false, "Print report must show shortage note");
assert(strpos($html, '20.00') !== false, "Print report must show 20.00 shortage amount");
assert(strpos($html, '120.00') !== false, "Print report must show cash sales");
assert(strpos($html, '80.00') !== false, "Print report must show card sales");
echo "[PASS] Thermal Print Z-Report HTML template verified with all metrics and shortage badge!\n";

echo "\n----------------------------------------------------\n";
echo "All 7 Cashier Shift Report Tests Passed Successfully!\n";
echo "----------------------------------------------------\n";
