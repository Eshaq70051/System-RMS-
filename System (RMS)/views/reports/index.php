<?php
$pageTitle = 'التقارير والإحصائيات الشاملة';
$pageSubtitle = 'تحليلات المبيعات، تقارير الورديات، حركة المواد وتصدير البيانات إلى Excel';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<!-- Report Tabs & Filter Bar -->
<div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm mb-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 pb-4 mb-4">
        
        <!-- Tab Navigation -->
        <div class="flex items-center gap-2 text-xs font-bold">
            <a href="<?= base_url('reports?tab=sales') ?>" class="px-4 py-2 rounded-xl transition <?= $tab === 'sales' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-300' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                <i class="fa-solid fa-chart-line ml-1"></i> تقرير المبيعات
            </a>
            <a href="<?= base_url('reports?tab=shifts') ?>" class="px-4 py-2 rounded-xl transition <?= $tab === 'shifts' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-300' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                <i class="fa-solid fa-clock ml-1"></i> تقرير الورديات والكاشير
            </a>
            <a href="<?= base_url('reports?tab=inventory') ?>" class="px-4 py-2 rounded-xl transition <?= $tab === 'inventory' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-300' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                <i class="fa-solid fa-boxes-stacked ml-1"></i> تقييم المخزون
            </a>
        </div>

        <!-- Export Buttons -->
        <div class="flex items-center gap-2">
            <a href="<?= base_url("reports/export-sales?date_from={$dateFrom}&date_to={$dateTo}") ?>" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-file-excel"></i>
                <span>تصدير Excel (CSV)</span>
            </a>
            <button onclick="window.print()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i>
                <span>طباعة</span>
            </button>
        </div>
    </div>

    <!-- Date & Branch Filters -->
    <form method="GET" action="<?= base_url('reports') ?>" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs font-semibold">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <div>
            <label class="block text-slate-600 mb-1">الفرع</label>
            <select name="branch_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                <option value="">جميع الفروع</option>
                <?php foreach ($branches as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $branchId == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
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
        <div class="flex items-end">
            <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition shadow-sm">
                <i class="fa-solid fa-filter"></i> تطبيق التصفية
            </button>
        </div>
    </form>
</div>

<!-- Tab Content: Sales Report -->
<?php if ($tab === 'sales'): ?>
    
    <!-- Sales KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">عدد الطلبات</span>
            <div class="text-xl font-black text-slate-900"><?= $salesSummary['orders_count'] ?? 0 ?> طلب</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">إجمالي المبيعات</span>
            <div class="text-xl font-black text-indigo-700"><?= currency_format($salesSummary['total_revenue'] ?? 0) ?></div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">مبيعات نقدية (كاش)</span>
            <div class="text-xl font-black text-emerald-600"><?= currency_format($salesSummary['total_cash'] ?? 0) ?></div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 block mb-1">مبيعات شبكة ومدى</span>
            <div class="text-xl font-black text-blue-600"><?= currency_format($salesSummary['total_card'] ?? 0) ?></div>
        </div>
    </div>

    <!-- Daily Breakdown Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h4 class="font-bold text-slate-800 text-sm">حركة المبيعات اليومية خلال الفترة</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">التاريخ</th>
                        <th class="py-3.5 px-4">عدد العمليات</th>
                        <th class="py-3.5 px-4">المبيعات النقدية</th>
                        <th class="py-3.5 px-4">مبيعات الشبكة</th>
                        <th class="py-3.5 px-4">الإجمالي اليومي</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($salesDaily)): ?>
                        <tr><td colspan="5" class="text-center py-8 text-slate-400">لا توجد مبيعات في الفترة المحددة</td></tr>
                    <?php else: ?>
                        <?php foreach ($salesDaily as $sd): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4 font-bold text-slate-900"><?= $sd['sale_date'] ?></td>
                                <td class="py-3.5 px-4 font-semibold text-slate-600"><?= $sd['orders_count'] ?> طلب</td>
                                <td class="py-3.5 px-4 text-emerald-600 font-semibold"><?= currency_format($sd['cash_rev']) ?></td>
                                <td class="py-3.5 px-4 text-blue-600 font-semibold"><?= currency_format($sd['card_rev']) ?></td>
                                <td class="py-3.5 px-4 font-black text-indigo-700"><?= currency_format($sd['revenue']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($tab === 'shifts'): ?>

    <!-- Shifts Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h4 class="font-bold text-slate-800 text-sm">سجل ورديات الكاشير وتسويات الصندوق</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">رقم الوردية</th>
                        <th class="py-3.5 px-4">الكاشير</th>
                        <th class="py-3.5 px-4">وقت الفتح والإغلاق</th>
                        <th class="py-3.5 px-4">الافتتاحي</th>
                        <th class="py-3.5 px-4">مبيعات كاش</th>
                        <th class="py-3.5 px-4">مبيعات شبكة</th>
                        <th class="py-3.5 px-4">المتوقع بالصندوق</th>
                        <th class="py-3.5 px-4">الفعلي المحسوب</th>
                        <th class="py-3.5 px-4">الفارق (عجز/زيادة)</th>
                        <th class="py-3.5 px-4">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($shifts)): ?>
                        <tr><td colspan="10" class="text-center py-8 text-slate-400">لا توجد ورديات مسجلة</td></tr>
                    <?php else: ?>
                        <?php foreach ($shifts as $cs): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-indigo-600">#<?= $cs['id'] ?></td>
                                <td class="py-3.5 px-4 font-bold text-slate-800"><?= htmlspecialchars($cs['cashier_name']) ?></td>
                                <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">
                                    <div>بدء: <?= date('d/m H:i', strtotime($cs['opened_at'])) ?></div>
                                    <div>إغلاق: <?= $cs['closed_at'] ? date('d/m H:i', strtotime($cs['closed_at'])) : '<span class="text-emerald-600 font-bold">مفتوحة حالياً</span>' ?></div>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-700"><?= currency_format($cs['opening_cash']) ?></td>
                                <td class="py-3.5 px-4 text-emerald-600 font-bold"><?= currency_format($cs['total_cash_sales']) ?></td>
                                <td class="py-3.5 px-4 text-blue-600 font-bold"><?= currency_format($cs['total_card_sales']) ?></td>
                                <td class="py-3.5 px-4 font-bold text-slate-800"><?= currency_format($cs['expected_cash']) ?></td>
                                <td class="py-3.5 px-4 font-black text-slate-900"><?= currency_format($cs['closing_cash_actual']) ?></td>
                                <td class="py-3.5 px-4">
                                    <?php if ($cs['status'] === 'closed'): ?>
                                        <span class="font-black <?= $cs['cash_difference'] < 0 ? 'text-rose-600' : ($cs['cash_difference'] > 0 ? 'text-emerald-600' : 'text-slate-700') ?>">
                                            <?= $cs['cash_difference'] > 0 ? '+' : '' ?><?= currency_format($cs['cash_difference']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $cs['status'] === 'open' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' ?>">
                                        <?= $cs['status'] === 'open' ? 'مفتوحة' : 'مغلقة' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($tab === 'inventory'): ?>

    <!-- Inventory Valuation Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h4 class="font-bold text-slate-800 text-sm">تقرير تقييم قيمة المخزون الحالي بالمستودع</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">المادة الخام</th>
                        <th class="py-3.5 px-4">الرصيد المتوفر</th>
                        <th class="py-3.5 px-4">حد التنبيه</th>
                        <th class="py-3.5 px-4">تكلفة الوحدة</th>
                        <th class="py-3.5 px-4">إجمالي القيمة التقديرية</th>
                        <th class="py-3.5 px-4">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($inventoryStats as $is): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-900"><?= htmlspecialchars($is['name']) ?></td>
                            <td class="py-3.5 px-4 font-black text-slate-800"><?= (float)$is['current_stock'] ?> <?= $is['unit'] ?></td>
                            <td class="py-3.5 px-4 text-slate-400"><?= (float)$is['min_stock_alert'] ?> <?= $is['unit'] ?></td>
                            <td class="py-3.5 px-4 text-slate-600 font-semibold"><?= currency_format($is['cost_per_unit']) ?></td>
                            <td class="py-3.5 px-4 font-black text-indigo-700"><?= currency_format($is['valuation']) ?></td>
                            <td class="py-3.5 px-4">
                                <?php if ($is['current_stock'] <= $is['min_stock_alert']): ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">ناقص ⚠️</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">آمن ✓</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
