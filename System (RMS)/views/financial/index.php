<?php
$pageTitle = 'الإدارة المالية وقائمة الأرباح والخسائر';
$pageSubtitle = 'المحاسبة الدقيقة، تكلفة البضاعة المباعة (COGS)، وصافي الأرباح الحقيقية';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<!-- Date Filter Header -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <form method="GET" action="<?= base_url('financial') ?>" class="flex items-center gap-2 text-xs font-semibold">
        <label class="text-slate-600">الفترة المالية من:</label>
        <input type="date" name="date_from" value="<?= $dateFrom ?>" class="px-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none">
        <label class="text-slate-600">إلى:</label>
        <input type="date" name="date_to" value="<?= $dateTo ?>" class="px-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none">
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl"><i class="fa-solid fa-filter"></i> عرض القائمة</button>
    </form>

    <div class="flex items-center gap-2">
        <a href="<?= base_url('expenses') ?>" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition flex items-center gap-1.5">
            <i class="fa-solid fa-money-bill-transfer text-rose-600"></i>
            <span>إدارة المصروفات</span>
        </a>
        <a href="<?= base_url('drawer') ?>" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition flex items-center gap-1.5">
            <i class="fa-solid fa-vault text-emerald-600"></i>
            <span>حركة الصندوق (الخزينة)</span>
        </a>
    </div>
</div>

<!-- Primary Financial Statement Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-6">
    <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
        <span class="text-xs font-bold text-slate-400 block mb-1">صافي الإيرادات (المبيعات)</span>
        <div class="text-2xl font-black text-slate-900"><?= currency_format($netSales) ?></div>
        <span class="text-[11px] text-slate-400 mt-1 block">إجمالي المبيعات مطروحاً منها المرتجعات</span>
    </div>

    <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
        <span class="text-xs font-bold text-slate-400 block mb-1">تكلفة البضاعة المباعة (COGS)</span>
        <div class="text-2xl font-black text-rose-600"><?= currency_format($cogs) ?></div>
        <span class="text-[11px] text-slate-400 mt-1 block">استهلاك المواد الخام للوجبات المباعة</span>
    </div>

    <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
        <span class="text-xs font-bold text-slate-400 block mb-1">إجمالي الربح التجاري (Gross)</span>
        <div class="text-2xl font-black text-indigo-700"><?= currency_format($grossProfit) ?></div>
        <span class="text-[11px] text-indigo-600 font-bold mt-1 block">هامش إجمالي: <?= $grossMargin ?>%</span>
    </div>

    <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
        <span class="text-xs font-bold text-slate-400 block mb-1">صافي الربح التشغيلي (Net)</span>
        <div class="text-2xl font-black <?= $netProfit >= 0 ? 'text-emerald-600' : 'text-rose-600' ?>"><?= currency_format($netProfit) ?></div>
        <span class="text-[11px] font-bold <?= $netProfit >= 0 ? 'text-emerald-600' : 'text-rose-600' ?> mt-1 block">هامش صافي: <?= $netMargin ?>%</span>
    </div>
</div>

