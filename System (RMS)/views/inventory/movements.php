<?php
$pageTitle = 'سجل حركة المواد والمخزون';
$pageSubtitle = 'تتبع تفصيلي لجميع عمليات الصرف، الشراء، التسويات، والمرتجعات';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<!-- Filters -->
<div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-6">
    <form method="GET" action="<?= base_url('inventory/movements') ?>" class="flex flex-wrap items-center gap-3 text-xs font-semibold">
        <div>
            <label class="block text-slate-600 mb-1">المادة الخام</label>
            <select name="item_id" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                <option value="">جميع المواد</option>
                <?php foreach ($items as $it): ?>
                    <option value="<?= $it['id'] ?>" <?= ($itemId == $it['id']) ? 'selected' : '' ?>><?= htmlspecialchars($it['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-slate-600 mb-1">نوع الحركة</label>
            <select name="type" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                <option value="">جميع أنواع الحركات</option>
                <option value="sale_deduction" <?= $type === 'sale_deduction' ? 'selected' : '' ?>>خصم استهلاك مبيعات (وصفة)</option>
                <option value="purchase" <?= $type === 'purchase' ? 'selected' : '' ?>>توريد مشتريات</option>
                <option value="adjustment_in" <?= $type === 'adjustment_in' ? 'selected' : '' ?>>تسوية جرد بالزيادة</option>
                <option value="adjustment_out" <?= $type === 'adjustment_out' ? 'selected' : '' ?>>تسوية جرد بالنقصان</option>
                <option value="return" <?= $type === 'return' ? 'selected' : '' ?>>استرجاع مبيعات</option>
            </select>
        </div>

        <div class="flex items-end gap-2 pt-4">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold"><i class="fa-solid fa-filter"></i> تصفية</button>
            <a href="<?= base_url('inventory/movements') ?>" class="p-2 bg-slate-100 text-slate-600 rounded-xl"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
    </form>
</div>

<!-- Ledger Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="py-3.5 px-4">رقم الحركة</th>
                    <th class="py-3.5 px-4">المادة الخام</th>
                    <th class="py-3.5 px-4">نوع الحركة</th>
                    <th class="py-3.5 px-4">الكمية</th>
                    <th class="py-3.5 px-4">المرجع</th>
                    <th class="py-3.5 px-4">الملاحظات والبيان</th>
                    <th class="py-3.5 px-4">المستخدم</th>
                    <th class="py-3.5 px-4">التاريخ والوقت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($movements)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-10 text-slate-400">لا توجد حركات مخزنية مسجلة</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-600">#<?= $m['id'] ?></td>
                            <td class="py-3.5 px-4 font-bold text-slate-900"><?= htmlspecialchars($m['item_name']) ?></td>
                            <td class="py-3.5 px-4">
                                <?php
                                $badgeClass = match($m['type']) {
                                    'sale_deduction' => 'bg-rose-100 text-rose-800',
                                    'purchase' => 'bg-emerald-100 text-emerald-800',
                                    'adjustment_in' => 'bg-blue-100 text-blue-800',
                                    'adjustment_out' => 'bg-amber-100 text-amber-800',
                                    'return' => 'bg-purple-100 text-purple-800',
                                    default => 'bg-slate-100 text-slate-800'
                                };
                                $typeLabel = match($m['type']) {
                                    'sale_deduction' => 'استهلاك مبيعات',
                                    'purchase' => 'توريد مشتريات',
                                    'adjustment_in' => 'تسوية بالزيادة',
                                    'adjustment_out' => 'تسوية بالعجز',
                                    'return' => 'إرجاع مبيعات',
                                    default => $m['type']
                                };
                                ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $badgeClass ?>">
                                    <?= $typeLabel ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-black <?= $m['quantity'] < 0 ? 'text-rose-600' : 'text-emerald-600' ?>">
                                <?= $m['quantity'] > 0 ? '+' : '' ?><?= (float)$m['quantity'] ?> <?= $m['unit'] ?>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">
                                <?= $m['reference_type'] ?> #<?= $m['reference_id'] ?: '-' ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 max-w-xs truncate"><?= htmlspecialchars($m['notes'] ?? '-') ?></td>
                            <td class="py-3.5 px-4 text-slate-600"><?= htmlspecialchars($m['user_name'] ?? 'النظام') ?></td>
                            <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px]"><?= date('Y-m-d H:i', strtotime($m['created_at'])) ?></td>
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
