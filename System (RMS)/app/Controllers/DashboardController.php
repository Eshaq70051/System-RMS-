<?php

namespace App\Controllers;

use App\Models\Database;

class DashboardController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $user = auth_user();

        // If Cashier, redirect directly to POS interface
        if ($user && ($user['role_slug'] ?? '') === 'cashier') {
            redirect('pos');
        }

        // If Kitchen, redirect directly to KDS
        if ($user && ($user['role_slug'] ?? '') === 'kitchen') {
            redirect('kds');
        }

        $branchId = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null;
        $branchClause = $branchId ? " AND branch_id = $branchId " : "";

        // 1. Today's Metrics
        $today = date('Y-m-d');
        $todaySales = (float)$this->db->fetchValue(
            "SELECT SUM(total_amount) FROM orders WHERE date(created_at) = '$today' AND status != 'cancelled' $branchClause"
        );
        $todayOrdersCount = (int)$this->db->fetchValue(
            "SELECT COUNT(*) FROM orders WHERE date(created_at) = '$today' AND status != 'cancelled' $branchClause"
        );
        $todayReturns = (float)$this->db->fetchValue(
            "SELECT SUM(refund_amount) FROM order_returns WHERE date(created_at) = '$today' $branchClause"
        );

        // 2. Month's Metrics
        $currentMonth = date('Y-m');
        $monthSales = (float)$this->db->fetchValue(
            "SELECT SUM(total_amount) FROM orders WHERE strftime('%Y-%m', created_at) = '$currentMonth' AND status != 'cancelled' $branchClause"
        );
        $monthExpenses = (float)$this->db->fetchValue(
            "SELECT SUM(amount) FROM expenses WHERE strftime('%Y-%m', date) = '$currentMonth' $branchClause"
        );
        $monthReturns = (float)$this->db->fetchValue(
            "SELECT SUM(refund_amount) FROM order_returns WHERE strftime('%Y-%m', created_at) = '$currentMonth' $branchClause"
        );

        // Cost of Goods Sold (COGS) for current month based on order items
        $monthCOGS = (float)$this->db->fetchValue(
            "SELECT SUM(oi.cost_price * oi.quantity) 
             FROM order_items oi 
             JOIN orders o ON oi.order_id = o.id 
             WHERE strftime('%Y-%m', o.created_at) = '$currentMonth' AND o.status != 'cancelled' $branchClause"
        );

        // Net Profit Calculation: Net Sales - COGS - Expenses
        $netSales = $monthSales - $monthReturns;
        $netProfit = $netSales - $monthCOGS - $monthExpenses;

        // Average Order Value
        $avgOrderValue = $todayOrdersCount > 0 ? ($todaySales / $todayOrdersCount) : 0;

        // 3. Orders Status Counts
        $pendingOrders = (int)$this->db->fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'pending' $branchClause");
        $kitchenOrders = (int)$this->db->fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'in_kitchen' $branchClause");
        $readyOrders = (int)$this->db->fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'ready' $branchClause");
        $completedOrders = (int)$this->db->fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'completed' $branchClause");
        $cancelledOrders = (int)$this->db->fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'cancelled' $branchClause");

        // 4. Low Stock Inventory Items Alert
        $lowStockItems = $this->db->fetchAll(
            "SELECT * FROM inventory_items WHERE current_stock <= min_stock_alert $branchClause ORDER BY current_stock ASC LIMIT 6"
        );

        // 5. Top Selling Products
        $topProducts = $this->db->fetchAll(
            "SELECT oi.product_name, SUM(oi.quantity) as total_qty, SUM(oi.total_price) as total_revenue 
             FROM order_items oi 
             JOIN orders o ON oi.order_id = o.id 
             WHERE o.status != 'cancelled' $branchClause 
             GROUP BY oi.product_id 
             ORDER BY total_qty DESC LIMIT 5"
        );

        // 6. Recent Orders Activity
        $recentOrders = $this->db->fetchAll(
            "SELECT o.*, u.name as cashier_name, t.table_number 
             FROM orders o 
             LEFT JOIN users u ON o.user_id = u.id 
             LEFT JOIN dining_tables t ON o.table_id = t.id 
             WHERE 1=1 $branchClause 
             ORDER BY o.id DESC LIMIT 6"
        );

        // 7. Last 7 Days Sales Trend for Chart
        $chartDays = [];
        $chartSales = [];
        $chartProfits = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $chartDays[] = date('D (d/m)', strtotime($day));

            $daySale = (float)$this->db->fetchValue(
                "SELECT SUM(total_amount) FROM orders WHERE date(created_at) = '$day' AND status != 'cancelled' $branchClause"
            );
            $dayCOGS = (float)$this->db->fetchValue(
                "SELECT SUM(oi.cost_price * oi.quantity) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE date(o.created_at) = '$day' AND o.status != 'cancelled' $branchClause"
            );
            $dayExp = (float)$this->db->fetchValue(
                "SELECT SUM(amount) FROM expenses WHERE date = '$day' $branchClause"
            );

            $chartSales[] = $daySale;
            $chartProfits[] = max(0, $daySale - $dayCOGS - $dayExp);
        }

        // Branches for filter dropdown
        $branches = $this->db->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");
        $branchName = 'جميع الفروع (شامل)';
        if ($branchId) {
            $foundBranch = $this->db->fetch("SELECT name FROM branches WHERE id = :id", ['id' => $branchId]);
            if ($foundBranch) {
                $branchName = $foundBranch['name'];
            }
        }

        // Active Cashier Shift for current user
        $user = auth_user();
        $activeShift = $user ? get_active_shift($user['id'], $user['branch_id'] ?? 1) : null;

        require_once __DIR__ . '/../../views/dashboard/index.php';
    }
}
