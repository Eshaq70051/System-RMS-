<?php
$pageTitle = 'المشتريات وفواتير المواد الخام';
$pageSubtitle = 'إدارة فواتير التوريد، استلام البضائع، وتحديث المخزون التلقائي';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<!-- Header Actions -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-2">
        <form method="GET" action="<?= base_url('purchases') ?>" class="flex items-center gap-2">
            <select name="supplier_id" onchange="this.form.submit()" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none font-semibold">
                <option value="">جميع الموردين</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($supplierId == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['company_name']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="flex items-center gap-2">
        <a href="<?= base_url('suppliers') ?>" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition flex items-center gap-1.5">
            <i class="fa-solid fa-truck-field"></i>
            <span>إدارة الموردين</span>
        </a>
        <a href="<?= base_url('purchases/create') ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-300 transition flex items-center gap-1.5">
            <i class="fa-solid fa-plus"></i>
            <span>فاتورة شراء جديدة</span>
        </a>
    </div>
</div>

<!-- Purchases Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="py-3.5 px-4">رقم الفاتورة</th>
                    <th class="py-3.5 px-4">المورد والشركة</th>
                    <th class="py-3.5 px-4">تاريخ الشراء</th>
                    <th class="py-3.5 px-4">إجمالي الفاتورة</th>
                    <th class="py-3.5 px-4">المسدد</th>
                    <th class="py-3.5 px-4">المتبقي (آجل)</th>
                    <th class="py-3.5 px-4">حالة السداد</th>
                    <th class="py-3.5 px-4">المستودع</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($purchases)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-10 text-slate-400">لا توجد فواتير مشتريات مسجلة</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($purchases as $po): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-bold font-mono text-indigo-600"><?= htmlspecialchars($po['invoice_number']) ?></td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 block"><?= htmlspecialchars($po['company_name'] ?: $po['supplier_name']) ?></span>
                                <span class="text-[10px] text-slate-400"><?= htmlspecialchars($po['supplier_name']) ?></span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-600"><?= $po['purchase_date'] ?></td>
                            <td class="py-3.5 px-4 font-black text-slate-900"><?= currency_format($po['grand_total']) ?></td>
                            <td class="py-3.5 px-4 font-bold text-emerald-600"><?= currency_format($po['paid_amount']) ?></td>
                            <td class="py-3.5 px-4 font-bold text-rose-600"><?= currency_format($po['remaining_amount']) ?></td>
                            <td class="py-3.5 px-4">
                                <?php
                                $payBadge = match($po['payment_status']) {
                                    'paid' => 'bg-emerald-100 text-emerald-800',
                                    'partial' => 'bg-amber-100 text-amber-800',
                                    default => 'bg-rose-100 text-rose-800'
                                };
                                $payLabel = match($po['payment_status']) {
                                    'paid' => 'مدفوع بالكامل',
                                    'partial' => 'مسدد جزئياً',
                                    default => 'غير مسدد (آجل)'
                                };
                                ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $payBadge ?>">
                                    <?= $payLabel ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 flex items-center gap-1 w-max">
                                    <i class="fa-solid fa-check"></i> تم التوريد للمخزون
                                </span>
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
