<?php
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Models/Database.php';

use App\Models\Database;

$db = Database::getInstance();
$tables = [
    'orders',
    'order_items',
    'order_returns',
    'order_return_items',
    'cashier_shifts',
    'cash_drawer_transactions',
    'expenses',
    'expense_categories',
    'purchase_orders',
    'purchase_order_items',
    'supplier_payments',
    'employee_payrolls',
    'employee_attendances',
    'stock_movements',
    'audit_logs',
    'products',
    'product_variants',
    'product_addons',
    'recipes',
    'recipe_ingredients',
    'inventory_items',
    'categories',
    'dining_tables',
    'suppliers',
    'employees',
    'users',
    'roles',
    'branches',
    'settings'
];

foreach ($tables as $t) {
    $count = $db->fetchValue("SELECT COUNT(*) FROM {$t}");
    echo str_pad($t, 26) . ": " . $count . "\n";
}
