<?php
$pageTitle = 'المخزون والمواد الخام والمستودع';
$pageSubtitle = 'إدارة المواد الأولية، مستويات المخزون، وتنبيهات النقص الفورية';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">إجمالي المواد المسجلة</span>
        <div class="text-2xl font-black text-slate-900"><?= $totalItemsCount ?> مادة أولية</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">مواد بلغت حد الخطر</span>
        <div class="text-2xl font-black <?= $lowStockCount > 0 ? 'text-rose-600' : 'text-emerald-600' ?>"><?= $lowStockCount ?> مواد ناقصة</div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">تقييم إجمالي المخزون</span>
        <div class="text-2xl font-black text-indigo-600"><?= currency_format($totalValuation) ?></div>
    </div>
</div>

<!-- Header Actions -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <form method="GET" action="<?= base_url('inventory') ?>" class="flex items-center gap-2">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="بحث باسم المادة أو الكود..." class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none">
        <select name="status" onchange="this.form.submit()" class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none">
            <option value="">جميع المواد</option>
            <option value="low" <?= $statusFilter === 'low' ? 'selected' : '' ?>>منخفضة المخزون فقط ⚠️</option>
        </select>
        <button type="submit" class="px-3 py-2 bg-slate-800 text-white rounded-xl text-xs"><i class="fa-solid fa-search"></i></button>
    </form>

    <div class="flex items-center gap-2">
        <a href="<?= base_url('inventory/movements') ?>" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition flex items-center gap-1.5">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>سجل حركة المواد</span>
        </a>
        <button onclick="openAddItemModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-300 transition flex items-center gap-1.5">
            <i class="fa-solid fa-plus"></i>
            <span>إضافة مادة خام</span>
        </button>
    </div>
</div>

<!-- Inventory Items Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="py-3.5 px-4">المادة الخام</th>
                    <th class="py-3.5 px-4">كود SKU</th>
                    <th class="py-3.5 px-4">الرصيد الحالي</th>
                    <th class="py-3.5 px-4">حد التنبيه</th>
                    <th class="py-3.5 px-4">تكلفة الوحدة</th>
                    <th class="py-3.5 px-4">القيمة الإجمالية</th>
                    <th class="py-3.5 px-4">تاريخ الصلاحية</th>
                    <th class="py-3.5 px-4 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-10 text-slate-400">لا توجد مواد مخزنية مسجلة</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php 
                        $isLow = $item['current_stock'] <= $item['min_stock_alert'];
                        ?>
                        <tr class="hover:bg-slate-50 transition <?= $isLow ? 'bg-rose-50/20' : '' ?>">
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 block"><?= htmlspecialchars($item['name']) ?></span>
                                <span class="text-[10px] text-slate-400">الوحدة: <?= htmlspecialchars($item['unit']) ?></span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px]"><?= $item['sku'] ?></td>
                            <td class="py-3.5 px-4">
                                <span class="font-black <?= $isLow ? 'text-rose-600 bg-rose-100 px-2 py-0.5 rounded' : 'text-slate-900' ?>">
                                    <?= (float)$item['current_stock'] ?> <?= $item['unit'] ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500"><?= (float)$item['min_stock_alert'] ?> <?= $item['unit'] ?></td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700"><?= number_format($item['cost_per_unit'], 2) ?> <?= $currencySymbol ?></td>
                            <td class="py-3.5 px-4 font-bold text-indigo-600"><?= currency_format($item['total_valuation']) ?></td>
                            <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px]"><?= $item['expiry_date'] ?: '-' ?></td>
                            <td class="py-3.5 px-4 text-center">
                                <button onclick="openAdjustModal(<?= $item['id'] ?>, '<?= htmlspecialchars($item['name']) ?>', <?= $item['current_stock'] ?>, '<?= $item['unit'] ?>')" class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 font-bold rounded-lg text-[11px] transition">
                                    <i class="fa-solid fa-sliders"></i> تسوية جرد
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Item Modal -->
<div id="addItemModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100">
        <h4 class="font-black text-slate-800 text-base mb-1">إضافة مادة خام جديدة للمستودع</h4>
        <p class="text-xs text-slate-400 mb-4">أدخل بيانات المادة وتكلفة شرائها وحد التنبيه الأدنى</p>

        <form action="<?= base_url('inventory') ?>" method="POST" class="space-y-4 text-xs font-semibold">
            <?= csrf_field() ?>
            <div>
                <label class="block text-slate-700 mb-1">اسم المادة الخام <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="مثال: صدور دجاج طازجة" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">كود SKU</label>
                    <input type="text" name="sku" placeholder="RAW-101" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">وحدة القياس</label>
                    <select name="unit" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                        <option value="كجم">كيلوجرام (كجم)</option>
                        <option value="جرام">جرام (جم)</option>
                        <option value="لتر">لتر (L)</option>
                        <option value="حبة">حبة / قطعة</option>
                        <option value="علبة">علبة / باكت</option>
                        <option value="كرتون">كرتون</option>
                        <option value="واحد">كيس</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">الرصيد الافتتاحي</label>
                    <input type="number" step="0.001" name="current_stock" value="0" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">حد تنبيه النقص</label>
                    <input type="number" step="0.001" name="min_stock_alert" value="5" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">تكلفة الوحدة (<?= $currencySymbol ?>)</label>
                    <input type="number" step="0.5" name="cost_per_unit" value="0.00" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">تاريخ انتهاء الصلاحية</label>
                    <input type="date" name="expiry_date" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">رقم التشغيلة (Batch No)</label>
                    <input type="text" name="batch_number" placeholder="BATCH-2026-A" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="button" onclick="closeAddItemModal()" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</button>
                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-300">حفظ المادة</button>
            </div>
        </form>
    </div>
