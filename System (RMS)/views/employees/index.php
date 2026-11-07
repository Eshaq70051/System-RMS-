<?php
$pageTitle = 'الموظفين والموارد البشرية (HR)';
$pageSubtitle = 'إدارة فريق العمل، البيانات الوظيفية، الرواتب والمهام';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<!-- Header Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6">
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">إجمالي الكادر الوظيفي</span>
        <div class="text-2xl font-black text-slate-900"><?= $totalStaff ?> موظف</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">إجمالي الرواتب الأساسية الشهرية</span>
        <div class="text-2xl font-black text-indigo-600"><?= currency_format($totalSalaries) ?></div>
    </div>
</div>

<!-- Header Actions -->
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-2">
        <a href="<?= base_url('attendance') ?>" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition flex items-center gap-1.5">
            <i class="fa-solid fa-user-clock text-indigo-600"></i>
            <span>سجل الحضور والانصراف</span>
        </a>
        <a href="<?= base_url('payroll') ?>" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition flex items-center gap-1.5">
            <i class="fa-solid fa-file-invoice-dollar text-emerald-600"></i>
            <span>مسيرات الرواتب</span>
        </a>
    </div>

    <button onclick="openAddEmpModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-300 transition flex items-center gap-1.5">
        <i class="fa-solid fa-user-plus"></i>
        <span>إضافة موظف جديد</span>
    </button>
</div>

<!-- Employees Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="py-3.5 px-4">اسم الموظف</th>
                    <th class="py-3.5 px-4">المسمى الوظيفي</th>
                    <th class="py-3.5 px-4">القسم</th>
                    <th class="py-3.5 px-4">رقم الهاتف</th>
                    <th class="py-3.5 px-4">الراتب الأساسي</th>
                    <th class="py-3.5 px-4">تاريخ التعيين</th>
                    <th class="py-3.5 px-4">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-10 text-slate-400">لا يوجد موظفين مسجلين</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees as $emp): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-xs">
                                        <?= mb_substr($emp['full_name'], 0, 1) ?>
                                    </div>
                                    <span class="font-bold text-slate-900"><?= htmlspecialchars($emp['full_name']) ?></span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700"><?= htmlspecialchars($emp['job_title']) ?></td>
                            <td class="py-3.5 px-4 text-slate-500"><?= htmlspecialchars($emp['department']) ?></td>
                            <td class="py-3.5 px-4 font-mono text-slate-600"><?= htmlspecialchars($emp['phone']) ?></td>
                            <td class="py-3.5 px-4 font-black text-indigo-700"><?= currency_format($emp['salary']) ?></td>
                            <td class="py-3.5 px-4 font-mono text-slate-500"><?= $emp['hire_date'] ?: '-' ?></td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">نشط على رأس العمل</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Employee Modal -->
<div id="addEmpModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100">
        <h4 class="font-black text-slate-800 text-base mb-1">إضافة موظف جديد</h4>
        <p class="text-xs text-slate-400 mb-4">أدخل البيانات الشخصية والوظيفية والراتب</p>

        <form action="<?= base_url('employees') ?>" method="POST" class="space-y-4 text-xs font-semibold">
            <?= csrf_field() ?>
            <div>
                <label class="block text-slate-700 mb-1">الاسم الكامل للموظف <span class="text-rose-500">*</span></label>
                <input type="text" name="full_name" required placeholder="مثال: خالد العتيبي" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">المسمى الوظيفي <span class="text-rose-500">*</span></label>
                    <input type="text" name="job_title" required placeholder="مثال: كاشير أول" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">القسم</label>
                    <select name="department" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                        <option value="الخدمة والصالة">الخدمة ونقاط البيع</option>
                        <option value="المطبخ والإنتاج">المطبخ والإنتاج</option>
                        <option value="الإدارة المالية">الإدارة المالية</option>
                        <option value="المستودع والمخازن">المستودع والمخازن</option>
                        <option value="التوصيل واللوجستيات">التوصيل واللوجستيات</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">رقم الهاتف <span class="text-rose-500">*</span></label>
                    <input type="text" name="phone" required placeholder="05xxxxxxxx" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">الراتب الأساسي (<?= $currencySymbol ?>) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.5" name="salary" required value="4000" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">رقم الهوية / الإقامة</label>
                    <input type="text" name="national_id" placeholder="10xxxxxxxx" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">تاريخ التعيين</label>
                    <input type="date" name="hire_date" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="button" onclick="closeAddEmpModal()" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</button>
                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-300">حفظ الموظف</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddEmpModal() { document.getElementById('addEmpModal').classList.remove('hidden'); }
    function closeAddEmpModal() { document.getElementById('addEmpModal').classList.add('hidden'); }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
