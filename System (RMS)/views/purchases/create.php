<?php
$pageTitle = 'تسجيل فاتورة شراء واستلام مواد خام';
$pageSubtitle = 'إدخال بنود الشراء وتحديث أرصدة وتكاليف المخزون تلقائياً';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<div class="max-w-4xl mx-auto bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
        <div>
            <h4 class="font-black text-slate-800 text-base">فاتورة مشتريات وتوريد مخزني</h4>
            <p class="text-xs text-slate-400">سيتم إضافة الكميات المشتراة وتحديث أسعار التكلفة فور اعتماد الفاتورة</p>
        </div>
        <a href="<?= base_url('purchases') ?>" class="text-xs font-bold text-slate-400 hover:text-indigo-600">إلغاء وعودة</a>
    </div>

    <form action="<?= base_url('purchases') ?>" method="POST" id="purchaseForm" class="space-y-5 text-xs font-semibold">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-slate-700 mb-1">المورد <span class="text-rose-500">*</span></label>
                <select name="supplier_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- اختر المورد --</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['company_name'] ?: $s['name']) ?> (رصيد سابق: <?= $s['current_balance'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">رقم فاتورة المورد</label>
                <input type="text" name="invoice_number" placeholder="مثال: INV-9988" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:outline-none">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">تاريخ الشراء والاستلام</label>
                <input type="date" name="purchase_date" value="<?= date('Y-m-d') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>
        </div>

        <!-- Items Table -->
        <div class="pt-3 border-t border-slate-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-800">الأصناف والمواد المشتراة:</span>
                <button type="button" onclick="addPurchaseRow()" class="px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-100 font-bold transition flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i> إضافة بند
                </button>
            </div>

            <div class="border border-slate-200 rounded-2xl overflow-hidden">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">المادة الخام</th>
                            <th class="py-2.5 px-3 w-32">الكمية</th>
                            <th class="py-2.5 px-3 w-32">سعر الوحدة</th>
                            <th class="py-2.5 px-3 w-32 text-left">الإجمالي</th>
                            <th class="py-2.5 px-3 w-10 text-center"></th>
                        </tr>
                    </thead>
                    <tbody id="purchaseRowsContainer" class="divide-y divide-slate-100">
                        <tr class="purchase-row">
                            <td class="p-2.5">
                                <select name="items[0][item_id]" required class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                                    <option value="">-- اختر المادة --</option>
                                    <?php foreach ($rawMaterials as $rm): ?>
                                        <option value="<?= $rm['id'] ?>"><?= htmlspecialchars($rm['name']) ?> (<?= $rm['unit'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="p-2.5">
                                <input type="number" step="0.001" name="items[0][quantity]" oninput="calcPurchaseTotal()" required placeholder="0.00" class="row-qty w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:outline-none">
                            </td>
                            <td class="p-2.5">
                                <input type="number" step="0.5" name="items[0][unit_cost]" oninput="calcPurchaseTotal()" required placeholder="0.00" class="row-cost w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:outline-none">
                            </td>
                            <td class="p-2.5 text-left font-black text-slate-800 row-total">0.00 <?= $currencySymbol ?></td>
                            <td class="p-2.5 text-center">
                                <button type="button" onclick="this.closest('tr').remove(); calcPurchaseTotal();" class="text-slate-400 hover:text-rose-500"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment and Summary -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
            <div>
                <label class="block text-slate-700 mb-1">المبلغ المسدد نقداً الآن (<?= $currencySymbol ?>)</label>
                <input type="number" step="0.5" name="paid_amount" id="poPaidAmount" value="0.00" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:outline-none">
                <span class="text-[11px] text-slate-400 mt-1 block">إذا كان مسدداً جزئياً، سيتم قيد الباقي دين على حساب المورد آلياً</span>
            </div>

            <div class="p-4 bg-slate-50 rounded-2xl flex flex-col justify-center text-left">
                <span class="text-xs text-slate-500 font-bold block mb-1">إجمالي الفاتورة المستحق:</span>
                <span id="poGrandTotalText" class="text-2xl font-black text-indigo-700">0.00 <?= $currencySymbol ?></span>
            </div>
        </div>

        <div>
            <label class="block text-slate-700 mb-1">ملاحظات الفاتورة</label>
            <input type="text" name="notes" placeholder="أية تفاصيل إضافية..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="<?= base_url('purchases') ?>" class="px-5 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</a>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-300">اعتماد الفاتورة وتوريد المخزون</button>
        </div>
    </form>
</div>

<script>
    let pRowIdx = 1;
    const itemOptions = `<?= implode('', array_map(function($rm) { return "<option value='{$rm['id']}'>" . htmlspecialchars($rm['name']) . " ({$rm['unit']})</option>"; }, $rawMaterials)) ?>`;

    function addPurchaseRow() {
        const container = document.getElementById('purchaseRowsContainer');
        const tr = document.createElement('tr');
        tr.className = 'purchase-row';
        tr.innerHTML = `
            <td class="p-2.5">
                <select name="items[${pRowIdx}][item_id]" required class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                    <option value="">-- اختر المادة --</option>
                    ${itemOptions}
                </select>
            </td>
            <td class="p-2.5">
                <input type="number" step="0.001" name="items[${pRowIdx}][quantity]" oninput="calcPurchaseTotal()" required placeholder="0.00" class="row-qty w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:outline-none">
            </td>
            <td class="p-2.5">
                <input type="number" step="0.5" name="items[${pRowIdx}][unit_cost]" oninput="calcPurchaseTotal()" required placeholder="0.00" class="row-cost w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:outline-none">
            </td>
            <td class="p-2.5 text-left font-black text-slate-800 row-total">0.00 <?= $currencySymbol ?></td>
            <td class="p-2.5 text-center">
                <button type="button" onclick="this.closest('tr').remove(); calcPurchaseTotal();" class="text-slate-400 hover:text-rose-500"><i class="fa-solid fa-trash"></i></button>
            </td>
        `;
        container.appendChild(tr);
        pRowIdx++;
    }

    function calcPurchaseTotal() {
        let total = 0;
        document.querySelectorAll('.purchase-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.row-qty').value) || 0;
            const cost = parseFloat(row.querySelector('.row-cost').value) || 0;
            const rowTotal = qty * cost;
            row.querySelector('.row-total').textContent = rowTotal.toFixed(2) + ' <?= $currencySymbol ?>';
            total += rowTotal;
        });

        document.getElementById('poGrandTotalText').textContent = total.toFixed(2) + ' <?= $currencySymbol ?>';
    }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
