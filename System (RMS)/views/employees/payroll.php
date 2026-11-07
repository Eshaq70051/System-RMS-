<?php
$pageTitle = 'مسيرات الرواتب الشهرية للموظفين';
$pageSubtitle = 'حساب الراتب الأساسي، البدلات، الخصومات والسلف وصافي الراتب المستحق';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<!-- Month & Year Filter -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <form method="GET" action="<?= base_url('payroll') ?>" class="flex items-center gap-2 text-xs font-semibold">
        <label class="text-slate-600">الشهر:</label>
        <select name="month" onchange="this.form.submit()" class="px-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= $m ?> / <?= date('F', mktime(0, 0, 0, $m, 10)) ?></option>
            <?php endfor; ?>
        </select>

        <label class="text-slate-600 mr-2">السنة:</label>
        <select name="year" onchange="this.form.submit()" class="px-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none">
            <option value="2026" selected>2026</option>
            <option value="2025">2025</option>
        </select>
    </form>

    <button onclick="openPayrollModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-300 transition flex items-center gap-1.5">
        <i class="fa-solid fa-file-invoice-dollar"></i>
        <span>صرف مسير راتب موظف</span>
    </button>
</div>

<!-- Payroll Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="py-3.5 px-4">الموظف</th>
                    <th class="py-3.5 px-4">الوظيفة</th>
                    <th class="py-3.5 px-4">الراتب الأساسي</th>
                    <th class="py-3.5 px-4">المكافآت والبدلات</th>
                    <th class="py-3.5 px-4">الخصومات والغياب</th>
                    <th class="py-3.5 px-4">السلف</th>
                    <th class="py-3.5 px-4">صافي الراتب المستحق</th>
                    <th class="py-3.5 px-4">تاريخ الصرف</th>
                    <th class="py-3.5 px-4">حالة المسير</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($payrolls)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-10 text-slate-400">لا توجد مسيرات رواتب مسجلة لهذا الشهر بعد</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payrolls as $pr): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-900"><?= htmlspecialchars($pr['full_name']) ?></td>
                            <td class="py-3.5 px-4 text-slate-600"><?= htmlspecialchars($pr['job_title']) ?></td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700"><?= number_format($pr['basic_salary'], 2) ?> <?= $currencySymbol ?></td>
                            <td class="py-3.5 px-4 font-bold text-emerald-600">+<?= number_format($pr['bonuses'], 2) ?> <?= $currencySymbol ?></td>
                            <td class="py-3.5 px-4 font-bold text-rose-600">-<?= number_format($pr['deductions'], 2) ?> <?= $currencySymbol ?></td>
                            <td class="py-3.5 px-4 font-bold text-amber-600">-<?= number_format($pr['advances'], 2) ?> <?= $currencySymbol ?></td>
                            <td class="py-3.5 px-4 font-black text-indigo-700 text-sm"><?= number_format($pr['net_salary'], 2) ?> <?= $currencySymbol ?></td>
                            <td class="py-3.5 px-4 font-mono text-slate-500"><?= $pr['payment_date'] ?: '-' ?></td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">معتمد ومصروف ✓</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Generate Payroll Modal -->
<div id="payrollModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <h4 class="font-black text-slate-800 text-base mb-1">صرف مسير راتب موظف</h4>
        <p class="text-xs text-slate-400 mb-4">حدد الموظف وقيمة البدلات والخصومات لاحتساب الصافي تلقائياً</p>

        <form action="<?= base_url('payroll') ?>" method="POST" class="space-y-4 text-xs font-semibold">
            <?= csrf_field() ?>
            <input type="hidden" name="month" value="<?= $month ?>">
            <input type="hidden" name="year" value="<?= $year ?>">

            <div>
                <label class="block text-slate-700 mb-1">الموظف <span class="text-rose-500">*</span></label>
                <select name="employee_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- اختر الموظف --</option>
                    <?php foreach ($employees as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?> (راتب أساسي: <?= $e['salary'] ?> <?= $currencySymbol ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">مكافآت وبدلات</label>
                    <input type="number" step="0.5" name="bonuses" value="0.00" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">خصومات وغياب</label>
                    <input type="number" step="0.5" name="deductions" value="0.00" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">سلف مستردة</label>
                    <input type="number" step="0.5" name="advances" value="0.00" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">ملاحظات المسير</label>
                <input type="text" name="notes" placeholder="بيان الراتب..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="button" onclick="closePayrollModal()" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</button>
                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-300">اعتماد وصرف الراتب</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openPayrollModal() { document.getElementById('payrollModal').classList.remove('hidden'); }
    function closePayrollModal() { document.getElementById('payrollModal').classList.add('hidden'); }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
