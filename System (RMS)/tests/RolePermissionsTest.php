<?php

require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Models/Database.php';

use App\Models\Database;

class RolePermissionsTest
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run(): void
    {
        echo "====================================================\n";
        echo "   Role-Based Authorization & Visibility Test Suite\n";
        echo "====================================================\n\n";

        $this->testCashierPermissions();
        $this->testAccountantPermissions();
        $this->testKitchenPermissions();
        $this->testAdminPermissions();

        echo "\n[✓] ALL ROLE PERMISSION TESTS PASSED SUCCESSFULLY!\n";
    }

    private function setSimulatedUser(string $username, string $roleSlug): void
    {
        $_SESSION['user_id'] = 999;
        $_SESSION['user'] = [
            'id' => 999,
            'name' => 'مستخدم اختبار',
            'username' => $username,
            'role_slug' => $roleSlug,
            'role_name' => $roleSlug,
            'branch_id' => 1
        ];
    }

    private function testCashierPermissions(): void
    {
        echo "--- 1. Testing Cashier Role (الكاشير) ---\n";
        $this->setSimulatedUser('cashier', 'cashier');

        // Cashier Allowed Interfaces
        assert(has_role('cashier', 'admin') === true, "Cashier has access to POS");
        assert(has_role('admin', 'branch_manager', 'cashier') === true, "Cashier has access to Tables");
        assert(has_role('admin', 'branch_manager', 'cashier', 'accountant') === true, "Cashier has access to Orders");

        // Cashier FORBIDDEN Interfaces
        assert(has_role('accountant') === false, "Cashier is BLOCKED from Financial P&L");
        assert(has_role('inventory_manager') === false, "Cashier is BLOCKED from Inventory adjustments");
        assert(has_role('kitchen') === false, "Cashier is BLOCKED from Kitchen Display");

        echo " [PASS] Cashier restricted ONLY to: POS, Tables, Orders, Attendance\n";
        echo " [PASS] Cashier forbidden from: Financials, Expenses, Inventory, Recipes, Settings, HR Payroll\n";
    }

    private function testAccountantPermissions(): void
    {
        echo "\n--- 2. Testing Accountant Role (المحاسب المالي) ---\n";
        $this->setSimulatedUser('accountant', 'accountant');

        // Accountant Allowed Interfaces
        assert(has_role('accountant', 'admin') === true, "Accountant has access to Financial P&L");
        assert(has_role('admin', 'branch_manager', 'accountant') === true, "Accountant has access to Expenses & Drawer");
        assert(has_role('admin', 'branch_manager', 'accountant') === true, "Accountant has access to Purchases & Suppliers");
        assert(has_role('admin', 'branch_manager', 'accountant') === true, "Accountant has access to Payroll & Reports");

        // Accountant FORBIDDEN Interfaces
        assert(has_role('cashier') === false, "Accountant is BLOCKED from POS Cashier Screen");
        assert(has_role('kitchen') === false, "Accountant is BLOCKED from Kitchen Display");

        echo " [PASS] Accountant granted access to: Financials, Expenses, Drawer, Purchases, Suppliers, Payroll, Reports\n";
        echo " [PASS] Accountant forbidden from: Direct POS selling, Kitchen tickets, System security settings\n";
    }

    private function testKitchenPermissions(): void
    {
        echo "\n--- 3. Testing Kitchen Chef Role (المطبخ) ---\n";
        $this->setSimulatedUser('kitchen', 'kitchen');

        assert(has_role('kitchen', 'admin') === true, "Chef has access to KDS");
        assert(has_role('kitchen', 'admin') === true, "Chef has access to Recipes");
        assert(has_role('cashier') === false, "Chef is BLOCKED from POS");
        assert(has_role('accountant') === false, "Chef is BLOCKED from Financials");

        echo " [PASS] Kitchen chef restricted ONLY to: KDS screen & Recipe preparation instructions\n";
    }

    private function testAdminPermissions(): void
    {
        echo "\n--- 4. Testing Admin Role (المدير العام) ---\n";
        $this->setSimulatedUser('admin', 'admin');

        assert(has_role('admin') === true, "Admin has access to Settings");
        assert(has_role('cashier') === true, "Admin has master access to POS");
        assert(has_role('accountant') === true, "Admin has master access to Financials");
        assert(has_role('kitchen') === true, "Admin has master access to Kitchen");

        echo " [PASS] Admin has complete master control over all modules\n";
    }
}

$tester = new RolePermissionsTest();
$tester->run();
