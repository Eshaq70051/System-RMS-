<?php
$pageTitle = 'سجل التدقيق والعمليات الأمنية (Audit Logs)';
$pageSubtitle = 'تتبع جميع العمليات الحساسة المنفذة في النظام مع اسم المستخدم وعنوان IP والوقت';
ob_start();
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <p class="text-xs text-slate-500">
        يتم تسجيل جميع عمليات البيع، التعديل، الحذف، المصروفات، والورديات آلياً لضمان الشفافية والرقابة.
    </p>
    <a href="<?= base_url('settings') ?>" class="text-xs font-bold text-indigo-600 hover:underline">
        <i class="fa-solid fa-arrow-right"></i> العودة للإعدادات
    </a>
</div>

<!-- Audit Logs Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="py-3.5 px-4">رقم العملية</th>
                    <th class="py-3.5 px-4">نوع الإجراء</th>
                    <th class="py-3.5 px-4">الوحدة / القسم</th>
                    <th class="py-3.5 px-4">المستخدم</th>
                    <th class="py-3.5 px-4">تفاصيل العملية</th>
                    <th class="py-3.5 px-4">عنوان IP</th>
                    <th class="py-3.5 px-4">التاريخ والوقت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-10 text-slate-400">لا توجد سجلات تدقيق حتى الآن</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-mono text-slate-400">#<?= $l['id'] ?></td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700">
                                    <?= htmlspecialchars($l['action']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-600 uppercase font-mono text-[11px]"><?= htmlspecialchars($l['module']) ?></td>
                            <td class="py-3.5 px-4 font-bold text-slate-800"><?= htmlspecialchars($l['user_name'] ?? 'النظام') ?></td>
                            <td class="py-3.5 px-4 text-slate-700"><?= htmlspecialchars($l['details']) ?></td>
                            <td class="py-3.5 px-4 font-mono text-slate-400 text-[11px]"><?= $l['ip_address'] ?></td>
                            <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px]"><?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?></td>
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
