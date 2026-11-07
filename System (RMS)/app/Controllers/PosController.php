<?php

namespace App\Controllers;

use App\Models\Database;
use App\Services\OrderService;

class PosController
{
    private Database $db;
    private OrderService $orderService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->orderService = new OrderService();
    }

    public function index(): void
    {
        $user = auth_user();
        $branchId = $user['branch_id'] ?? 1;

        // Check active shift - auto-create in background so cashier is never blocked
        $activeShift = get_active_shift($user['id'], $branchId);
        if (!$activeShift && $user) {
            $this->db->insert('cashier_shifts', [
                'branch_id' => $branchId,
                'user_id' => $user['id'],
                'opening_cash' => 0.00,
                'status' => 'open',
                'opened_at' => date('Y-m-d H:i:s')
            ]);
            $activeShift = get_active_shift($user['id'], $branchId);
        }

        // Categories
        $categories = $this->db->fetchAll(
            "SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC"
        );

        // Products with variants and addons
        $products = $this->db->fetchAll(
            "SELECT p.*, c.name as category_name 
             FROM products p 
             JOIN categories c ON p.category_id = c.id 
             WHERE p.is_active = 1 AND p.is_available = 1 
             ORDER BY c.sort_order ASC, p.name ASC"
        );

        foreach ($products as &$prod) {
            $prod['variants'] = $this->db->fetchAll(
                "SELECT * FROM product_variants WHERE product_id = :pid",
                ['pid' => $prod['id']]
            );
            $prod['addons'] = $this->db->fetchAll(
                "SELECT * FROM product_addons WHERE product_id = :pid",
                ['pid' => $prod['id']]
            );
        }

        // Available Tables
        $tables = $this->db->fetchAll(
            "SELECT * FROM dining_tables WHERE branch_id = :bid ORDER BY table_number ASC",
            ['bid' => $branchId]
        );

        // Recent Orders by current cashier today
        $recentOrders = $this->db->fetchAll(
            "SELECT o.*, t.table_number 
             FROM orders o 
             LEFT JOIN dining_tables t ON o.table_id = t.id 
             WHERE o.user_id = :uid AND date(o.created_at) = date('now') 
             ORDER BY o.id DESC LIMIT 10",
            ['uid' => $user['id']]
        );

        require_once __DIR__ . '/../../views/pos/index.php';
    }

    /**
     * Open a new shift for the cashier
     */
    public function openShift(): void
    {
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'رمز التحقق غير صالح']);
        }

        $user = auth_user();
        $branchId = $user['branch_id'] ?? 1;
        $openingCash = (float)($_POST['opening_cash'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        // Check if there is already an open shift
        $existing = get_active_shift($user['id'], $branchId);
        if ($existing) {
            json_response(['success' => false, 'message' => 'لديك وردية مفتوحة بالفعل!']);
        }

        $shiftId = $this->db->insert('cashier_shifts', [
            'branch_id' => $branchId,
            'user_id' => $user['id'],
            'opened_at' => date('Y-m-d H:i:s'),
            'opening_cash' => $openingCash,
            'total_sales' => 0.00,
            'total_returns' => 0.00,
            'total_cash_sales' => 0.00,
            'total_card_sales' => 0.00,
            'status' => 'open',
            'notes' => $notes
        ]);

        $this->db->insert('cash_drawer_transactions', [
            'branch_id' => $branchId,
            'shift_id' => $shiftId,
            'type' => 'in',
            'amount' => $openingCash,
            'category' => 'opening_balance',
            'description' => 'افتتاح وردية جديدة بصندوق نقدية',
            'created_by' => $user['id']
        ]);

        log_audit('افتتاح وردية كاشير', 'pos', "افتتاح وردية برصيد {$openingCash} ر.س للمستخدم {$user['username']}");

        json_response([
            'success' => true,
            'shift_id' => $shiftId,
            'message' => 'تم افتتاح الوردية بنجاح، يمكنك الآن استقبال الطلبات.'
        ]);
    }

    /**
     * Close the active shift and calculate drawer differences
     */
    public function closeShift(): void
    {
        if (!verify_csrf()) {
            json_response(['success' => false, 'message' => 'رمز التحقق غير صالح']);
        }

        $user = auth_user();
        $branchId = $user['branch_id'] ?? 1;
        $shift = get_active_shift($user['id'], $branchId);

        if (!$shift) {
            json_response(['success' => false, 'message' => 'لا توجد وردية مفتوحة حالياً']);
        }

        $actualCash = (float)($_POST['closing_cash_actual'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        // Expected Cash = Opening Cash + Cash Sales - Cash Returns
        $expectedCash = $shift['opening_cash'] + $shift['total_cash_sales'] - $shift['total_returns'];
        $difference = $actualCash - $expectedCash; // Positive = Surplus, Negative = Shortage (عجز)

        $this->db->update('cashier_shifts', [
            'closed_at' => date('Y-m-d H:i:s'),
            'closing_cash_actual' => $actualCash,
            'expected_cash' => $expectedCash,
            'cash_difference' => $difference,
            'status' => 'closed',
            'notes' => $notes
        ], "id = :id", ['id' => $shift['id']]);

        log_audit(
            'إغلاق وردية كاشير',
            'pos',
            "إغلاق الوردية #{$shift['id']}: النقد الفعلي {$actualCash}، المتوقع {$expectedCash}، الفارق {$difference}"
        );

        json_response([
            'success' => true,
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'difference' => $difference,
            'message' => 'تم إغلاق الوردية بنجاح وتسوية الصندوق.'
        ]);
    }

    /**
     * Submit an order from POS
     */
    public function checkout(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $user = auth_user();
        $branchId = $user['branch_id'] ?? 1;
        $shift = get_active_shift($user['id'], $branchId);

        if (!$shift) {
            $shiftId = $this->db->insert('cashier_shifts', [
                'branch_id' => $branchId,
                'user_id' => $user['id'],
                'opening_cash' => 0.00,
                'status' => 'open',
                'opened_at' => date('Y-m-d H:i:s')
            ]);
            $shift = get_active_shift($user['id'], $branchId);
        }

        $items = $input['items'] ?? [];
        if (empty($items)) {
            json_response(['success' => false, 'message' => 'سلة الطلب فارغة!']);
        }

        $orderData = [
            'branch_id' => $branchId,
            'user_id' => $user['id'],
            'shift_id' => $shift['id'],
            'table_id' => !empty($input['table_id']) ? (int)$input['table_id'] : null,
            'order_type' => $input['order_type'] ?? 'dine_in',
            'customer_name' => $input['customer_name'] ?? 'عميل صالة',
            'customer_phone' => $input['customer_phone'] ?? '',
            'delivery_address' => $input['delivery_address'] ?? null,
            'payment_method' => $input['payment_method'] ?? 'cash',
            'paid_amount' => (float)($input['paid_amount'] ?? 0),
            'cash_amount' => (float)($input['cash_amount'] ?? 0),
            'card_amount' => (float)($input['card_amount'] ?? 0),
            'discount_value' => (float)($input['discount_value'] ?? 0),
            'kitchen_notes' => $input['kitchen_notes'] ?? ''
        ];

        $result = $this->orderService->createOrder($orderData, $items);
        json_response($result);
    }

    /**
     * Get thermal receipt data or HTML
     */
    public function receipt(int $id): void
    {
        $order = $this->db->fetch(
            "SELECT o.*, u.name as cashier_name, b.name as branch_name, b.phone as branch_phone, b.address as branch_address, b.tax_number as branch_tax, t.table_number 
             FROM orders o 
             JOIN users u ON o.user_id = u.id 
             JOIN branches b ON o.branch_id = b.id 
             LEFT JOIN dining_tables t ON o.table_id = t.id 
             WHERE o.id = :id",
            ['id' => $id]
        );

        if (!$order) {
            die("الطلب غير موجود");
        }

        $items = $this->db->fetchAll("SELECT * FROM order_items WHERE order_id = :id", ['id' => $id]);

        require_once __DIR__ . '/../../views/pos/receipt.php';
    }

    /**
     * Return items from an order
     */
    public function returnOrder(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $orderId = (int)($input['order_id'] ?? 0);
        $items = $input['items'] ?? [];
        $reason = trim($input['reason'] ?? 'طلب العميل');
        $user = auth_user();

        if (!$orderId || empty($items)) {
            json_response(['success' => false, 'message' => 'بيانات المرتجع غير مكتملة']);
        }

        $result = $this->orderService->returnOrder($orderId, $items, $reason, $user['id']);
        json_response($result);
    }

    /**
     * Cashier Shift Performance & Cash Drawer Report API (JSON)
     */
    public function shiftReport(): void
    {
        $user = auth_user();
        $shiftId = (int)($_GET['shift_id'] ?? 0);

        if ($shiftId > 0) {
            $shift = $this->db->fetch("SELECT cs.*, u.name as cashier_name FROM cashier_shifts cs JOIN users u ON cs.user_id = u.id WHERE cs.id = :id", ['id' => $shiftId]);
        } else {
            $shift = get_active_shift($user['id'], $user['branch_id'] ?? 1);
            if (!$shift) {
                $shift = $this->db->fetch(
                    "SELECT cs.*, u.name as cashier_name FROM cashier_shifts cs JOIN users u ON cs.user_id = u.id WHERE cs.user_id = :uid ORDER BY cs.id DESC LIMIT 1",
                    ['uid' => $user['id']]
                );
            }
        }

        if (!$shift) {
            json_response(['success' => false, 'message' => 'لا توجد وردية مسجلة لهذا الكاشير']);
        }

        // Calculate work duration
        $startTs = strtotime($shift['opened_at']);
        $endTs = !empty($shift['closed_at']) ? strtotime($shift['closed_at']) : time();
        $diffSeconds = max(0, $endTs - $startTs);
        $hoursWorked = floor($diffSeconds / 3600);
        $minutesWorked = floor(($diffSeconds % 3600) / 60);

        // Fetch orders executed in this shift
        $orders = $this->db->fetchAll(
            "SELECT o.id, o.order_number, o.order_type, o.payment_method, o.total_amount, o.created_at, t.table_number 
             FROM orders o 
             LEFT JOIN dining_tables t ON o.table_id = t.id 
             WHERE o.shift_id = :sid AND o.status != 'cancelled' 
             ORDER BY o.id DESC",
            ['sid' => $shift['id']]
        );

        $ordersCount = count($orders);
        $openingCash = (float)$shift['opening_cash'];
        $cashSales = (float)$shift['total_cash_sales'];
        $cardSales = (float)$shift['total_card_sales'];
        $totalSales = (float)$shift['total_sales'];
        $returns = (float)$shift['total_returns'];

        // Expected cash drawer = Opening Cash + Cash Sales - Returns
        $expectedCashInDrawer = $openingCash + $cashSales - $returns;
        $actualCash = (float)($shift['closing_cash_actual'] ?? 0);
        $difference = (float)($shift['cash_difference'] ?? 0);

        json_response([
            'success' => true,
            'shift' => [
                'id' => $shift['id'],
                'cashier_name' => $shift['cashier_name'] ?? $user['name'],
                'status' => $shift['status'],
                'opened_at' => $shift['opened_at'],
                'closed_at' => $shift['closed_at'],
                'duration_text' => "{$hoursWorked} ساعة و {$minutesWorked} دقيقة",
                'orders_count' => $ordersCount,
                'total_sales' => $totalSales,
                'cash_sales' => $cashSales,
                'card_sales' => $cardSales,
                'total_returns' => $returns,
                'opening_cash' => $openingCash,
                'expected_cash' => $expectedCashInDrawer,
                'actual_cash' => $actualCash,
                'difference' => $difference,
                'notes' => $shift['notes'] ?? ''
            ],
            'orders' => $orders
        ]);
    }

    /**
     * Print Thermal Shift Settlement Receipt (Z-Report / X-Report)
     */
    public function printShiftReport(): void
    {
        $user = auth_user();
        $shiftId = (int)($_GET['shift_id'] ?? 0);

        if ($shiftId > 0) {
            $shift = $this->db->fetch(
                "SELECT cs.*, u.name as cashier_name, b.name as branch_name 
                 FROM cashier_shifts cs 
                 JOIN users u ON cs.user_id = u.id 
                 JOIN branches b ON cs.branch_id = b.id 
                 WHERE cs.id = :id",
                ['id' => $shiftId]
            );
        } else {
            $shift = get_active_shift($user['id'], $user['branch_id'] ?? 1);
            if (!$shift) {
                $shift = $this->db->fetch(
                    "SELECT cs.*, u.name as cashier_name, b.name as branch_name 
                     FROM cashier_shifts cs 
                     JOIN users u ON cs.user_id = u.id 
                     JOIN branches b ON cs.branch_id = b.id 
                     WHERE cs.user_id = :uid ORDER BY cs.id DESC LIMIT 1",
                    ['uid' => $user['id']]
                );
            }
        }

        if (!$shift) {
            die("الوردية غير موجودة");
        }

        $startTs = strtotime($shift['opened_at']);
        $endTs = !empty($shift['closed_at']) ? strtotime($shift['closed_at']) : time();
        $diffSeconds = max(0, $endTs - $startTs);
        $hoursWorked = floor($diffSeconds / 3600);
        $minutesWorked = floor(($diffSeconds % 3600) / 60);

        $orders = $this->db->fetchAll(
            "SELECT order_number, payment_method, total_amount, created_at 
             FROM orders 
             WHERE shift_id = :sid AND status != 'cancelled' 
             ORDER BY id DESC",
            ['sid' => $shift['id']]
        );

        $expectedCash = $shift['opening_cash'] + $shift['total_cash_sales'] - $shift['total_returns'];

        require_once __DIR__ . '/../../views/pos/shift_report_print.php';
    }
}
