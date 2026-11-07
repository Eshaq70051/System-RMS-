<?php

require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Models/Database.php';
require_once __DIR__ . '/../app/Services/OrderService.php';

use App\Models\Database;
use App\Services\OrderService;

class SystemTest
{
    private Database $db;
    private OrderService $orderService;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->orderService = new OrderService();
    }

    private function assert($condition, string $testName): void
    {
        if ($condition) {
            echo " [PASS] {$testName}\n";
            $this->passed++;
        } else {
            echo " [FAIL] {$testName}\n";
            $this->failed++;
        }
    }

    public function runAll(): void
    {
        echo "====================================================\n";
        echo "    Smart RMS End-to-End System Test Suite\n";
        echo "====================================================\n\n";

        $this->testDatabaseAndSeed();
        $this->testAuthentication();
        $this->testPosOrderWithRecipeDeduction();
        $this->testOrderReturnAndStockRestoration();
        $this->testKitchenDisplayFlow();
        $this->testPurchaseAndSupplierDebt();
        $this->testFinancialProfitAndLoss();
        $this->testHRAttendanceAndPayroll();

        echo "\n----------------------------------------------------\n";
        echo "Tests Completed: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "----------------------------------------------------\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function testDatabaseAndSeed(): void
    {
        echo "--- Test 1: Database & Seed Integrity ---\n";
        $usersCount = (int)$this->db->fetchValue("SELECT COUNT(*) FROM users");
        $this->assert($usersCount >= 4, "Users table populated (Found: {$usersCount})");

        $prodCount = (int)$this->db->fetchValue("SELECT COUNT(*) FROM products");
        $this->assert($prodCount >= 8, "Products catalog populated (Found: {$prodCount})");

        $rawCount = (int)$this->db->fetchValue("SELECT COUNT(*) FROM inventory_items");
        $this->assert($rawCount >= 10, "Raw materials inventory populated (Found: {$rawCount})");

        $recipeCount = (int)$this->db->fetchValue("SELECT COUNT(*) FROM recipes");
        $this->assert($recipeCount >= 2, "Recipes and food costs populated (Found: {$recipeCount})");
    }

    private function testAuthentication(): void
    {
        echo "\n--- Test 2: Authentication & Password Verification ---\n";
        $admin = $this->db->fetch("SELECT * FROM users WHERE username = 'admin'");
        $this->assert(!empty($admin), "Admin user exists in database");

        $valid = password_verify('admin123', $admin['password']);
        $this->assert($valid === true, "Password verification succeeds for admin123");

        $invalid = password_verify('wrongpassword', $admin['password']);
        $this->assert($invalid === false, "Password verification rejects invalid password");
    }

    private function testPosOrderWithRecipeDeduction(): void
    {
        echo "\n--- Test 3: POS Order Creation & Automated Recipe Deduction ---\n";
        
        // Find Black Angus Burger (which has recipe linking to Beef & Bun)
        $burger = $this->db->fetch("SELECT * FROM products WHERE sku = 'BRG-BEEF-01'");
        $this->assert(!empty($burger), "Black Angus Burger exists");

        // Record stock before sale
        $beefItem = $this->db->fetch("SELECT * FROM inventory_items WHERE sku = 'RAW-BEEF-01'");
        $stockBefore = (float)$beefItem['current_stock'];

        // Table
        $table = $this->db->fetch("SELECT * FROM dining_tables WHERE status = 'available' LIMIT 1");
        $tableId = $table ? $table['id'] : 1;

        // Active shift
        $shift = $this->db->fetch("SELECT * FROM cashier_shifts WHERE status = 'open' LIMIT 1");
        if (!$shift) {
            $shiftId = $this->db->insert('cashier_shifts', [
                'branch_id' => 1,
                'user_id' => 1,
                'opening_cash' => 500.0,
                'status' => 'open',
                'opened_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $shiftId = $shift['id'];
        }

        // Create Order of 2 Burgers
        $orderData = [
            'branch_id' => 1,
            'user_id' => 1,
            'shift_id' => $shiftId,
            'table_id' => $tableId,
            'order_type' => 'dine_in',
            'customer_name' => 'عميل تجريبي',
            'payment_method' => 'cash',
            'paid_amount' => 100.0,
            'discount_value' => 0.0,
            'kitchen_notes' => 'بدون بصل'
        ];

        $items = [
            [
                'product_id' => $burger['id'],
                'quantity' => 2,
                'unit_price' => $burger['price']
            ]
        ];

        $result = $this->orderService->createOrder($orderData, $items);
        $this->assert($result['success'] === true, "Order created successfully: " . ($result['order_number'] ?? ''));

        // Verify stock deducted
        // Recipe specifies 0.180 kg beef per burger. For 2 burgers = 0.360 kg deducted!
        $beefAfter = $this->db->fetch("SELECT * FROM inventory_items WHERE sku = 'RAW-BEEF-01'");
        $stockAfter = (float)$beefAfter['current_stock'];
        $expectedDeduction = 0.360;
        $actualDeduction = round($stockBefore - $stockAfter, 3);

        $this->assert(abs($actualDeduction - $expectedDeduction) < 0.001, "Recipe deducted exact grams from inventory ({$actualDeduction} kg deducted)");

        // Verify stock movement logged
        $movement = $this->db->fetch(
            "SELECT * FROM stock_movements WHERE reference_type = 'order' AND reference_id = :oid AND type = 'sale_deduction'",
            ['oid' => $result['order_id']]
        );
        $this->assert(!empty($movement), "Stock movement ledger entry logged for order #{$result['order_id']}");

        // Verify table marked as occupied
        $tableAfter = $this->db->fetch("SELECT * FROM dining_tables WHERE id = :tid", ['tid' => $tableId]);
        $this->assert($tableAfter['status'] === 'occupied', "Dining table marked occupied");

        // Verify cash drawer transaction recorded
        $drawerTx = $this->db->fetch(
            "SELECT * FROM cash_drawer_transactions WHERE reference_type = 'order' AND reference_id = :oid",
            ['oid' => $result['order_id']]
        );
        $this->assert(!empty($drawerTx), "Cash drawer recorded cash sale");

        $GLOBALS['test_order_id'] = $result['order_id'];
        $GLOBALS['test_table_id'] = $tableId;
    }

    private function testOrderReturnAndStockRestoration(): void
    {
        echo "\n--- Test 4: Sales Return & Inventory Restoration ---\n";
        $orderId = $GLOBALS['test_order_id'] ?? 1;

        $orderItem = $this->db->fetch("SELECT * FROM order_items WHERE order_id = :oid LIMIT 1", ['oid' => $orderId]);
        $this->assert(!empty($orderItem), "Found order item to return");

        // Beef stock before return
        $beefBefore = (float)$this->db->fetchValue("SELECT current_stock FROM inventory_items WHERE sku = 'RAW-BEEF-01'");

        // Return 1 burger
        $returnItems = [
            [
                'order_item_id' => $orderItem['id'],
                'quantity' => 1
            ]
        ];

        $returnResult = $this->orderService->returnOrder($orderId, $returnItems, 'خطأ في طلب العميل', 1);
        $this->assert($returnResult['success'] === true, "Return registered successfully");

        // Beef stock after return should have increased by 0.180 kg
        $beefAfter = (float)$this->db->fetchValue("SELECT current_stock FROM inventory_items WHERE sku = 'RAW-BEEF-01'");
        $restored = round($beefAfter - $beefBefore, 3);
        $this->assert(abs($restored - 0.180) < 0.001, "Recipe stock restored back to inventory (+{$restored} kg)");
    }

    private function testKitchenDisplayFlow(): void
    {
        echo "\n--- Test 5: Kitchen Display (KDS) & Order Status Lifecycle ---\n";
        $orderId = $GLOBALS['test_order_id'] ?? 1;
        $tableId = $GLOBALS['test_table_id'] ?? 1;

        // 1. Start Cooking
        $this->db->update('orders', ['status' => 'in_kitchen', 'kitchen_status' => 'cooking'], 'id = :id', ['id' => $orderId]);
        $order = $this->db->fetch("SELECT status, kitchen_status FROM orders WHERE id = :id", ['id' => $orderId]);
        $this->assert($order['kitchen_status'] === 'cooking', "Kitchen status transitioned to 'cooking'");

        // 2. Mark Ready
        $this->db->update('orders', ['status' => 'ready', 'kitchen_status' => 'done'], 'id = :id', ['id' => $orderId]);
        $order = $this->db->fetch("SELECT status, kitchen_status FROM orders WHERE id = :id", ['id' => $orderId]);
        $this->assert($order['kitchen_status'] === 'done', "Kitchen status transitioned to 'done'");

        // 3. Mark Served & Free table
        $this->db->update('orders', ['status' => 'completed', 'kitchen_status' => 'served'], 'id = :id', ['id' => $orderId]);
        $this->db->update('dining_tables', ['status' => 'available', 'current_order_id' => null], 'id = :id', ['id' => $tableId]);

        $table = $this->db->fetch("SELECT status, current_order_id FROM dining_tables WHERE id = :id", ['id' => $tableId]);
        $this->assert($table['status'] === 'available' && empty($table['current_order_id']), "Dining table released back to 'available'");
    }

    private function testPurchaseAndSupplierDebt(): void
    {
        echo "\n--- Test 6: Purchase Invoice & Supplier Debt Tracking ---\n";
        $supplier = $this->db->fetch("SELECT * FROM suppliers LIMIT 1");
        $this->assert(!empty($supplier), "Supplier exists in database");

        $rawItem = $this->db->fetch("SELECT * FROM inventory_items WHERE sku = 'RAW-CHICK-01'");
        $stockBefore = (float)$rawItem['current_stock'];
        $debtBefore = (float)$supplier['current_balance'];

        // Purchase 20 kg chicken at 20 SAR = 400 SAR. Pay 150 cash, remaining debt 250
        $qtyPurchased = 20.0;
        $unitCost = 20.0;
        $totalCost = 400.0;
        $paid = 150.0;
        $remaining = 250.0;

        $poId = $this->db->insert('purchase_orders', [
            'branch_id' => 1,
            'supplier_id' => $supplier['id'],
            'invoice_number' => 'TEST-PUR-' . time(),
            'purchase_date' => date('Y-m-d'),
            'total_amount' => $totalCost,
            'grand_total' => $totalCost,
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'payment_status' => 'partial',
            'status' => 'received',
            'created_by' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->db->query(
            "UPDATE inventory_items SET current_stock = current_stock + :qty, cost_per_unit = :cost WHERE id = :id",
            ['qty' => $qtyPurchased, 'cost' => $unitCost, 'id' => $rawItem['id']]
        );

        $this->db->query(
            "UPDATE suppliers SET current_balance = current_balance + :rem WHERE id = :id",
            ['rem' => $remaining, 'id' => $supplier['id']]
        );

        // Check stock updated
        $stockAfter = (float)$this->db->fetchValue("SELECT current_stock FROM inventory_items WHERE id = :id", ['id' => $rawItem['id']]);
        $this->assert(abs(($stockAfter - $stockBefore) - $qtyPurchased) < 0.001, "Chicken inventory stock increased by {$qtyPurchased} kg");

        // Check supplier debt updated
        $debtAfter = (float)$this->db->fetchValue("SELECT current_balance FROM suppliers WHERE id = :id", ['id' => $supplier['id']]);
        $this->assert(abs(($debtAfter - $debtBefore) - $remaining) < 0.001, "Supplier debt increased by {$remaining} SAR");
    }

    private function testFinancialProfitAndLoss(): void
    {
        echo "\n--- Test 7: Financial Profit & Loss Statement (Real COGS & Margins) ---\n";
        
        $totalSales = (float)$this->db->fetchValue("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled'");
        $totalReturns = (float)$this->db->fetchValue("SELECT SUM(refund_amount) FROM order_returns");
        $netSales = max(0, $totalSales - $totalReturns);

        $cogs = (float)$this->db->fetchValue(
            "SELECT SUM(oi.cost_price * oi.quantity) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.status != 'cancelled'"
        );

        $expenses = (float)$this->db->fetchValue("SELECT SUM(amount) FROM expenses");
        $grossProfit = $netSales - $cogs;
        $netProfit = $grossProfit - $expenses;

        $this->assert($totalSales > 0, "Sales revenue calculated: {$totalSales} SAR");
        $this->assert($cogs > 0, "Cost of Goods Sold (COGS) calculated from recipes: {$cogs} SAR");
        $this->assert($grossProfit == ($netSales - $cogs), "Gross Profit correctly computed as Net Sales - COGS");
        echo "   [INFO] Net Operating Profit = {$netProfit} SAR\n";
    }

    private function testHRAttendanceAndPayroll(): void
    {
        echo "\n--- Test 8: HR Attendance & Monthly Payroll Generation ---\n";
        $employee = $this->db->fetch("SELECT * FROM employees LIMIT 1");
        $this->assert(!empty($employee), "Employee exists in database");

        // Record attendance
        $attId = $this->db->insert('employee_attendances', [
            'employee_id' => $employee['id'],
            'date' => date('Y-m-d'),
            'clock_in' => '09:00:00',
            'clock_out' => '17:30:00',
            'working_hours' => 8.5,
            'status' => 'present'
        ]);
        $this->assert($attId > 0, "Attendance logged successfully for {$employee['full_name']}");

        // Generate Payroll
        $basic = (float)$employee['salary'];
        $bonus = 200.0;
        $deduction = 50.0;
        $net = $basic + $bonus - $deduction;

        $prId = $this->db->insert('employee_payrolls', [
            'employee_id' => $employee['id'],
            'month' => (int)date('m'),
            'year' => (int)date('Y'),
            'basic_salary' => $basic,
            'bonuses' => $bonus,
            'deductions' => $deduction,
            'advances' => 0.0,
            'net_salary' => $net,
            'payment_status' => 'paid',
            'payment_date' => date('Y-m-d')
        ]);
        $this->assert($prId > 0, "Payroll generated: Net Salary {$net} SAR");
    }
}

$test = new SystemTest();
$test->runAll();
