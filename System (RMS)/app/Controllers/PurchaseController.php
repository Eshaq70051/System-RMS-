<?php

namespace App\Controllers;

use App\Models\Database;

class PurchaseController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $supplierId = $_GET['supplier_id'] ?? '';
        $where = ["1=1"];
        $params = [];

        if ($supplierId !== '') {
            $where[] = "po.supplier_id = :sid";
            $params['sid'] = (int)$supplierId;
        }

        $whereSql = implode(' AND ', $where);

        $purchases = $this->db->fetchAll(
            "SELECT po.*, s.name as supplier_name, s.company_name, u.name as creator_name 
             FROM purchase_orders po 
             JOIN suppliers s ON po.supplier_id = s.id 
             LEFT JOIN users u ON po.created_by = u.id 
             WHERE {$whereSql} 
             ORDER BY po.id DESC",
            $params
        );

        $suppliers = $this->db->fetchAll("SELECT * FROM suppliers ORDER BY name ASC");

        require_once __DIR__ . '/../../views/purchases/index.php';
    }

    public function create(): void
    {
        $suppliers = $this->db->fetchAll("SELECT * FROM suppliers ORDER BY name ASC");
        $rawMaterials = $this->db->fetchAll("SELECT * FROM inventory_items ORDER BY name ASC");
        require_once __DIR__ . '/../../views/purchases/create.php';
    }

    public function store(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('purchases/create');
        }

        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $invoiceNumber = trim($_POST['invoice_number'] ?? '') ?: ('PUR-' . date('Ymd') . '-' . rand(100, 999));
        $purchaseDate = $_POST['purchase_date'] ?? date('Y-m-d');
        $paidAmount = (float)($_POST['paid_amount'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $items = $_POST['items'] ?? []; // Array of [item_id, quantity, unit_cost]
        $user = auth_user();
        $branchId = $user['branch_id'] ?? 1;

        if (!$supplierId || empty($items)) {
            set_flash('error', 'يرجى اختيار المورد وإضافة المواد المشتراة');
            redirect('purchases/create');
        }

        $this->db->beginTransaction();
        try {
            $totalAmount = 0.0;
            $processedItems = [];

            foreach ($items as $it) {
                $itemId = (int)($it['item_id'] ?? 0);
                $qty = (float)($it['quantity'] ?? 0);
                $unitCost = (float)($it['unit_cost'] ?? 0);

                if (!$itemId || $qty <= 0 || $unitCost <= 0) continue;

                $itemTotal = $qty * $unitCost;
                $totalAmount += $itemTotal;

                $processedItems[] = [
                    'inventory_item_id' => $itemId,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $itemTotal
                ];
            }

            if (empty($processedItems)) {
                throw new \Exception("لا توجد بنود صحيحة في الفاتورة");
            }

            $remainingAmount = max(0, $totalAmount - $paidAmount);
            $paymentStatus = $remainingAmount == 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            // 1. Insert Purchase Order
            $poId = $this->db->insert('purchase_orders', [
                'branch_id' => $branchId,
                'supplier_id' => $supplierId,
                'invoice_number' => $invoiceNumber,
                'purchase_date' => $purchaseDate,
                'total_amount' => $totalAmount,
                'tax_amount' => 0.00,
                'discount_amount' => 0.00,
                'grand_total' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'payment_status' => $paymentStatus,
                'status' => 'received',
                'notes' => $notes,
                'created_by' => $user['id'] ?? 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // 2. Insert items and update inventory stock & cost
            foreach ($processedItems as $pItem) {
                $this->db->insert('purchase_order_items', [
                    'purchase_order_id' => $poId,
                    'inventory_item_id' => $pItem['inventory_item_id'],
                    'quantity' => $pItem['quantity'],
                    'unit_cost' => $pItem['unit_cost'],
                    'total_cost' => $pItem['total_cost']
                ]);

                // Update current stock and cost in inventory_items
                $this->db->query(
                    "UPDATE inventory_items SET 
                        current_stock = current_stock + :qty, 
                        cost_per_unit = :cost,
                        updated_at = datetime('now') 
                     WHERE id = :id",
                    [
                        'qty' => $pItem['quantity'],
                        'cost' => $pItem['unit_cost'],
                        'id' => $pItem['inventory_item_id']
                    ]
                );

                // Log movement
                $this->db->insert('stock_movements', [
                    'inventory_item_id' => $pItem['inventory_item_id'],
                    'branch_id' => $branchId,
                    'type' => 'purchase',
                    'quantity' => $pItem['quantity'],
                    'unit_cost' => $pItem['unit_cost'],
                    'total_cost' => $pItem['total_cost'],
                    'reference_type' => 'purchase',
                    'reference_id' => $poId,
                    'notes' => "استلام فاتورة مشتريات رقم #{$invoiceNumber}",
                    'created_by' => $user['id'] ?? 1
                ]);
            }

            // 3. Update Supplier Balance (add remaining debt)
            if ($remainingAmount > 0) {
                $this->db->query(
                    "UPDATE suppliers SET current_balance = current_balance + :rem WHERE id = :sid",
                    ['rem' => $remainingAmount, 'sid' => $supplierId]
                );
            }

            // 4. Record supplier payment if paid > 0
            if ($paidAmount > 0) {
                $this->db->insert('supplier_payments', [
                    'supplier_id' => $supplierId,
                    'purchase_order_id' => $poId,
                    'payment_date' => $purchaseDate,
                    'amount' => $paidAmount,
                    'payment_method' => 'cash',
                    'reference_number' => $invoiceNumber,
                    'notes' => "دفعة مسددة مع الفاتورة رقم #{$invoiceNumber}",
                    'created_by' => $user['id'] ?? 1
                ]);

                // Record cash drawer outflow
                $this->db->insert('cash_drawer_transactions', [
                    'branch_id' => $branchId,
                    'shift_id' => null,
                    'type' => 'out',
                    'amount' => $paidAmount,
                    'category' => 'expense',
                    'reference_type' => 'purchase',
                    'reference_id' => $poId,
                    'description' => "سداد فاتورة مشتريات رقم #{$invoiceNumber}",
                    'created_by' => $user['id'] ?? 1
                ]);
            }

            log_audit('تسجيل مشتريات', 'purchases', "تسجيل فاتورة مشتريات #{$invoiceNumber} بإجمالي {$totalAmount} ر.س وتحديث المخزون");
            $this->db->commit();
            set_flash('success', "تم تسجيل فاتورة الشراء وتحديث أرصدة المخزون وحساب المورد بنجاح");
            redirect('purchases');
        } catch (\Exception $e) {
            $this->db->rollBack();
            set_flash('error', 'خطأ أثناء تسجيل المشتريات: ' . $e->getMessage());
            redirect('purchases/create');
        }
    }

    /**
     * Suppliers Management
     */
    public function suppliers(): void
    {
        $suppliers = $this->db->fetchAll("SELECT * FROM suppliers ORDER BY id DESC");
        require_once __DIR__ . '/../../views/suppliers/index.php';
    }

    public function storeSupplier(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('suppliers');
        }

        $name = trim($_POST['name'] ?? '');
        $company = trim($_POST['company_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $taxNumber = trim($_POST['tax_number'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name)) {
            set_flash('error', 'يرجى كتابة اسم المورد');
            redirect('suppliers');
        }

        $this->db->insert('suppliers', [
            'name' => $name,
            'company_name' => $company,
            'phone' => $phone,
            'email' => $email,
            'tax_number' => $taxNumber,
            'address' => $address,
            'current_balance' => 0.00
        ]);

        log_audit('إضافة مورد', 'suppliers', "إضافة مورد جديد {$name} ({$company})");
        set_flash('success', 'تمت إضافة المورد بنجاح');
        redirect('suppliers');
    }

    /**
     * Record payment to a supplier to settle debts
     */
    public function paySupplier(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('suppliers');
        }

        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $refNumber = trim($_POST['reference_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $user = auth_user();

        if (!$supplierId || $amount <= 0) {
            set_flash('error', 'يرجى إدخال مبلغ صحيح للدفعة');
            redirect('suppliers');
        }

        $this->db->beginTransaction();
        try {
            // Deduct balance from supplier
            $this->db->query(
                "UPDATE suppliers SET current_balance = current_balance - :amt WHERE id = :id",
                ['amt' => $amount, 'id' => $supplierId]
            );

            // Insert payment log
            $this->db->insert('supplier_payments', [
                'supplier_id' => $supplierId,
                'payment_date' => date('Y-m-d'),
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'reference_number' => $refNumber,
                'notes' => $notes ?: 'سداد دفعة من الحساب الجاري للمورد',
                'created_by' => $user['id'] ?? 1
            ]);

            // If cash, record drawer transaction
            if ($paymentMethod === 'cash') {
                $this->db->insert('cash_drawer_transactions', [
                    'branch_id' => $user['branch_id'] ?? 1,
                    'shift_id' => null,
                    'type' => 'out',
                    'amount' => $amount,
                    'category' => 'expense',
                    'description' => "سداد دفعة للمورد #{$supplierId}",
                    'created_by' => $user['id'] ?? 1
                ]);
            }

            log_audit('سداد مورد', 'suppliers', "سداد مبلغ {$amount} ر.س للمورد #{$supplierId}");
            $this->db->commit();
            set_flash('success', "تم تسجيل سداد الدفعة بنجاح وتحديث رصيد المورد");
        } catch (\Exception $e) {
            $this->db->rollBack();
            set_flash('error', 'خطأ أثناء تسجيل الدفعة: ' . $e->getMessage());
        }

        redirect('suppliers');
    }
}
