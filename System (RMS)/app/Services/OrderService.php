<?php

namespace App\Services;

use App\Models\Database;
use Exception;

class OrderService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Creates a complete order with transactional integrity,
     * deducting inventory via recipes and logging financial movements.
     */
    public function createOrder(array $data, array $items): array
    {
        $this->db->beginTransaction();

        try {
            $branchId = (int)($data['branch_id'] ?? 1);
            $userId = (int)($data['user_id'] ?? 1);
            $shiftId = !empty($data['shift_id']) ? (int)$data['shift_id'] : null;
            $tableId = !empty($data['table_id']) ? (int)$data['table_id'] : null;
            $orderType = $data['order_type'] ?? 'dine_in';
            $customerName = $data['customer_name'] ?? 'عميل صالة';
            $customerPhone = $data['customer_phone'] ?? '';
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $paidAmount = (float)($data['paid_amount'] ?? 0);
            $discountValue = (float)($data['discount_value'] ?? 0);
            $kitchenNotes = $data['kitchen_notes'] ?? '';

            // Generate unique Order Number
            $prefix = get_setting('order_prefix', 'ORD-');
            $datePart = date('Ymd');
            $countToday = $this->db->fetchValue(
                "SELECT COUNT(*) FROM orders WHERE created_at >= date('now', 'start of day')"
            ) + 1;
            $orderNumber = sprintf("%s%s-%04d", $prefix, $datePart, $countToday);

            // Calculate totals
            $subtotal = 0.0;
            $totalCost = 0.0;
            $processedItems = [];

            foreach ($items as $item) {
                $product = $this->db->fetch("SELECT * FROM products WHERE id = :id", ['id' => $item['product_id']]);
                if (!$product) {
                    throw new Exception("المنتج رقم {$item['product_id']} غير موجود.");
                }

                $qty = (int)($item['quantity'] ?? 1);
                $unitPrice = (float)($item['unit_price'] ?? $product['price']);
                $costPrice = (float)($product['cost_price'] ?? 0);
                $itemTotal = $unitPrice * $qty;

                $subtotal += $itemTotal;
                $totalCost += ($costPrice * $qty);

                $processedItems[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'unit_price' => $unitPrice,
                    'cost_price' => $costPrice,
                    'quantity' => $qty,
                    'total_price' => $itemTotal,
                    'notes' => $item['notes'] ?? '',
                    'addons_json' => !empty($item['addons']) ? json_encode($item['addons'], JSON_UNESCAPED_UNICODE) : null,
                    'has_recipe' => $product['has_recipe']
                ];
            }

            // Tax and Discount calculation
            $discountAmount = min($discountValue, $subtotal);
            $afterDiscount = max(0, $subtotal - $discountAmount);

            $taxRate = (float)get_setting('tax_rate', 15.0);
            $taxEnabled = (int)get_setting('tax_enabled', 1);
            $taxAmount = $taxEnabled ? round(($afterDiscount * ($taxRate / 100)), 2) : 0.00;
            $grandTotal = round($afterDiscount + $taxAmount, 2);

            $changeAmount = max(0, $paidAmount - $grandTotal);
            $cashAmount = 0.0;
            $cardAmount = 0.0;

            if ($paymentMethod === 'cash') {
                $cashAmount = $grandTotal;
            } elseif ($paymentMethod === 'card') {
                $cardAmount = $grandTotal;
            } elseif ($paymentMethod === 'split') {
                $cashAmount = (float)($data['cash_amount'] ?? 0);
                $cardAmount = (float)($data['card_amount'] ?? 0);
            }

            // Insert Order
            $orderId = $this->db->insert('orders', [
                'order_number' => $orderNumber,
                'branch_id' => $branchId,
                'shift_id' => $shiftId,
                'user_id' => $userId,
                'table_id' => $tableId,
                'order_type' => $orderType,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'delivery_address' => $data['delivery_address'] ?? null,
                'status' => 'pending',
                'kitchen_status' => 'waiting',
                'kitchen_notes' => $kitchenNotes,
                'subtotal' => $subtotal,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $grandTotal,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $paymentMethod,
                'cash_amount' => $cashAmount,
                'card_amount' => $cardAmount,
                'is_paid' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Insert Order Items & Deduct Inventory via Recipe
            $enableRecipeDeduction = (int)get_setting('enable_recipe_deduction', 1);

            foreach ($processedItems as $pItem) {
                $this->db->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $pItem['product_id'],
                    'product_name' => $pItem['product_name'],
                    'unit_price' => $pItem['unit_price'],
                    'cost_price' => $pItem['cost_price'],
                    'quantity' => $pItem['quantity'],
                    'total_price' => $pItem['total_price'],
                    'notes' => $pItem['notes'],
                    'addons_json' => $pItem['addons_json'],
                    'status' => 'pending'
                ]);

                // Recipe Inventory Deduction
                if ($enableRecipeDeduction && $pItem['has_recipe']) {
                    $recipe = $this->db->fetch("SELECT id FROM recipes WHERE product_id = :pid", ['pid' => $pItem['product_id']]);
                    if ($recipe) {
                        $ingredients = $this->db->fetchAll(
                            "SELECT * FROM recipe_ingredients WHERE recipe_id = :rid",
                            ['rid' => $recipe['id']]
                        );

                        foreach ($ingredients as $ing) {
                            $totalDeductQty = $ing['quantity_required'] * $pItem['quantity'];

                            // Deduct from inventory
                            $this->db->query(
                                "UPDATE inventory_items SET current_stock = current_stock - :qty, updated_at = datetime('now') WHERE id = :id",
                                ['qty' => $totalDeductQty, 'id' => $ing['inventory_item_id']]
                            );

                            // Log stock movement
                            $this->db->insert('stock_movements', [
                                'inventory_item_id' => $ing['inventory_item_id'],
                                'branch_id' => $branchId,
                                'type' => 'sale_deduction',
                                'quantity' => -$totalDeductQty,
                                'unit_cost' => 0,
                                'total_cost' => 0,
                                'reference_type' => 'order',
                                'reference_id' => $orderId,
                                'notes' => "خصم استهلاك وصفة تلقائي لطلب مبيعات #{$orderNumber} - صنف: {$pItem['product_name']}",
                                'created_by' => $userId
                            ]);
                        }
                    }
                }
            }

            // Update Dining Table status if dine_in
            if ($orderType === 'dine_in' && $tableId) {
                $this->db->update('dining_tables', [
                    'status' => 'occupied',
                    'current_order_id' => $orderId,
                    'updated_at' => date('Y-m-d H:i:s')
                ], "id = :tid", ['tid' => $tableId]);
            }

            // Update Cashier Shift sales & Drawer transactions
            if ($shiftId) {
                $this->db->query(
                    "UPDATE cashier_shifts SET 
                        total_sales = total_sales + :total,
                        total_cash_sales = total_cash_sales + :cash,
                        total_card_sales = total_card_sales + :card
                     WHERE id = :sid",
                    [
                        'total' => $grandTotal,
                        'cash' => $cashAmount,
                        'card' => $cardAmount,
                        'sid' => $shiftId
                    ]
                );

                if ($cashAmount > 0) {
                    $this->db->insert('cash_drawer_transactions', [
                        'branch_id' => $branchId,
                        'shift_id' => $shiftId,
                        'type' => 'in',
                        'amount' => $cashAmount,
                        'category' => 'sale',
                        'reference_type' => 'order',
                        'reference_id' => $orderId,
                        'description' => "مبيعات نقدية للطلب #{$orderNumber}",
                        'created_by' => $userId
                    ]);
                }
            }

            log_audit('انشاء طلب مبيعات', 'pos', "تم إنشاء الطلب رقم {$orderNumber} بقيمة {$grandTotal} ر.س");

            $this->db->commit();

            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'grand_total' => $grandTotal,
                'change_amount' => $changeAmount,
                'message' => 'تم إنشاء الطلب بنجاح وترحيل المخزون والصندوق'
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'خطأ أثناء تنفيذ الطلب: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process return/refund for an order
     */
    public function returnOrder(int $orderId, array $returnItems, string $reason, int $userId): array
    {
        $this->db->beginTransaction();

        try {
            $order = $this->db->fetch("SELECT * FROM orders WHERE id = :id", ['id' => $orderId]);
            if (!$order) {
                throw new Exception("الطلب غير موجود");
            }

            $returnNumber = 'RET-' . date('Ymd') . '-' . rand(1000, 9999);
            $totalRefund = 0.0;

            foreach ($returnItems as $item) {
                $orderItem = $this->db->fetch(
                    "SELECT * FROM order_items WHERE id = :id AND order_id = :oid",
                    ['id' => $item['order_item_id'], 'oid' => $orderId]
                );
                if (!$orderItem) {
                    continue;
                }

                $qty = min((int)$item['quantity'], $orderItem['quantity']);
                $refund = $qty * $orderItem['unit_price'];
                $totalRefund += $refund;
            }

            if ($totalRefund <= 0) {
                throw new Exception("لم يتم تحديد كميات صالحة للإرجاع");
            }

            // Insert return header
            $returnId = $this->db->insert('order_returns', [
                'order_id' => $orderId,
                'branch_id' => $order['branch_id'],
                'return_number' => $returnNumber,
                'refund_amount' => $totalRefund,
                'refund_method' => $order['payment_method'],
                'reason' => $reason,
                'returned_by' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Insert return items & restore raw materials if required
            foreach ($returnItems as $item) {
                $orderItem = $this->db->fetch("SELECT * FROM order_items WHERE id = :id", ['id' => $item['order_item_id']]);
                if (!$orderItem) continue;

                $qty = min((int)$item['quantity'], $orderItem['quantity']);
                $refund = $qty * $orderItem['unit_price'];

                $this->db->insert('order_return_items', [
                    'order_return_id' => $returnId,
                    'order_item_id' => $orderItem['id'],
                    'product_id' => $orderItem['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $orderItem['unit_price'],
                    'total_refund' => $refund,
                    'restore_stock' => 1
                ]);

                // Restore stock from recipe
                $recipe = $this->db->fetch("SELECT id FROM recipes WHERE product_id = :pid", ['pid' => $orderItem['product_id']]);
                if ($recipe) {
                    $ingredients = $this->db->fetchAll("SELECT * FROM recipe_ingredients WHERE recipe_id = :rid", ['rid' => $recipe['id']]);
                    foreach ($ingredients as $ing) {
                        $restoreQty = $ing['quantity_required'] * $qty;
                        $this->db->query(
                            "UPDATE inventory_items SET current_stock = current_stock + :qty WHERE id = :id",
                            ['qty' => $restoreQty, 'id' => $ing['inventory_item_id']]
                        );

                        $this->db->insert('stock_movements', [
                            'inventory_item_id' => $ing['inventory_item_id'],
                            'branch_id' => $order['branch_id'],
                            'type' => 'return',
                            'quantity' => $restoreQty,
                            'reference_type' => 'order_return',
                            'reference_id' => $returnId,
                            'notes' => "إرجاع مخزون لوصفة بناءً على مرتجع الطلب #{$returnNumber}",
                            'created_by' => $userId
                        ]);
                    }
                }
            }

            // Update shift returns
            if ($order['shift_id']) {
                $this->db->query(
                    "UPDATE cashier_shifts SET total_returns = total_returns + :ret WHERE id = :sid",
                    ['ret' => $totalRefund, 'sid' => $order['shift_id']]
                );

                $this->db->insert('cash_drawer_transactions', [
                    'branch_id' => $order['branch_id'],
                    'shift_id' => $order['shift_id'],
                    'type' => 'out',
                    'amount' => $totalRefund,
                    'category' => 'return',
                    'reference_type' => 'order_return',
                    'reference_id' => $returnId,
                    'description' => "صرف مرتجع مبيعات للطلب #{$order['order_number']}",
                    'created_by' => $userId
                ]);
            }

            log_audit('مرتجع مبيعات', 'pos', "إرجاع بقيمة {$totalRefund} ر.س للطلب #{$order['order_number']}");

            $this->db->commit();
            return ['success' => true, 'message' => 'تم تسجيل المرتجع واستعادة المخزون بنجاح'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'خطأ أثناء تسجيل المرتجع: ' . $e->getMessage()];
        }
    }
}