<!-- Detailed P&L Income Statement Breakdown -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Income Statement Sheet (2 Cols) -->
    <div class="lg:col-span-2 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <h4 class="font-black text-slate-800 text-base mb-1">قائمة الأرباح والخسائر الرسمية (Income Statement)</h4>
        <p class="text-xs text-slate-400 mb-5">عن الفترة من <?= $dateFrom ?> إلى <?= $dateTo ?></p>

        <div class="space-y-3 text-xs font-semibold">
            
            <!-- Revenue Section -->
            <div class="p-3 bg-slate-50 rounded-2xl space-y-2">
                <span class="text-xs font-bold text-indigo-700 block uppercase">1. الإيرادات التشغيلية</span>
                <div class="flex justify-between text-slate-700">
                    <span>إجمالي مبيعات الصالة والسفري والتوصيل:</span>
                    <span><?= currency_format($grossSales) ?></span>
                </div>
                <div class="flex justify-between text-rose-600">
                    <span>مرتجعات المبيعات والخصومات:</span>
                    <span>-<?= currency_format($returns) ?></span>
                </div>
                <div class="flex justify-between font-black text-slate-900 pt-2 border-t border-slate-200 text-sm">
                    <span>صافي المبيعات المحققة:</span>
                    <span><?= currency_format($netSales) ?></span>
                </div>
            </div>

            <!-- Cost of Sales Section -->
            <div class="p-3 bg-slate-50 rounded-2xl space-y-2">
                <span class="text-xs font-bold text-rose-700 block uppercase">2. تكلفة المبيعات (المواد الخام)</span>
                <div class="flex justify-between text-slate-700">
                    <span>تكلفة المواد الخام المستهلكة في الوجبات (COGS):</span>
                    <span class="text-rose-600 font-bold">-<?= currency_format($cogs) ?></span>
                </div>
                <div class="flex justify-between font-black text-indigo-700 pt-2 border-t border-slate-200 text-sm">
                    <span>إجمالي الربح قبل المصاريف (Gross Profit):</span>
                    <span><?= currency_format($grossProfit) ?></span>
                </div>
            </div>

            <!-- Operating Expenses Section -->
            <div class="p-3 bg-slate-50 rounded-2xl space-y-2">
                <span class="text-xs font-bold text-amber-700 block uppercase">3. المصروفات التشغيلية والإدارية</span>
                <?php if (empty($expensesByCategory)): ?>
                    <p class="text-slate-400 text-xs">لا توجد مصروفات مسجلة خلال هذه الفترة</p>
                <?php else: ?>
                    <?php foreach ($expensesByCategory as $expCat): ?>
                        <div class="flex justify-between text-slate-600">
                            <span><?= htmlspecialchars($expCat['category_name']) ?>:</span>
                            <span class="text-rose-600">-<?= currency_format($expCat['total_amount']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="flex justify-between font-black text-rose-700 pt-2 border-t border-slate-200 text-sm">
                    <span>إجمالي المصروفات:</span>
                    <span>-<?= currency_format($totalExpenses) ?></span>
                </div>
            </div>

            <!-- Bottom Line Net Profit -->
            <div class="p-4 rounded-2xl border-2 <?= $netProfit >= 0 ? 'bg-emerald-50 border-emerald-300' : 'bg-rose-50 border-rose-300' ?> flex items-center justify-between text-base font-black">
                <span class="<?= $netProfit >= 0 ? 'text-emerald-800' : 'text-rose-800' ?>">صافي الربح الفعلي بعد كافة التكاليف (Net Profit):</span>
                <span class="<?= $netProfit >= 0 ? 'text-emerald-700' : 'text-rose-700' ?> text-xl"><?= currency_format($netProfit) ?></span>
            </div>

        </div>
    </div>

    <!-- Right Summary: Drawer Cash & Balances (1 Col) -->
    <div class="space-y-6">
        
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <h4 class="font-black text-slate-800 text-sm mb-1 flex items-center gap-2">
                <i class="fa-solid fa-vault text-amber-500"></i>
                <span>الرصيد الفعلي للصندوق (الخزينة)</span>
            </h4>
            <p class="text-xs text-slate-400 mb-4">صافي النقد المتوفر في أدراج الكاشير والخزينة الآن</p>

            <div class="text-3xl font-black text-slate-900 mb-4">
                <?= currency_format($currentDrawerBalance) ?>
            </div>

            <div class="space-y-2 text-xs font-semibold pt-3 border-t border-slate-100">
                <div class="flex justify-between text-emerald-600">
                    <span>إجمالي المقبوضات النقدية (IN):</span>
                    <span><?= currency_format($cashIn) ?></span>
                </div>
                <div class="flex justify-between text-rose-600">
                    <span>إجمالي المصروفات النقدية (OUT):</span>
                    <span><?= currency_format($cashOut) ?></span>
                </div>
            </div>

            <a href="<?= base_url('drawer') ?>" class="block w-full text-center mt-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                عرض كشف حركة الصندوق التفصيلي
            </a>
        </div>

    </div>

</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
