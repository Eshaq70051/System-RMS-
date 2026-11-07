<?php
/**
 * Script to clean operational and test transactional data
 * Leaves master catalog, products, recipes, users, roles, branches, categories intact.
 */

require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Models/Database.php';

use App\Models\Database;

echo "====================================================\n";
echo "    Clearing Operational & Test Demo Data           \n";
echo "====================================================\n\n";

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    $pdo->beginTransaction();

    // 1. Delete Orders & Returns
    echo "1. Deleting Orders, Order Items, and Order Returns...\n";
    $pdo->exec("DELETE FROM order_return_items");
    $pdo->exec("DELETE FROM order_returns");
    $pdo->exec("DELETE FROM order_items");
    $pdo->exec("DELETE FROM orders");

    // 2. Delete Shifts & Cash Drawer Transactions
    echo "2. Deleting Cashier Shifts and Cash Drawer Logs...\n";
    $pdo->exec("DELETE FROM cash_drawer_transactions");
    $pdo->exec("DELETE FROM cashier_shifts");

    // 3. Delete Expenses
    echo "3. Deleting Expenses...\n";
    $pdo->exec("DELETE FROM expenses");

    // 4. Delete Purchase Orders & Supplier Payments
    echo "4. Deleting Purchase Orders and Supplier Payments...\n";
    $pdo->exec("DELETE FROM purchase_order_items");
    $pdo->exec("DELETE FROM supplier_payments");
    $pdo->exec("DELETE FROM purchase_orders");

    // 5. Delete HR Attendance and Payrolls
    echo "5. Deleting Employee Attendances and Payroll records...\n";
    $pdo->exec("DELETE FROM employee_attendances");
    $pdo->exec("DELETE FROM employee_payrolls");

    // 6. Delete Stock Movements
    echo "6. Deleting Stock Movements ledger...\n";
    $pdo->exec("DELETE FROM stock_movements");

    // 7. Delete Audit Logs
    echo "7. Deleting Audit Logs...\n";
    $pdo->exec("DELETE FROM audit_logs");

    // 8. Reset Dining Tables status to 'available'
    echo "8. Resetting all Dining Tables status to 'available'...\n";
    $pdo->exec("UPDATE dining_tables SET status = 'available'");

    // 9. Reset Supplier debt balances to 0.00
    echo "9. Resetting Supplier balances to 0.00...\n";
    $pdo->exec("UPDATE suppliers SET current_balance = 0.00");

    // 10. Reset Inventory Items current_stock to initial base stock
    echo "10. Resetting Raw Material Inventory initial stock...\n";
    $initialStocks = [
        'RAW-BEEF-01' => 100.0,
        'RAW-CHICK-01' => 120.0,
        'RAW-BUN-01' => 350.0,
        'RAW-CHED-01' => 300.0,
        'RAW-POT-01' => 100.0,
        'RAW-OIL-01' => 80.0,
        'RAW-VEG-01' => 50.0,
        'RAW-DOUGH-01' => 60.0,
        'RAW-MOZZ-01' => 40.0,
        'RAW-RICE-01' => 150.0,
        'RAW-SAUCE-01' => 35.0,
        'RAW-PKG-01' => 700.0,
        'RAW-COLA-01' => 200.0
    ];
    $stmtStock = $pdo->prepare("UPDATE inventory_items SET current_stock = :stock WHERE sku = :sku");
    foreach ($initialStocks as $sku => $stock) {
        $stmtStock->execute(['stock' => $stock, 'sku' => $sku]);
    }

    // 11. Reset SQLite Auto-Increment Sequences
    echo "11. Resetting SQLite Auto-Increment counters...\n";
    $resetTables = [
        'orders',
        'order_items',
        'order_returns',
        'order_return_items',
        'cashier_shifts',
        'cash_drawer_transactions',
        'expenses',
        'purchase_orders',
        'purchase_order_items',
        'supplier_payments',
        'employee_payrolls',
        'employee_attendances',
        'stock_movements',
        'audit_logs'
    ];
    $inClause = "'" . implode("', '", $resetTables) . "'";
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name IN ({$inClause})");

    $pdo->commit();
    echo "\n[SUCCESS] All test operational and transaction data cleared successfully!\n";

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "[ERROR] Failed to clear data: " . $e->getMessage() . "\n";
    exit(1);
}
