<?php
$pageTitle = 'سجل الحضور والانصراف';
$pageSubtitle = 'متابعة حضور وانصراف الموظفين وساعات العمل اليومية';
ob_start();
?>

<!-- Date Filter Header -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <form method="GET" action="<?= base_url('attendance') ?>" class="flex items-center gap-2">
        <label class="text-xs font-bold text-slate-600">اختر التاريخ:</label>
        <input type="date" name="date" value="<?= $date ?>" onchange="this.form.submit()" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold focus:outline-none">
    </form>

    <a href="<?= base_url('employees') ?>" class="text-xs font-bold text-indigo-600 hover:underline">
        <i class="fa-solid fa-users"></i> قائمة الموظفين
    </a>
</div>

<!-- Attendance Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="py-3.5 px-4">الموظف</th>
                    <th class="py-3.5 px-4">المسمى الوظيفي</th>
                    <th class="py-3.5 px-4">القسم</th>
                    <th class="py-3.5 px-4">وقت الحضور</th>
                    <th class="py-3.5 px-4">وقت الانصراف</th>
                    <th class="py-3.5 px-4">ساعات العمل</th>
                    <th class="py-3.5 px-4">الحالة</th>
                    <th class="py-3.5 px-4 text-center">تسجيل / تعديل</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($attendances as $att): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3.5 px-4 font-bold text-slate-900"><?= htmlspecialchars($att['full_name']) ?></td>
                        <td class="py-3.5 px-4 text-slate-600 font-semibold"><?= htmlspecialchars($att['job_title']) ?></td>
                        <td class="py-3.5 px-4 text-slate-400"><?= htmlspecialchars($att['department']) ?></td>
                        <td class="py-3.5 px-4 font-mono font-bold text-slate-700"><?= $att['clock_in'] ?: '-' ?></td>
                        <td class="py-3.5 px-4 font-mono font-bold text-slate-700"><?= $att['clock_out'] ?: '-' ?></td>
                        <td class="py-3.5 px-4 font-bold text-indigo-600"><?= $att['working_hours'] ? $att['working_hours'] . ' ساعة' : '-' ?></td>
                        <td class="py-3.5 px-4">
                            <?php
                            $attStatus = $att['status'] ?? 'present';
                            $attClass = match($attStatus) {
                                'present' => 'bg-emerald-100 text-emerald-800',
                                'absent' => 'bg-rose-100 text-rose-800',
                                'late' => 'bg-amber-100 text-amber-800',
                                'leave' => 'bg-blue-100 text-blue-800',
                                default => 'bg-slate-100 text-slate-800'
                            };
                            $attLabel = match($attStatus) {
                                'present' => 'حاضر',
                                'absent' => 'غائب',
                                'late' => 'متأخر',
                                'leave' => 'إجازة',
                                default => 'حاضر'
                            };
                            ?>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $attClass ?>"><?= $attLabel ?></span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <button onclick="openRecordModal(<?= $att['employee_id'] ?>, '<?= htmlspecialchars($att['full_name']) ?>', '<?= $att['status'] ?? 'present' ?>', '<?= $att['clock_in'] ?? '09:00' ?>', '<?= $att['clock_out'] ?? '17:00' ?>')" class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 font-bold rounded-lg text-[11px] transition">
                                <i class="fa-solid fa-pen-to-square"></i> تسجيل
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Record Attendance Modal -->
<div id="recordModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <h4 class="font-black text-slate-800 text-base mb-1">تسجيل حضور / انصراف الموظف</h4>
        <p id="recordEmpName" class="text-xs font-bold text-indigo-600 mb-4"></p>

        <form action="<?= base_url('attendance') ?>" method="POST" class="space-y-4 text-xs font-semibold">
            <?= csrf_field() ?>
            <input type="hidden" name="employee_id" id="recEmpId">
            <input type="hidden" name="date" value="<?= $date ?>">

            <div>
                <label class="block text-slate-700 mb-1">حالة الحضور</label>
                <select name="status" id="recStatus" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                    <option value="present">حاضر (Present)</option>
                    <option value="late">متأخر (Late)</option>
                    <option value="absent">غائب (Absent)</option>
                    <option value="leave">إجازة رسمية / مرضية (Leave)</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">وقت الحضور</label>
                    <input type="time" name="clock_in" id="recIn" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">وقت الانصراف</label>
                    <input type="time" name="clock_out" id="recOut" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">ملاحظات</label>
                <input type="text" name="notes" placeholder="أية ملاحظات..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="button" onclick="closeRecordModal()" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</button>
                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-300">حفظ السجل</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRecordModal(id, name, status, cin, cout) {
        document.getElementById('recEmpId').value = id;
        document.getElementById('recordEmpName').textContent = name;
        document.getElementById('recStatus').value = status;
        document.getElementById('recIn').value = cin;
        document.getElementById('recOut').value = cout;
        document.getElementById('recordModal').classList.remove('hidden');
    }
    function closeRecordModal() { document.getElementById('recordModal').classList.add('hidden'); }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
