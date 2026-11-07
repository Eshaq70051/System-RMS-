<?php

namespace App\Controllers;

use App\Models\Database;

class FinancialController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Profit & Loss Income Statement (قائمة الأرباح والخسائر الحقيقية)
     */
    public function index(): void
    {
        $branchId = auth_user()['branch_id'] ?? 1;
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        // 1. Gross Sales
        $grossSales = (float)$this->db->fetchValue(
            "SELECT SUM(total_amount) FROM orders 
             WHERE branch_id = :bid AND date(created_at) BETWEEN :df AND :dt AND status != 'cancelled'",
            ['bid' => $branchId, 'df' => $dateFrom, 'dt' => $dateTo]
        );

        // 2. Returns & Refunds
        $returns = (float)$this->db->fetchValue(
            "SELECT SUM(refund_amount) FROM order_returns 
             WHERE branch_id = :bid AND date(created_at) BETWEEN :df AND :dt",
            ['bid' => $branchId, 'df' => $dateFrom, 'dt' => $dateTo]
        );

        $netSales = max(0, $grossSales - $returns);

        // 3. Cost of Goods Sold (COGS)
        $cogs = (float)$this->db->fetchValue(
            "SELECT SUM(oi.cost_price * oi.quantity) 
             FROM order_items oi 
             JOIN orders o ON oi.order_id = o.id 
             WHERE o.branch_id = :bid AND date(o.created_at) BETWEEN :df AND :dt AND o.status != 'cancelled'",
            ['bid' => $branchId, 'df' => $dateFrom, 'dt' => $dateTo]
        );

        // Gross Profit (إجمالي الربح قبل المصاريف)
        $grossProfit = $netSales - $cogs;
        $grossMargin = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 1) : 0;

        // 4. Operating Expenses breakdown by category
        $expensesByCategory = $this->db->fetchAll(
            "SELECT ec.name as category_name, SUM(e.amount) as total_amount 
             FROM expenses e 
             JOIN expense_categories ec ON e.expense_category_id = ec.id 
             WHERE e.branch_id = :bid AND date(e.date) BETWEEN :df AND :dt 
             GROUP BY e.expense_category_id 
             ORDER BY total_amount DESC",
            ['bid' => $branchId, 'df' => $dateFrom, 'dt' => $dateTo]
        );

        $totalExpenses = array_sum(array_column($expensesByCategory, 'total_amount'));

        // 5. Net Profit (صافي الربح الحقيقي)
        $netProfit = $grossProfit - $totalExpenses;
        $netMargin = $netSales > 0 ? round(($netProfit / $netSales) * 100, 1) : 0;

        // Cash Drawer Balance
        $cashIn = (float)$this->db->fetchValue("SELECT SUM(amount) FROM cash_drawer_transactions WHERE branch_id = :bid AND type = 'in'", ['bid' => $branchId]);
        $cashOut = (float)$this->db->fetchValue("SELECT SUM(amount) FROM cash_drawer_transactions WHERE branch_id = :bid AND type = 'out'", ['bid' => $branchId]);
        $currentDrawerBalance = $cashIn - $cashOut;

        require_once __DIR__ . '/../../views/financial/index.php';
    }

    /**
     * Expenses Management
     */
    public function expenses(): void
    {
        $branchId = auth_user()['branch_id'] ?? 1;
        $catId = $_GET['category_id'] ?? '';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        $where = ["e.branch_id = :bid", "date(e.date) BETWEEN :df AND :dt"];
        $params = ['bid' => $branchId, 'df' => $dateFrom, 'dt' => $dateTo];

        if ($catId !== '') {
            $where[] = "e.expense_category_id = :cid";
            $params['cid'] = (int)$catId;
        }

        $whereSql = implode(' AND ', $where);

        $expenses = $this->db->fetchAll(
            "SELECT e.*, ec.name as category_name, u.name as user_name 
             FROM expenses e 
             JOIN expense_categories ec ON e.expense_category_id = ec.id 
             LEFT JOIN users u ON e.created_by = u.id 
             WHERE {$whereSql} 
             ORDER BY e.date DESC, e.id DESC",
            $params
        );

        $categories = $this->db->fetchAll("SELECT * FROM expense_categories ORDER BY name ASC");

        require_once __DIR__ . '/../../views/financial/expenses.php';
    }

    public function storeExpense(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('expenses');
        }

        $branchId = auth_user()['branch_id'] ?? 1;
        $categoryId = (int)($_POST['expense_category_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $date = $_POST['date'] ?? date('Y-m-d');
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $paidTo = trim($_POST['paid_to'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $user = auth_user();

        if (empty($title) || !$categoryId || $amount <= 0) {
            set_flash('error', 'يرجى إدخال عنوان المصروف والقسم والمبلغ بشكل صحيح');
            redirect('expenses');
        }

        $this->db->beginTransaction();
        try {
            $expId = $this->db->insert('expenses', [
                'branch_id' => $branchId,
                'expense_category_id' => $categoryId,
                'title' => $title,
                'amount' => $amount,
                'date' => $date,
                'payment_method' => $paymentMethod,
                'paid_to' => $paidTo,
                'notes' => $notes,
                'created_by' => $user['id'] ?? 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // If cash payment, deduct from cash drawer
            if ($paymentMethod === 'cash') {
                $this->db->insert('cash_drawer_transactions', [
                    'branch_id' => $branchId,
                    'shift_id' => null,
                    'type' => 'out',
                    'amount' => $amount,
                    'category' => 'expense',
                    'reference_type' => 'expense',
                    'reference_id' => $expId,
                    'description' => "صرف مصروف: {$title}",
                    'created_by' => $user['id'] ?? 1
                ]);
            }

            log_audit('تسجيل مصروف', 'financial', "تسجيل مصروف {$title} بمبلغ {$amount} ر.س");
            $this->db->commit();
            set_flash('success', 'تم تسجيل المصروف بنجاح وترحيله للصندوق');
        } catch (\Exception $e) {
            $this->db->rollBack();
            set_flash('error', 'خطأ أثناء تسجيل المصروف: ' . $e->getMessage());
        }

        redirect('expenses');
    }

    /**
     * Cash Drawer Log (حركة الصندوق)
     */
    public function drawer(): void
    {
        $branchId = auth_user()['branch_id'] ?? 1;

        $transactions = $this->db->fetchAll(
            "SELECT cdt.*, u.name as user_name 
             FROM cash_drawer_transactions cdt 
             LEFT JOIN users u ON cdt.created_by = u.id 
             WHERE cdt.branch_id = :bid 
             ORDER BY cdt.id DESC LIMIT 150",
            ['bid' => $branchId]
        );

        $totalIn = (float)$this->db->fetchValue("SELECT SUM(amount) FROM cash_drawer_transactions WHERE branch_id = :bid AND type = 'in'", ['bid' => $branchId]);
        $totalOut = (float)$this->db->fetchValue("SELECT SUM(amount) FROM cash_drawer_transactions WHERE branch_id = :bid AND type = 'out'", ['bid' => $branchId]);
        $currentBalance = $totalIn - $totalOut;

        require_once __DIR__ . '/../../views/financial/drawer.php';
    }
}
