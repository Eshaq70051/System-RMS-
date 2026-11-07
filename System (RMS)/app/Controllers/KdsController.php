<?php

namespace App\Controllers;

use App\Models\Database;

class KdsController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        require_once __DIR__ . '/../../views/kds/index.php';
    }

    /**
     * API endpoint returning active kitchen orders (for real-time live refresh)
     */
    public function getActiveOrders(): void
    {
        $branchId = auth_user()['branch_id'] ?? 1;

        // Fetch orders that are not completed or cancelled
        $orders = $this->db->fetchAll(
            "SELECT o.*, t.table_number, u.name as cashier_name 
             FROM orders o 
             LEFT JOIN dining_tables t ON o.table_id = t.id 
             LEFT JOIN users u ON o.user_id = u.id 
             WHERE o.branch_id = :bid AND o.status IN ('pending', 'in_kitchen', 'ready') 
             ORDER BY 
                CASE o.kitchen_status 
                    WHEN 'waiting' THEN 1 
                    WHEN 'cooking' THEN 2 
                    WHEN 'done' THEN 3 
                    ELSE 4 
                END,
                o.created_at ASC",
            ['bid' => $branchId]
        );

        foreach ($orders as &$order) {
            $order['items'] = $this->db->fetchAll(
                "SELECT oi.*, p.unit, c.name as category_name 
                 FROM order_items oi 
                 JOIN products p ON oi.product_id = p.id 
                 JOIN categories c ON p.category_id = c.id 
                 WHERE oi.order_id = :oid",
                ['oid' => $order['id']]
            );

            // Calculate elapsed time in minutes
            $createdTs = strtotime($order['created_at']);
            $order['elapsed_minutes'] = max(0, round((time() - $createdTs) / 60));
        }

        json_response([
            'success' => true,
            'count' => count($orders),
            'server_time' => date('H:i:s'),
            'orders' => $orders
        ]);
    }

    /**
     * Update kitchen order status
     */
    public function updateStatus(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $orderId = (int)($input['order_id'] ?? 0);
        $status = $input['status'] ?? ''; // 'cooking', 'done', 'served'

        if (!$orderId || !in_array($status, ['waiting', 'cooking', 'done', 'served'])) {
            json_response(['success' => false, 'message' => 'بيانات غير صحيحة']);
        }

        $orderStatus = 'pending';
        $updateData = ['kitchen_status' => $status];

        if ($status === 'cooking') {
            $orderStatus = 'in_kitchen';
            $updateData['kitchen_started_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'done') {
            $orderStatus = 'ready';
            $updateData['kitchen_ready_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'served') {
            $orderStatus = 'completed';
            $updateData['completed_at'] = date('Y-m-d H:i:s');

            // Free table if dine_in
            $order = $this->db->fetch("SELECT table_id FROM orders WHERE id = :id", ['id' => $orderId]);
            if ($order && $order['table_id']) {
                $this->db->update('dining_tables', [
                    'status' => 'available',
                    'current_order_id' => null,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $order['table_id']]);
            }
        }

        $updateData['status'] = $orderStatus;
        $this->db->update('orders', $updateData, 'id = :id', ['id' => $orderId]);

        // Also update all items in this order
        $this->db->update('order_items', ['status' => $status], 'order_id = :oid', ['oid' => $orderId]);

        json_response(['success' => true, 'message' => 'تم تحديث حالة الطلب بنجاح']);
    }
}
