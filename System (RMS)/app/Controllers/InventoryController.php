<?php

namespace App\Controllers;

use App\Models\Database;

class InventoryController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $branchId = auth_user()['branch_id'] ?? 1;
        $search = trim($_GET['search'] ?? '');
        $statusFilter = $_GET['status'] ?? ''; // 'low', 'all'

        $where = ["branch_id = :bid"];
        $params = ['bid' => $branchId];

        if ($search !== '') {
            $where[] = "(name LIKE :search OR sku LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        if ($statusFilter === 'low') {
            $where[] = "current_stock <= min_stock_alert";
        }

        $whereSql = implode(' AND ', $where);

        $items = $this->db->fetchAll(
            "SELECT *, (current_stock * cost_per_unit) as total_valuation 
             FROM inventory_items 
             WHERE {$whereSql} 
             ORDER BY current_stock ASC",
            $params
        );

        // Overall stats
        $totalItemsCount = (int)$this->db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE branch_id = :bid", ['bid' => $branchId]);
        $lowStockCount = (int)$this->db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE branch_id = :bid AND current_stock <= min_stock_alert", ['bid' => $branchId]);
        $totalValuation = (float)$this->db->fetchValue("SELECT SUM(current_stock * cost_per_unit) FROM inventory_items WHERE branch_id = :bid", ['bid' => $branchId]);

        require_once __DIR__ . '/../../views/inventory/index.php';
    }

    public function store(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('inventory');
        }

        $branchId = auth_user()['branch_id'] ?? 1;
        $name = trim($_POST['name'] ?? '');
        $sku = trim($_POST['sku'] ?? '') ?: ('RAW-' . rand(1000, 9999));
        $unit = trim($_POST['unit'] ?? 'كجم');
        $minStock = (float)($_POST['min_stock_alert'] ?? 5);
        $currentStock = (float)($_POST['current_stock'] ?? 0);
        $costPerUnit = (float)($_POST['cost_per_unit'] ?? 0);
        $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $batchNumber = trim($_POST['batch_number'] ?? '');

        if (empty($name)) {
            set_flash('error', 'يرجى كتابة اسم المادة الخام');
            redirect('inventory');
        }

        $itemId = $this->db->insert('inventory_items', [
            'branch_id' => $branchId,
            'name' => $name,
            'sku' => $sku,
            'unit' => $unit,
            'min_stock_alert' => $minStock,
            'current_stock' => $currentStock,
            'cost_per_unit' => $costPerUnit,
            'expiry_date' => $expiryDate,
            'batch_number' => $batchNumber,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($currentStock > 0) {
            $this->db->insert('stock_movements', [
                'inventory_item_id' => $itemId,
                'branch_id' => $branchId,
                'type' => 'adjustment_in',
                'quantity' => $currentStock,
                'unit_cost' => $costPerUnit,
                'total_cost' => $currentStock * $costPerUnit,
                'reference_type' => 'manual',
                'notes' => 'رصيد افتتاحي أولي للمادة',
                'created_by' => auth_user()['id'] ?? 1
            ]);
        }

        log_audit('إضافة مادة خام', 'inventory', "إضافة مادة خام {$name} برصيد {$currentStock} {$unit}");
        set_flash('success', 'تمت إضافة المادة الخام بنجاح');
        redirect('inventory');
    }

    /**
     * Stock adjustment / physical inventory count settlement
     */
    public function adjust(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('inventory');
        }

        $itemId = (int)($_POST['item_id'] ?? 0);
        $newStock = (float)($_POST['actual_stock'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'جرد دوري وتسوية');
        $user = auth_user();

        $item = $this->db->fetch("SELECT * FROM inventory_items WHERE id = :id", ['id' => $itemId]);
        if (!$item) {
            set_flash('error', 'المادة غير موجودة');
            redirect('inventory');
        }

        $diff = $newStock - $item['current_stock'];
        if ($diff == 0) {
            set_flash('info', 'الرصيد الفعلي يطابق الرصيد المسجل تماماً');
            redirect('inventory');
        }

        $this->db->beginTransaction();
        try {
            $this->db->update('inventory_items', [
                'current_stock' => $newStock,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $itemId]);

            $type = $diff > 0 ? 'adjustment_in' : 'adjustment_out';
            $this->db->insert('stock_movements', [
                'inventory_item_id' => $itemId,
                'branch_id' => $item['branch_id'],
                'type' => $type,
                'quantity' => $diff,
                'unit_cost' => $item['cost_per_unit'],
                'total_cost' => abs($diff) * $item['cost_per_unit'],
                'reference_type' => 'adjustment',
                'notes' => "تسوية جرد مخزني: الرصيد السابق ({$item['current_stock']}) -> الفعلي ({$newStock}). السبب: {$reason}",
                'created_by' => $user['id'] ?? 1
            ]);

            log_audit('تسوية جرد', 'inventory', "تسوية مخزون للمادة {$item['name']} بفارق {$diff} {$item['unit']}");
            $this->db->commit();
            set_flash('success', "تمت تسوية المخزون وتحديث الرصيد الفعلي بنجاح إلى {$newStock} {$item['unit']}");
        } catch (\Exception $e) {
            $this->db->rollBack();
            set_flash('error', 'خطأ أثناء تسوية المخزون: ' . $e->getMessage());
        }

        redirect('inventory');
    }

    /**
     * Stock Movement Log / Audit Trail
     */
    public function movements(): void
    {
        $itemId = $_GET['item_id'] ?? '';
        $type = $_GET['type'] ?? '';

        $where = ["1=1"];
        $params = [];

        if ($itemId !== '') {
            $where[] = "sm.inventory_item_id = :iid";
            $params['iid'] = (int)$itemId;
        }

        if ($type !== '') {
            $where[] = "sm.type = :type";
            $params['type'] = $type;
        }

        $whereSql = implode(' AND ', $where);

        $movements = $this->db->fetchAll(
            "SELECT sm.*, ii.name as item_name, ii.unit, u.name as user_name 
             FROM stock_movements sm 
             JOIN inventory_items ii ON sm.inventory_item_id = ii.id 
             LEFT JOIN users u ON sm.created_by = u.id 
             WHERE {$whereSql} 
             ORDER BY sm.id DESC LIMIT 150",
            $params
        );

        $items = $this->db->fetchAll("SELECT id, name FROM inventory_items ORDER BY name ASC");

        require_once __DIR__ . '/../../views/inventory/movements.php';
    }
}
