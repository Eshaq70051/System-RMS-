<?php
$pageTitle = 'تفاصيل الطلب #' . $order['order_number'];
$pageSubtitle = 'عرض شامل للبنود والمبالغ والحالة اللحظية للطلب';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header Actions -->
    <div class="flex items-center justify-between">
        <a href="<?= base_url('orders') ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-indigo-600 transition">
            <i class="fa-solid fa-arrow-right"></i>
            <span>العودة لقائمة الطلبات</span>
        </a>

        <div class="flex items-center gap-2">
            <a href="<?= base_url('pos/receipt/' . $order['id']) ?>" target="_blank" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-300 transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i>
                <span>طباعة الفاتورة الحرارية</span>
            </a>

            <?php if ($order['status'] !== 'cancelled'): ?>
                <form action="<?= base_url('orders/' . $order['id'] . '/cancel') ?>" method="POST" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الطلب واسترجاع المخزون؟')">
                    <?= csrf_field() ?>
                    <button type="submit" class="px-3.5 py-2 bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 font-bold text-xs rounded-xl transition flex items-center gap-1.5">
                        <i class="fa-solid fa-ban"></i>
                        <span>إلغاء الطلب</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Summary Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b border-slate-100 pb-4 mb-4 gap-3">
            <div>
                <span class="text-xs font-bold text-slate-400 block">رقم الطلب</span>
                <h3 class="text-xl font-black text-slate-900"><?= $order['order_number'] ?></h3>
            </div>
            
            <div class="flex items-center gap-2">
                <?php 
                $statusClass = match($order['status']) {
                    'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                    'in_kitchen' => 'bg-blue-100 text-blue-800 border-blue-200',
                    'ready' => 'bg-teal-100 text-teal-800 border-teal-200',
                    'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
                    default => 'bg-slate-100 text-slate-800 border-slate-200'
                };
                $statusLabel = match($order['status']) {
                    'pending' => 'بانتظار التحضير',
                    'in_kitchen' => 'قيد الطهي في المطبخ',
                    'ready' => 'جاهز للتسليم',
                    'completed' => 'مكتمل ومسدد',
                    'cancelled' => 'ملغي',
                    default => $order['status']
                };
                ?>
                <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $statusClass ?>">
                    <?= $statusLabel ?>
                </span>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs mb-6">
            <div class="p-3 bg-slate-50 rounded-xl">
                <span class="text-slate-400 block mb-0.5">نوع الطلب</span>
                <span class="font-bold text-slate-800">
                    <?= $order['order_type'] === 'dine_in' ? 'صالة - طاولة (' . ($order['table_number'] ?: '-') . ')' : ($order['order_type'] === 'takeaway' ? 'سفري' : 'توصيل') ?>
                </span>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
                <span class="text-slate-400 block mb-0.5">الكاشير المسئول</span>
                <span class="font-bold text-slate-800"><?= htmlspecialchars($order['cashier_name']) ?></span>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
                <span class="text-slate-400 block mb-0.5">تاريخ ووقت الإنشاء</span>
                <span class="font-bold text-slate-800 font-mono"><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
                <span class="text-slate-400 block mb-0.5">طريقة الدفع</span>
                <span class="font-bold text-indigo-700"><?= $order['payment_method'] === 'cash' ? 'نقدي (كاش)' : ($order['payment_method'] === 'card' ? 'شبكة / مدى' : 'دفع مختلط') ?></span>
            </div>
        </div>

        <!-- Items Table -->
        <div class="border border-slate-200 rounded-2xl overflow-hidden mb-6">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">الصنف</th>
                        <th class="py-3 px-4 text-center">الكمية</th>
                        <th class="py-3 px-4 text-left">سعر الوحدة</th>
                        <th class="py-3 px-4 text-left">الإجمالي</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-800 block"><?= htmlspecialchars($item['product_name']) ?></span>
                                <?php if ($item['notes']): ?>
                                    <span class="text-[10px] text-amber-600 block"><i class="fa-solid fa-pen"></i> <?= htmlspecialchars($item['notes']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-slate-700"><?= $item['quantity'] ?></td>
                            <td class="py-3 px-4 text-left text-slate-600"><?= number_format($item['unit_price'], 2) ?> <?= $currencySymbol ?></td>
                            <td class="py-3 px-4 text-left font-bold text-slate-900"><?= number_format($item['total_price'], 2) ?> <?= $currencySymbol ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals Breakdown -->
        <div class="flex justify-end">
            <div class="w-full sm:w-72 space-y-2 text-xs font-semibold text-slate-600">
                <div class="flex justify-between">
                    <span>المجموع الخاضع للضريبة:</span>
                    <span><?= currency_format($order['subtotal']) ?></span>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                    <div class="flex justify-between text-rose-600">
                        <span>الخصم المطبق:</span>
                        <span>-<?= currency_format($order['discount_amount']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($order['tax_amount'] > 0): ?>
                    <div class="flex justify-between text-slate-500">
                        <span>ضريبة القيمة المضافة (15%):</span>
                        <span><?= currency_format($order['tax_amount']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex justify-between text-sm font-black text-slate-900 pt-2 border-t border-slate-200">
                    <span>المجموع الإجمالي:</span>
                    <span class="text-indigo-600 text-base"><?= currency_format($order['total_amount']) ?></span>
                </div>
                <div class="flex justify-between text-slate-500 pt-1 text-[11px]">
                    <span>المبلغ المدفوع:</span>
                    <span><?= currency_format($order['paid_amount']) ?></span>
                </div>
                <div class="flex justify-between text-emerald-700 font-bold text-[11px]">
                    <span>المتبقي للعميل:</span>
                    <span><?= currency_format($order['change_amount']) ?></span>
                </div>
            </div>
        </div>

    </div>

</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