</div>

<!-- Stock Settlement / Adjustment Modal -->
<div id="adjustModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <h4 class="font-black text-slate-800 text-base mb-1">تسوية جرد فعلي للمادة</h4>
        <p class="text-xs text-slate-400 mb-4">سيتم احتساب الفارق وتسجيل حركة تسوية مخزنية تلقائياً</p>

        <form action="<?= base_url('inventory/adjust') ?>" method="POST" class="space-y-4 text-xs font-semibold">
            <?= csrf_field() ?>
            <input type="hidden" name="item_id" id="adjustItemId">
            
            <div class="p-3 bg-indigo-50 rounded-xl border border-indigo-100">
                <span class="block text-slate-500 mb-1">المادة الخام:</span>
                <span id="adjustItemName" class="font-black text-indigo-700 text-sm"></span>
                <span id="adjustCurrentStockText" class="block text-[11px] text-slate-500 mt-1"></span>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">الرصيد الفعلي بعد الجرد <span class="text-rose-500">*</span></label>
                <input type="number" step="0.001" name="actual_stock" id="adjustActualStock" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">سبب التسوية</label>
                <input type="text" name="reason" value="جرد دوري وتسوية فروقات" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="button" onclick="closeAdjustModal()" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</button>
                <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-300">اعتماد الجرد</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddItemModal() { document.getElementById('addItemModal').classList.remove('hidden'); }
    function closeAddItemModal() { document.getElementById('addItemModal').classList.add('hidden'); }
    
    function openAdjustModal(id, name, stock, unit) {
        document.getElementById('adjustItemId').value = id;
        document.getElementById('adjustItemName').textContent = name;
        document.getElementById('adjustCurrentStockText').textContent = `الرصيد المسجل في النظام: ${stock} ${unit}`;
        document.getElementById('adjustActualStock').value = stock;
        document.getElementById('adjustModal').classList.remove('hidden');
    }
    function closeAdjustModal() { document.getElementById('adjustModal').classList.add('hidden'); }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
