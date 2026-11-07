<?php

use App\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\PosController;
use App\Controllers\KdsController;
use App\Controllers\OrderController;
use App\Controllers\TableController;
use App\Controllers\ProductController;
use App\Controllers\InventoryController;
use App\Controllers\RecipeController;
use App\Controllers\PurchaseController;
use App\Controllers\EmployeeController;
use App\Controllers\FinancialController;
use App\Controllers\ReportController;
use App\Controllers\SettingController;

/** @var Router $router */

// Auth routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// Dashboard (Admin, Manager, Accountant)
$router->get('/', [DashboardController::class, 'index'], ['auth']);
$router->get('/dashboard', [DashboardController::class, 'index'], ['auth']);

// POS (Points of Sale: Admin, Branch Manager, Cashier)
$router->get('/pos', [PosController::class, 'index'], ['auth', 'role:admin,branch_manager,cashier']);
$router->post('/pos/open-shift', [PosController::class, 'openShift'], ['auth', 'role:admin,branch_manager,cashier']);
$router->post('/pos/close-shift', [PosController::class, 'closeShift'], ['auth', 'role:admin,branch_manager,cashier']);
$router->post('/pos/checkout', [PosController::class, 'checkout'], ['auth', 'role:admin,branch_manager,cashier']);
$router->get('/pos/receipt/{id}', [PosController::class, 'receipt'], ['auth']);
$router->post('/pos/return', [PosController::class, 'returnOrder'], ['auth', 'role:admin,branch_manager,cashier']);
$router->get('/pos/shift-report', [PosController::class, 'shiftReport'], ['auth', 'role:admin,branch_manager,cashier']);
$router->get('/pos/shift-report/print', [PosController::class, 'printShiftReport'], ['auth', 'role:admin,branch_manager,cashier']);

// KDS (Kitchen Display System: Admin, Branch Manager, Kitchen)
$router->get('/kds', [KdsController::class, 'index'], ['auth', 'role:admin,branch_manager,kitchen']);
$router->get('/kds/orders', [KdsController::class, 'getActiveOrders'], ['auth', 'role:admin,branch_manager,kitchen']);
$router->post('/kds/update-status', [KdsController::class, 'updateStatus'], ['auth', 'role:admin,branch_manager,kitchen']);

// Orders (Admin, Branch Manager, Cashier, Accountant)
$router->get('/orders', [OrderController::class, 'index'], ['auth', 'role:admin,branch_manager,cashier,accountant']);
$router->get('/orders/{id}', [OrderController::class, 'show'], ['auth', 'role:admin,branch_manager,cashier,accountant']);
$router->post('/orders/{id}/cancel', [OrderController::class, 'cancel'], ['auth', 'role:admin,branch_manager,cashier']);

// Tables (Admin, Branch Manager, Cashier)
$router->get('/tables', [TableController::class, 'index'], ['auth', 'role:admin,branch_manager,cashier']);
$router->post('/tables', [TableController::class, 'store'], ['auth', 'role:admin,branch_manager']);
$router->post('/tables/{id}/status', [TableController::class, 'updateStatus'], ['auth', 'role:admin,branch_manager,cashier']);
$router->post('/tables/transfer', [TableController::class, 'transfer'], ['auth', 'role:admin,branch_manager,cashier']);

// Products & Categories (Admin, Branch Manager)
$router->get('/products', [ProductController::class, 'index'], ['auth', 'role:admin,branch_manager']);
$router->get('/products/create', [ProductController::class, 'create'], ['auth', 'role:admin,branch_manager']);
$router->post('/products', [ProductController::class, 'store'], ['auth', 'role:admin,branch_manager']);
$router->get('/products/{id}/edit', [ProductController::class, 'edit'], ['auth', 'role:admin,branch_manager']);
$router->post('/products/{id}', [ProductController::class, 'update'], ['auth', 'role:admin,branch_manager']);
$router->post('/products/{id}/delete', [ProductController::class, 'delete'], ['auth', 'role:admin,branch_manager']);
$router->get('/categories', [ProductController::class, 'categories'], ['auth', 'role:admin,branch_manager']);
$router->post('/categories', [ProductController::class, 'storeCategory'], ['auth', 'role:admin,branch_manager']);

