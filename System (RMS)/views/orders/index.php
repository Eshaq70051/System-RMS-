<?php
$pageTitle = 'إدارة الطلبات والمبيعات';
$pageSubtitle = 'متابعة سجل الطلبات، الحالات، الفواتير والمرتجعات';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<!-- Filter Card -->
<div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-6">
    <form method="GET" action="<?= base_url('orders') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 text-xs font-semibold">
        
        <div>
            <label class="block text-slate-600 mb-1">بحث برقم الطلب / العميل</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ORD-2026..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
        </div>

        <div>
            <label class="block text-slate-600 mb-1">حالة الطلب</label>
            <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                <option value="">جميع الحالات</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>بانتظار التحضير</option>
                <option value="in_kitchen" <?= $status === 'in_kitchen' ? 'selected' : '' ?>>قيد الطهي</option>
                <option value="ready" <?= $status === 'ready' ? 'selected' : '' ?>>جاهز للتسليم</option>
                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>مكتمل</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>ملغي</option>
            </select>
        </div>

        <div>
            <label class="block text-slate-600 mb-1">نوع الطلب</label>
            <select name="order_type" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                <option value="">جميع الأنواع</option>
                <option value="dine_in" <?= $orderType === 'dine_in' ? 'selected' : '' ?>>داخل الصالة</option>
                <option value="takeaway" <?= $orderType === 'takeaway' ? 'selected' : '' ?>>سفري</option>
                <option value="delivery" <?= $orderType === 'delivery' ? 'selected' : '' ?>>توصيل</option>
            </select>
        </div>

        <div>
            <label class="block text-slate-600 mb-1">من تاريخ</label>
            <input type="date" name="date_from" value="<?= $dateFrom ?>" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
        </div>

        <div>
            <label class="block text-slate-600 mb-1">إلى تاريخ</label>
            <input type="date" name="date_to" value="<?= $dateTo ?>" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition shadow-sm">
                <i class="fa-solid fa-filter"></i> تصفية
            </button>
            <a href="<?= base_url('orders') ?>" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition" title="إعادة ضبط">
                <i class="fa-solid fa-rotate-left"></i>
            </a>
        </div>

    </form>
</div>

<!-- Orders Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h4 class="font-bold text-slate-800 text-sm">قائمة الطلبات (<?= count($orders) ?> طلب)</h4>
        <a href="<?= base_url('pos') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white text-xs font-bold rounded-xl shadow-sm hover:bg-emerald-700 transition">
            <i class="fa-solid fa-plus"></i>
            <span>طلب كاشير جديد</span>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                <tr>
                    <th class="py-3.5 px-4">رقم الطلب</th>
                    <th class="py-3.5 px-4">نوع الطلب</th>
                    <th class="py-3.5 px-4">العميل</th>
                    <th class="py-3.5 px-4">الكاشير / الفرع</th>
                    <th class="py-3.5 px-4">الإجمالي</th>
                    <th class="py-3.5 px-4">طريقة الدفع</th>
                    <th class="py-3.5 px-4">حالة الطلب</th>
                    <th class="py-3.5 px-4">التاريخ والوقت</th>
                    <th class="py-3.5 px-4 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-10 text-slate-400 font-medium">لا توجد طلبات تطابق معايير البحث المحددة</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-bold text-indigo-600">
                                <a href="<?= base_url('orders/' . $order['id']) ?>" class="hover:underline"><?= $order['order_number'] ?></a>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700">
                                <?php if ($order['order_type'] === 'dine_in'): ?>
                                    <span class="inline-flex items-center gap-1 text-indigo-700 font-bold">
                                        <i class="fa-solid fa-chair text-xs"></i>
                                        <span>طاولة <?= $order['table_number'] ?: '-' ?></span>
                                    </span>
                                <?php elseif ($order['order_type'] === 'takeaway'): ?>
                                    <span class="text-amber-600 font-bold">سفري</span>
                                <?php else: ?>
                                    <span class="text-blue-600 font-bold">توصيل</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700"><?= htmlspecialchars($order['customer_name'] ?? 'عميل صالة') ?></td>
                            <td class="py-3.5 px-4 text-slate-600">
                                <span class="font-semibold block"><?= htmlspecialchars($order['cashier_name']) ?></span>
                                <span class="text-[10px] text-slate-400"><?= htmlspecialchars($order['branch_name']) ?></span>
                            </td>
                            <td class="py-3.5 px-4 font-black text-slate-900"><?= currency_format($order['total_amount']) ?></td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $order['payment_method'] === 'cash' ? 'bg-emerald-100 text-emerald-800' : ($order['payment_method'] === 'card' ? 'bg-indigo-100 text-indigo-800' : 'bg-purple-100 text-purple-800') ?>">
                                    <?= $order['payment_method'] === 'cash' ? 'نقدي' : ($order['payment_method'] === 'card' ? 'شبكة' : 'مختلط') ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <?php 
                                $statusClass = match($order['status']) {
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'in_kitchen' => 'bg-blue-100 text-blue-800',
                                    'ready' => 'bg-teal-100 text-teal-800',
                                    'completed' => 'bg-emerald-100 text-emerald-800',
                                    'cancelled' => 'bg-rose-100 text-rose-800',
                                    default => 'bg-slate-100 text-slate-800'
                                };
                                $statusLabel = match($order['status']) {
                                    'pending' => 'انتظار',
                                    'in_kitchen' => 'في المطبخ',
                                    'ready' => 'جاهز',
                                    'completed' => 'مكتمل',
                                    'cancelled' => 'ملغي',
                                    default => $order['status']
                                };
                                ?>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $statusClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">
                                <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="<?= base_url('orders/' . $order['id']) ?>" class="p-1.5 text-slate-400 hover:text-indigo-600 transition" title="عرض التفاصيل">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="<?= base_url('pos/receipt/' . $order['id']) ?>" target="_blank" class="p-1.5 text-slate-400 hover:text-amber-600 transition" title="طباعة الفاتورة">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
