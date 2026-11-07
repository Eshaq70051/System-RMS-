<?php
$pageTitle = 'إدارة الصالة وتوزيع الطاولات';
$pageSubtitle = 'خريطة تفاعلية لحالات الطاولات وإشغال الصالة ونقل الطلبات';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<!-- Header Actions -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-3 text-xs font-bold">
        <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> فارغة ومتاحة
        </span>
        <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 text-rose-700 border border-rose-200">
            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> مشغولة بطلب
        </span>
        <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 text-amber-700 border border-amber-200">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> محجوزة
        </span>
    </div>

    <div class="flex items-center gap-2">
        <button onclick="openTransferModal()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-right-arrow-left text-indigo-600"></i>
            <span>نقل طلب بين الطاولات</span>
        </button>

        <button onclick="openAddTableModal()" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-300 transition flex items-center gap-1.5">
            <i class="fa-solid fa-plus"></i>
            <span>إضافة طاولة جديدة</span>
        </button>
    </div>
</div>

<!-- Sections Floor Grid -->
<div class="space-y-8">
    <?php foreach ($groupedTables as $sectionName => $tablesList): ?>
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-5">
                <h4 class="font-black text-slate-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-map-pin text-indigo-500"></i>
                    <span><?= htmlspecialchars($sectionName) ?></span>
                    <span class="text-xs font-semibold text-slate-400 font-sans">(<?= count($tablesList) ?> طاولات)</span>
                </h4>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach ($tablesList as $t): ?>
                    <?php 
                    $isOccupied = $t['status'] === 'occupied';
                    $isReserved = $t['status'] === 'reserved';
                    $cardBorder = $isOccupied ? 'border-rose-400 bg-rose-50/30' : ($isReserved ? 'border-amber-400 bg-amber-50/30' : 'border-slate-200 hover:border-emerald-500 bg-white');
                    $statusColor = $isOccupied ? 'text-rose-600' : ($isReserved ? 'text-amber-600' : 'text-emerald-600');
                    ?>
                    <div class="p-4 rounded-2xl border-2 <?= $cardBorder ?> flex flex-col justify-between transition-all duration-200 hover:shadow-md relative">
                        
                        <div class="flex items-start justify-between mb-2">
                            <span class="text-lg font-black text-slate-800"><?= $t['table_number'] ?></span>
                            <span class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                                <i class="fa-solid fa-users text-xs"></i> <?= $t['capacity'] ?>
                            </span>
                        </div>

                        <div class="py-2 text-center">
                            <i class="fa-solid fa-chair text-3xl <?= $statusColor ?> mb-1"></i>
                            <span class="block text-[11px] font-bold <?= $statusColor ?>">
                                <?= $isOccupied ? 'مشغولة' : ($isReserved ? 'محجوزة' : 'فارغة') ?>
                            </span>
                        </div>

                        <?php if ($isOccupied && $t['order_number']): ?>
                            <div class="mt-2 pt-2 border-t border-rose-200/60 text-[10px]">
                                <span class="font-bold text-rose-700 block truncate">طلب: <?= $t['order_number'] ?></span>
                                <span class="font-black text-slate-900 block"><?= number_format($t['total_amount'], 2) ?> <?= $currencySymbol ?></span>
                            </div>
                        <?php else: ?>
                            <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-center">
                                <button onclick="toggleTableStatus(<?= $t['id'] ?>, '<?= $t['status'] === 'available' ? 'reserved' : 'available' ?>')" class="text-[10px] text-slate-500 hover:text-indigo-600 font-bold transition">
                                    <?= $t['status'] === 'available' ? 'حجز الطاولة' : 'إلغاء الحجز' ?>
                                </button>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add Table Modal -->
<div id="addTableModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <h4 class="font-black text-slate-800 text-base mb-1">إضافة طاولة طعام جديدة</h4>
        <p class="text-xs text-slate-400 mb-4">حدد رقم الطاولة والقسم وسعة الأفراد</p>

        <form action="<?= base_url('tables') ?>" method="POST" class="space-y-4 text-xs font-semibold">
            <?= csrf_field() ?>
            <div>
                <label class="block text-slate-700 mb-1">رقم أو رمز الطاولة (مثال: T-15)</label>
                <input type="text" name="table_number" required placeholder="T-15" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-slate-700 mb-1">القسم / الصالة</label>
                <select name="section" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                    <option value="الصالة الرئيسية">الصالة الرئيسية</option>
                    <option value="قسم العوائل">قسم العوائل</option>
                    <option value="التراس الخارجي VIP">التراس الخارجي VIP</option>
                    <option value="صالة الأفراد">صالة الأفراد</option>
                </select>
            </div>
            <div>
                <label class="block text-slate-700 mb-1">السعة (عدد الأشخاص)</label>
                <input type="number" name="capacity" value="4" min="1" max="24" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>
            <div class="flex items-center gap-2 pt-2">
                <button type="button" onclick="closeAddTableModal()" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</button>
                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-300">إضافة الطاولة</button>
            </div>
        </form>
    </div>
</div>

<!-- Transfer Order Modal -->
<div id="transferModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <h4 class="font-black text-slate-800 text-base mb-1">نقل طلب إلى طاولة أخرى</h4>
        <p class="text-xs text-slate-400 mb-4">اختر الطاولة الحالية المشغولة والطاولة الجديدة المراد التحويل إليها</p>

        <form action="<?= base_url('tables/transfer') ?>" method="POST" class="space-y-4 text-xs font-semibold">
            <?= csrf_field() ?>
            <div>
                <label class="block text-slate-700 mb-1">الطاولة المشغولة (المصدر)</label>
                <select name="from_table_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                    <option value="">-- اختر الطاولة المشغولة --</option>
                    <?php foreach ($tables as $t): ?>
                        <?php if ($t['status'] === 'occupied'): ?>
                            <option value="<?= $t['id'] ?>">طاولة <?= $t['table_number'] ?> (<?= $t['section'] ?>) - طلب: <?= $t['order_number'] ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-slate-700 mb-1">الطاولة الجديدة الفارغة (الهدف)</label>
                <select name="to_table_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                    <option value="">-- اختر طاولة فارغة --</option>
                    <?php foreach ($tables as $t): ?>
                        <?php if ($t['status'] === 'available'): ?>
                            <option value="<?= $t['id'] ?>">طاولة <?= $t['table_number'] ?> (<?= $t['section'] ?> - سعة <?= $t['capacity'] ?>)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-2 pt-2">
                <button type="button" onclick="closeTransferModal()" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</button>
                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-300">تأكيد النقل</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddTableModal() { document.getElementById('addTableModal').classList.remove('hidden'); }
    function closeAddTableModal() { document.getElementById('addTableModal').classList.add('hidden'); }
    function openTransferModal() { document.getElementById('transferModal').classList.remove('hidden'); }
    function closeTransferModal() { document.getElementById('transferModal').classList.add('hidden'); }

    async function toggleTableStatus(tableId, newStatus) {
        const formData = new FormData();
        formData.append('status', newStatus);
        formData.append('csrf_token', '<?= csrf_token() ?>');

        const res = await fetch(`<?= base_url('tables/') ?>${tableId}/status`, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            location.reload();
        }
    }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
