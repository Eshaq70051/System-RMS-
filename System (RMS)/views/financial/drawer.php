<?php
$pageTitle = 'حركة الصندوق والخزينة (Cash Drawer)';
$pageSubtitle = 'كشف تفصيلي بجميع المقبوضات والمدفوعات النقدية وتسويات الورديات';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<!-- Drawer Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">إجمالي المقبوضات النقدية (IN)</span>
        <div class="text-2xl font-black text-emerald-600"><?= currency_format($totalIn) ?></div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">إجمالي المدفوعات والمصروفات (OUT)</span>
        <div class="text-2xl font-black text-rose-600"><?= currency_format($totalOut) ?></div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">الرصيد الصافي الفعلي بالصندوق</span>
        <div class="text-2xl font-black text-indigo-700"><?= currency_format($currentBalance) ?></div>
    </div>
</div>

<!-- Drawer Ledger Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h4 class="font-bold text-slate-800 text-sm">سجل العمليات النقدية بالصندوق (<?= count($transactions) ?> عملية)</h4>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="py-3.5 px-4">رقم الحركة</th>
                    <th class="py-3.5 px-4">نوع الحركة</th>
                    <th class="py-3.5 px-4">التصنيف</th>
                    <th class="py-3.5 px-4">المبلغ</th>
                    <th class="py-3.5 px-4">البيان والتفاصيل</th>
                    <th class="py-3.5 px-4">المستخدم المسؤول</th>
                    <th class="py-3.5 px-4">التاريخ والوقت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-10 text-slate-400">لا توجد حركات مسجلة بالصندوق</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-500">#<?= $t['id'] ?></td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $t['type'] === 'in' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                                    <?= $t['type'] === 'in' ? 'إيداع / قبض (IN)' : 'صرف / سحب (OUT)' ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700"><?= $t['category'] ?></td>
                            <td class="py-3.5 px-4 font-black <?= $t['type'] === 'in' ? 'text-emerald-600' : 'text-rose-600' ?>">
                                <?= $t['type'] === 'in' ? '+' : '-' ?><?= currency_format($t['amount']) ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700"><?= htmlspecialchars($t['description'] ?: '-') ?></td>
                            <td class="py-3.5 px-4 text-slate-500"><?= htmlspecialchars($t['user_name'] ?? 'النظام') ?></td>
                            <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px]"><?= date('Y-m-d H:i', strtotime($t['created_at'])) ?></td>
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
