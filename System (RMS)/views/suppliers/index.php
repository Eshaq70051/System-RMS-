<?php
$pageTitle = 'إدارة الموردين وحسابات الديون';
$pageSubtitle = 'سجل بيانات الموردين، أرصدة الحسابات ومتابعة السداد والمدفوعات';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<!-- Header Actions -->
<div class="flex items-center justify-between mb-6">
    <p class="text-xs text-slate-500">
        يتم تسجيل الذمم الدائنة تلقائياً عند شراء بضائع آجلة أو سداد دفعات نقدية للموردين.
    </p>
    <button onclick="openAddSupplierModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-300 transition flex items-center gap-1.5">
        <i class="fa-solid fa-plus"></i>
        <span>إضافة مورد جديد</span>
    </button>
</div>

<!-- Suppliers Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="py-3.5 px-4">اسم المورد</th>
                    <th class="py-3.5 px-4">اسم الشركة والمؤسسة</th>
                    <th class="py-3.5 px-4">رقم الهاتف</th>
                    <th class="py-3.5 px-4">الرقم الضريبي</th>
                    <th class="py-3.5 px-4">الرصيد المستحق للمورد</th>
                    <th class="py-3.5 px-4 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-10 text-slate-400">لا يوجد موردين مسجلين</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-900"><?= htmlspecialchars($s['name']) ?></td>
                            <td class="py-3.5 px-4 text-slate-700 font-semibold"><?= htmlspecialchars($s['company_name'] ?: '-') ?></td>
                            <td class="py-3.5 px-4 font-mono text-slate-600"><?= htmlspecialchars($s['phone'] ?: '-') ?></td>
                            <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px]"><?= htmlspecialchars($s['tax_number'] ?: '-') ?></td>
                            <td class="py-3.5 px-4">
                                <span class="font-black <?= $s['current_balance'] > 0 ? 'text-rose-600' : 'text-emerald-600' ?>">
                                    <?= currency_format($s['current_balance']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <button onclick="openPayModal(<?= $s['id'] ?>, '<?= htmlspecialchars($s['name']) ?>', <?= $s['current_balance'] ?>)" class="px-3 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-lg text-[11px] border border-emerald-200 transition">
                                    <i class="fa-solid fa-money-bill"></i> سداد دفعة
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Supplier Modal -->
<div id="addSupplierModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <h4 class="font-black text-slate-800 text-base mb-1">إضافة مورد جديد</h4>
        <p class="text-xs text-slate-400 mb-4">أدخل بيانات المورد والشركة ومعلومات التواصل</p>

        <form action="<?= base_url('suppliers') ?>" method="POST" class="space-y-4 text-xs font-semibold">
            <?= csrf_field() ?>
            <div>
                <label class="block text-slate-700 mb-1">اسم المورد / المندوب <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="مثال: سالم الشهري" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">اسم الشركة أو المؤسسة</label>
                <input type="text" name="company_name" placeholder="مثال: شركة المراعي للتوريد" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">رقم الهاتف</label>
                    <input type="text" name="phone" placeholder="05xxxxxxxx" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">الرقم الضريبي</label>
                    <input type="text" name="tax_number" placeholder="3000..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">العنوان أو المستودع</label>
                <input type="text" name="address" placeholder="المدينة، الحي..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="button" onclick="closeAddSupplierModal()" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</button>
                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-300">حفظ المورد</button>
            </div>
        </form>
    </div>
</div>

<!-- Pay Supplier Modal -->
<div id="paySupplierModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <h4 class="font-black text-slate-800 text-base mb-1">سداد دفعة نقدية للمورد</h4>
        <p class="text-xs text-slate-400 mb-4">سيتم خصم المبلغ من رصيد دين المورد وقيدها بحركة الصندوق</p>

        <form action="<?= base_url('suppliers/pay') ?>" method="POST" class="space-y-4 text-xs font-semibold">
            <?= csrf_field() ?>
            <input type="hidden" name="supplier_id" id="paySupId">

            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200">
                <span class="text-slate-500 block mb-0.5">المورد:</span>
                <span id="paySupName" class="font-bold text-slate-900 text-sm"></span>
                <span id="paySupBalance" class="block text-xs font-black text-rose-600 mt-1"></span>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">المبلغ المسدد (<?= $currencySymbol ?>) <span class="text-rose-500">*</span></label>
                <input type="number" step="0.5" min="1" name="amount" required id="payAmountInput" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">طريقة السداد</label>
                    <select name="payment_method" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                        <option value="cash">نقداً من الدرج (كاش)</option>
                        <option value="bank_transfer">تحويل بنكي</option>
                        <option value="cheque">شيك مصرفي</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">رقم الإيصال / الحوالة</label>
                    <input type="text" name="reference_number" placeholder="TRX-101" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="button" onclick="closePayModal()" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</button>
                <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-300">تأكيد السداد</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddSupplierModal() { document.getElementById('addSupplierModal').classList.remove('hidden'); }
    function closeAddSupplierModal() { document.getElementById('addSupplierModal').classList.add('hidden'); }

    function openPayModal(id, name, balance) {
        document.getElementById('paySupId').value = id;
        document.getElementById('paySupName').textContent = name;
        document.getElementById('paySupBalance').textContent = `الرصيد المستحق حالياً: ${balance} <?= $currencySymbol ?>`;
        document.getElementById('payAmountInput').value = balance > 0 ? balance : '';
        document.getElementById('paySupplierModal').classList.remove('hidden');
    }
    function closePayModal() { document.getElementById('paySupplierModal').classList.add('hidden'); }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