// Inventory & Recipes (Admin, Branch Manager, Inventory Manager, Accountant for inventory)
$router->get('/inventory', [InventoryController::class, 'index'], ['auth', 'role:admin,branch_manager,inventory_manager,accountant']);
$router->post('/inventory', [InventoryController::class, 'store'], ['auth', 'role:admin,branch_manager,inventory_manager']);
$router->post('/inventory/adjust', [InventoryController::class, 'adjust'], ['auth', 'role:admin,branch_manager,inventory_manager']);
$router->get('/inventory/movements', [InventoryController::class, 'movements'], ['auth', 'role:admin,branch_manager,inventory_manager,accountant']);

// Recipes (Admin, Branch Manager, Kitchen, Inventory Manager)
$router->get('/recipes', [RecipeController::class, 'index'], ['auth', 'role:admin,branch_manager,kitchen,inventory_manager']);
$router->post('/recipes', [RecipeController::class, 'store'], ['auth', 'role:admin,branch_manager']);
$router->post('/recipes/{id}/delete', [RecipeController::class, 'delete'], ['auth', 'role:admin,branch_manager']);

// Purchases & Suppliers (Admin, Branch Manager, Inventory Manager, Accountant)
$router->get('/purchases', [PurchaseController::class, 'index'], ['auth', 'role:admin,branch_manager,inventory_manager,accountant']);
$router->get('/purchases/create', [PurchaseController::class, 'create'], ['auth', 'role:admin,branch_manager,inventory_manager,accountant']);
$router->post('/purchases', [PurchaseController::class, 'store'], ['auth', 'role:admin,branch_manager,inventory_manager,accountant']);
$router->get('/suppliers', [PurchaseController::class, 'suppliers'], ['auth', 'role:admin,branch_manager,accountant']);
$router->post('/suppliers', [PurchaseController::class, 'storeSupplier'], ['auth', 'role:admin,branch_manager,accountant']);
$router->post('/suppliers/pay', [PurchaseController::class, 'paySupplier'], ['auth', 'role:admin,branch_manager,accountant']);

// Employees, Attendance & Payroll (Admin, Branch Manager, Accountant)
$router->get('/employees', [EmployeeController::class, 'index'], ['auth', 'role:admin,branch_manager,accountant']);
$router->post('/employees', [EmployeeController::class, 'store'], ['auth', 'role:admin,branch_manager']);
$router->get('/attendance', [EmployeeController::class, 'attendance'], ['auth']);
$router->post('/attendance', [EmployeeController::class, 'recordAttendance'], ['auth', 'role:admin,branch_manager,accountant']);
$router->get('/payroll', [EmployeeController::class, 'payroll'], ['auth', 'role:admin,branch_manager,accountant']);
$router->post('/payroll', [EmployeeController::class, 'generatePayroll'], ['auth', 'role:admin,branch_manager,accountant']);

// Financial, Expenses & Cash Drawer (Admin, Branch Manager, Accountant)
$router->get('/financial', [FinancialController::class, 'index'], ['auth', 'role:admin,branch_manager,accountant']);
$router->get('/expenses', [FinancialController::class, 'expenses'], ['auth', 'role:admin,branch_manager,accountant']);
$router->post('/expenses', [FinancialController::class, 'storeExpense'], ['auth', 'role:admin,branch_manager,accountant']);
$router->get('/drawer', [FinancialController::class, 'drawer'], ['auth', 'role:admin,branch_manager,accountant']);

// Reports (Admin, Branch Manager, Accountant)
$router->get('/reports', [ReportController::class, 'index'], ['auth', 'role:admin,branch_manager,accountant']);
$router->get('/reports/export-sales', [ReportController::class, 'exportSales'], ['auth', 'role:admin,branch_manager,accountant']);

// Settings & System (Admin only)
$router->get('/settings', [SettingController::class, 'index'], ['auth', 'role:admin']);
$router->post('/settings', [SettingController::class, 'update'], ['auth', 'role:admin']);
$router->post('/settings/users', [SettingController::class, 'storeUser'], ['auth', 'role:admin']);
$router->get('/settings/audit-logs', [SettingController::class, 'auditLogs'], ['auth', 'role:admin']);
$router->get('/settings/backup', [SettingController::class, 'backup'], ['auth', 'role:admin']);
