<?php

namespace App\Controllers;

use App\Models\Database;

class OrderController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $branchId = $_GET['branch_id'] ?? '';
        $status = $_GET['status'] ?? '';
        $orderType = $_GET['order_type'] ?? '';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $search = trim($_GET['search'] ?? '');

        $where = ["1=1"];
        $params = [];

        if ($branchId !== '') {
            $where[] = "o.branch_id = :bid";
            $params['bid'] = (int)$branchId;
        }

        if ($status !== '') {
            $where[] = "o.status = :status";
            $params['status'] = $status;
        }

        if ($orderType !== '') {
            $where[] = "o.order_type = :otype";
            $params['otype'] = $orderType;
        }

        if ($dateFrom) {
            $where[] = "date(o.created_at) >= :dfrom";
            $params['dfrom'] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = "date(o.created_at) <= :dto";
            $params['dto'] = $dateTo;
        }

        if ($search) {
            $where[] = "(o.order_number LIKE :search OR o.customer_name LIKE :search OR o.customer_phone LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        $whereClause = implode(' AND ', $where);

        $orders = $this->db->fetchAll(
            "SELECT o.*, u.name as cashier_name, b.name as branch_name, t.table_number 
             FROM orders o 
             JOIN users u ON o.user_id = u.id 
             JOIN branches b ON o.branch_id = b.id 
             LEFT JOIN dining_tables t ON o.table_id = t.id 
             WHERE {$whereClause} 
             ORDER BY o.id DESC LIMIT 100",
            $params
        );

        $branches = $this->db->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");

        require_once __DIR__ . '/../../views/orders/index.php';
    }

    public function show(int $id): void
    {
        $order = $this->db->fetch(
            "SELECT o.*, u.name as cashier_name, b.name as branch_name, t.table_number 
             FROM orders o 
             JOIN users u ON o.user_id = u.id 
             JOIN branches b ON o.branch_id = b.id 
             LEFT JOIN dining_tables t ON o.table_id = t.id 
             WHERE o.id = :id",
            ['id' => $id]
        );

        if (!$order) {
            set_flash('error', 'الطلب غير موجود');
            redirect('orders');
        }

        $items = $this->db->fetchAll("SELECT * FROM order_items WHERE order_id = :id", ['id' => $id]);
        $returns = $this->db->fetchAll("SELECT * FROM order_returns WHERE order_id = :id", ['id' => $id]);

        require_once __DIR__ . '/../../views/orders/show.php';
    }

    public function cancel(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('orders/' . $id);
        }

        $order = $this->db->fetch("SELECT * FROM orders WHERE id = :id", ['id' => $id]);
        if (!$order || $order['status'] === 'cancelled') {
            set_flash('error', 'لا يمكن إلغاء هذا الطلب');
            redirect('orders');
        }

        $this->db->beginTransaction();
        try {
            // Cancel order
            $this->db->update('orders', ['status' => 'cancelled'], 'id = :id', ['id' => $id]);

            // If table was occupied, release table
            if ($order['table_id']) {
                $this->db->update('dining_tables', [
                    'status' => 'available',
                    'current_order_id' => null
                ], 'id = :tid', ['tid' => $order['table_id']]);
            }

            // Restore recipe inventory
            $items = $this->db->fetchAll("SELECT * FROM order_items WHERE order_id = :id", ['id' => $id]);
            foreach ($items as $item) {
                $recipe = $this->db->fetch("SELECT id FROM recipes WHERE product_id = :pid", ['pid' => $item['product_id']]);
                if ($recipe) {
                    $ingredients = $this->db->fetchAll("SELECT * FROM recipe_ingredients WHERE recipe_id = :rid", ['rid' => $recipe['id']]);
                    foreach ($ingredients as $ing) {
                        $qtyToRestore = $ing['quantity_required'] * $item['quantity'];
                        $this->db->query(
                            "UPDATE inventory_items SET current_stock = current_stock + :qty WHERE id = :id",
                            ['qty' => $qtyToRestore, 'id' => $ing['inventory_item_id']]
                        );
                    }
                }
            }

            log_audit('إلغاء طلب مبيعات', 'orders', "تم إلغاء الطلب #{$order['order_number']}");
            $this->db->commit();
            set_flash('success', 'تم إلغاء الطلب واسترجاع المخزون بنجاح');
        } catch (\Exception $e) {
            $this->db->rollBack();
            set_flash('error', 'حدث خطأ أثناء إلغاء الطلب: ' . $e->getMessage());
        }

        redirect('orders/' . $id);
    }
}
