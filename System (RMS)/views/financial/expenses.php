<?php
$pageTitle = 'المصروفات التشغيلية والنفقات';
$pageSubtitle = 'تسجيل ومتابعة مصاريف الإيجار، الطاقة، الصيانة، والمستلزمات';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<!-- Header Actions -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <form method="GET" action="<?= base_url('expenses') ?>" class="flex flex-wrap items-center gap-2 text-xs font-semibold">
        <select name="category_id" onchange="this.form.submit()" class="px-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none">
            <option value="">جميع فئات المصروفات</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($catId == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" value="<?= $dateFrom ?>" class="px-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none">
        <input type="date" name="date_to" value="<?= $dateTo ?>" class="px-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none">
        <button type="submit" class="px-3 py-2 bg-slate-800 text-white rounded-xl"><i class="fa-solid fa-filter"></i></button>
    </form>

    <button onclick="openAddExpenseModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-300 transition flex items-center gap-1.5">
        <i class="fa-solid fa-plus"></i>
        <span>تسجيل مصروف جديد</span>
    </button>
</div>

<!-- Expenses Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="py-3.5 px-4">عنوان المصروف والبيان</th>
                    <th class="py-3.5 px-4">الفئة والتصنيف</th>
                    <th class="py-3.5 px-4">المبلغ</th>
                    <th class="py-3.5 px-4">طريقة الدفع</th>
                    <th class="py-3.5 px-4">الجهة المدفوع لها</th>
                    <th class="py-3.5 px-4">المستخدم</th>
                    <th class="py-3.5 px-4">التاريخ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-10 text-slate-400">لا توجد مصروفات مسجلة لهذه الفترة</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($expenses as $exp): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 block"><?= htmlspecialchars($exp['title']) ?></span>
                                <?php if ($exp['notes']): ?>
                                    <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($exp['notes']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                    <?= htmlspecialchars($exp['category_name']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-black text-rose-600 text-sm"><?= currency_format($exp['amount']) ?></td>
                            <td class="py-3.5 px-4 font-bold text-slate-600"><?= $exp['payment_method'] === 'cash' ? 'نقدي (من الصندوق)' : 'بنكي / سداد' ?></td>
                            <td class="py-3.5 px-4 text-slate-700"><?= htmlspecialchars($exp['paid_to'] ?: '-') ?></td>
                            <td class="py-3.5 px-4 text-slate-500"><?= htmlspecialchars($exp['user_name'] ?? 'النظام') ?></td>
                            <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px]"><?= $exp['date'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Expense Modal -->
<div id="addExpenseModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <h4 class="font-black text-slate-800 text-base mb-1">تسجيل سند صرف / مصروف جديد</h4>
        <p class="text-xs text-slate-400 mb-4">أدخل تفاصيل المصروف وسيتم قيده مباشرة في قائمة الدخل والصندوق</p>

        <form action="<?= base_url('expenses') ?>" method="POST" class="space-y-4 text-xs font-semibold">
            <?= csrf_field() ?>
            <div>
                <label class="block text-slate-700 mb-1">بيان المصروف <span class="text-rose-500">*</span></label>
                <input type="text" name="title" required placeholder="مثال: فاتورة كهرباء شهر نوفمبر" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">الفئة والتصنيف <span class="text-rose-500">*</span></label>
                    <select name="expense_category_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                        <option value="">-- اختر الفئة --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">المبلغ (<?= $currencySymbol ?>) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.5" min="0.5" name="amount" required placeholder="0.00" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">طريقة الصرف</label>
                    <select name="payment_method" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                        <option value="cash">نقداً من الصندوق (كاش)</option>
                        <option value="bank">تحويل بنكي / سداد</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">تاريخ الصرف</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">الجهة / الشخص المستلم</label>
                <input type="text" name="paid_to" placeholder="اسم الشركة أو المستلم..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">ملاحظات إضافية</label>
                <input type="text" name="notes" placeholder="أية تفاصيل..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="button" onclick="closeAddExpenseModal()" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</button>
                <button type="submit" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-md shadow-rose-300">قيد وصرف المصروف</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddExpenseModal() { document.getElementById('addExpenseModal').classList.remove('hidden'); }
    function closeAddExpenseModal() { document.getElementById('addExpenseModal').classList.add('hidden'); }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
