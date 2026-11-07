<?php

namespace App\Controllers;

use App\Models\Database;

class TableController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $branchId = auth_user()['branch_id'] ?? 1;

        $tables = $this->db->fetchAll(
            "SELECT t.*, o.order_number, o.total_amount, o.customer_name, o.created_at as order_time 
             FROM dining_tables t 
             LEFT JOIN orders o ON t.current_order_id = o.id 
             WHERE t.branch_id = :bid 
             ORDER BY t.section ASC, t.table_number ASC",
            ['bid' => $branchId]
        );

        // Group by section
        $groupedTables = [];
        foreach ($tables as $t) {
            $groupedTables[$t['section']][] = $t;
        }

        require_once __DIR__ . '/../../views/tables/index.php';
    }

    public function store(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('tables');
        }

        $branchId = auth_user()['branch_id'] ?? 1;
        $tableNumber = trim($_POST['table_number'] ?? '');
        $section = trim($_POST['section'] ?? 'الصالة الرئيسية');
        $capacity = (int)($_POST['capacity'] ?? 4);

        if (empty($tableNumber)) {
            set_flash('error', 'يرجى كتابة رقم أو اسم الطاولة');
            redirect('tables');
        }

        $this->db->insert('dining_tables', [
            'branch_id' => $branchId,
            'table_number' => $tableNumber,
            'section' => $section,
            'capacity' => $capacity,
            'status' => 'available'
        ]);

        log_audit('إضافة طاولة', 'tables', "إضافة طاولة جديدة {$tableNumber} في {$section}");
        set_flash('success', 'تمت إضافة الطاولة بنجاح');
        redirect('tables');
    }

    public function updateStatus(int $id): void
    {
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'رمز التحقق غير صالح']);
        }

        $status = $_POST['status'] ?? 'available';
        if (!in_array($status, ['available', 'occupied', 'reserved'])) {
            json_response(['success' => false, 'message' => 'حالة غير صالحة']);
        }

        $updateData = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status === 'available') {
            $updateData['current_order_id'] = null;
        }

        $this->db->update('dining_tables', $updateData, 'id = :id', ['id' => $id]);

        json_response(['success' => true, 'message' => 'تم تحديث حالة الطاولة']);
    }

    /**
     * Transfer an order to another table
     */
    public function transfer(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('tables');
        }

        $fromTableId = (int)($_POST['from_table_id'] ?? 0);
        $toTableId = (int)($_POST['to_table_id'] ?? 0);

        if (!$fromTableId || !$toTableId || $fromTableId === $toTableId) {
            set_flash('error', 'يرجى اختيار طاولة مختلفة للتحويل إليها');
            redirect('tables');
        }

        $fromTable = $this->db->fetch("SELECT * FROM dining_tables WHERE id = :id", ['id' => $fromTableId]);
        $toTable = $this->db->fetch("SELECT * FROM dining_tables WHERE id = :id", ['id' => $toTableId]);

        if (!$fromTable || !$fromTable['current_order_id']) {
            set_flash('error', 'الطاولة المصدر ليس عليها طلب نشط!');
            redirect('tables');
        }

        if ($toTable['status'] === 'occupied') {
            set_flash('error', 'الطاولة الهدف مشغولة بالفعل بطلب آخر!');
            redirect('tables');
        }

        $orderId = $fromTable['current_order_id'];

        $this->db->beginTransaction();
        try {
            // Update order record
            $this->db->update('orders', ['table_id' => $toTableId], 'id = :oid', ['oid' => $orderId]);

            // Free fromTable
            $this->db->update('dining_tables', [
                'status' => 'available',
                'current_order_id' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $fromTableId]);

            // Occupy toTable
            $this->db->update('dining_tables', [
                'status' => 'occupied',
                'current_order_id' => $orderId,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $toTableId]);

            log_audit('نقل طاولة', 'tables', "نقل الطلب #{$orderId} من الطاولة {$fromTable['table_number']} إلى {$toTable['table_number']}");
            $this->db->commit();
            set_flash('success', "تم نقل الطلب بنجاح إلى الطاولة {$toTable['table_number']}");
        } catch (\Exception $e) {
            $this->db->rollBack();
            set_flash('error', 'خطأ أثناء نقل الطاولة: ' . $e->getMessage());
        }

        redirect('tables');
    }
}
