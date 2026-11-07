<?php

namespace App\Controllers;

use App\Models\Database;

class ReportController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $tab = $_GET['tab'] ?? 'sales';
        $branchId = $_GET['branch_id'] ?? '';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        $where = ["date(o.created_at) BETWEEN :df AND :dt", "o.status != 'cancelled'"];
        $params = ['df' => $dateFrom, 'dt' => $dateTo];

        if ($branchId !== '') {
            $where[] = "o.branch_id = :bid";
            $params['bid'] = (int)$branchId;
        }

        $whereSql = implode(' AND ', $where);

        // 1. Sales Report Data
        $salesSummary = $this->db->fetch(
            "SELECT 
                COUNT(*) as orders_count,
                SUM(subtotal) as total_subtotal,
                SUM(discount_amount) as total_discount,
                SUM(tax_amount) as total_tax,
                SUM(total_amount) as total_revenue,
                SUM(cash_amount) as total_cash,
                SUM(card_amount) as total_card
             FROM orders o 
             WHERE {$whereSql}",
            $params
        );

        $salesDaily = $this->db->fetchAll(
            "SELECT 
                date(o.created_at) as sale_date,
                COUNT(*) as orders_count,
                SUM(total_amount) as revenue,
                SUM(cash_amount) as cash_rev,
                SUM(card_amount) as card_rev
             FROM orders o 
             WHERE {$whereSql} 
             GROUP BY date(o.created_at) 
             ORDER BY sale_date DESC",
            $params
        );

        // 2. Shifts Report Data
        $shifts = $this->db->fetchAll(
            "SELECT cs.*, u.name as cashier_name, b.name as branch_name 
             FROM cashier_shifts cs 
             JOIN users u ON cs.user_id = u.id 
             JOIN branches b ON cs.branch_id = b.id 
             WHERE date(cs.opened_at) BETWEEN :df AND :dt 
             ORDER BY cs.id DESC",
            ['df' => $dateFrom, 'dt' => $dateTo]
        );

        // 3. Inventory Valuation & Alert Report
        $inventoryStats = $this->db->fetchAll(
            "SELECT *, (current_stock * cost_per_unit) as valuation 
             FROM inventory_items 
             ORDER BY valuation DESC"
        );

        $branches = $this->db->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");

        require_once __DIR__ . '/../../views/reports/index.php';
    }

    /**
     * Export Sales to CSV with UTF-8 BOM for Microsoft Excel
     */
    public function exportSales(): void
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        $orders = $this->db->fetchAll(
            "SELECT o.order_number, o.created_at, o.order_type, o.payment_method, o.total_amount, o.discount_amount, o.tax_amount, u.name as cashier 
             FROM orders o 
             JOIN users u ON o.user_id = u.id 
             WHERE date(o.created_at) BETWEEN :df AND :dt AND o.status != 'cancelled' 
             ORDER BY o.id DESC",
            ['df' => $dateFrom, 'dt' => $dateTo]
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="sales_report_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['رقم الطلب', 'تاريخ الإنشاء', 'نوع الطلب', 'طريقة الدفع', 'الإجمالي', 'الخصم', 'الضريبة', 'الكاشير']);

        foreach ($orders as $row) {
            fputcsv($output, [
                $row['order_number'],
                $row['created_at'],
                $row['order_type'],
                $row['payment_method'],
                $row['total_amount'],
                $row['discount_amount'],
                $row['tax_amount'],
                $row['cashier']
            ]);
        }

        fclose($output);
        exit;
    }
}
