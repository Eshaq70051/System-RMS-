<?php
$pageTitle = 'قائمة الوجبات والأصناف';
$pageSubtitle = 'إدارة قائمة الطعام، الأسعار، التكاليف، والوصفات';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<!-- Header Actions & Filters -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-3 w-full sm:w-auto">
        <form method="GET" action="<?= base_url('products') ?>" class="flex items-center gap-2">
            <select name="category_id" onchange="this.form.submit()" class="bg-white border border-slate-200 text-xs font-semibold rounded-xl px-3 py-2 text-slate-700 focus:outline-none">
                <option value="">جميع التصنيفات</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($categoryId == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="بحث بالاسم أو الباركود..." class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none">
            <button type="submit" class="px-3 py-2 bg-slate-800 text-white rounded-xl text-xs"><i class="fa-solid fa-search"></i></button>
        </form>
    </div>

    <div class="flex items-center gap-2">
        <a href="<?= base_url('categories') ?>" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition flex items-center gap-1.5">
            <i class="fa-solid fa-layer-group"></i>
            <span>إدارة التصنيفات</span>
        </a>
        <a href="<?= base_url('products/create') ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-300 transition flex items-center gap-1.5">
            <i class="fa-solid fa-plus"></i>
            <span>إضافة وجبة جديدة</span>
        </a>
    </div>
</div>

<!-- Products Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="py-3.5 px-4">اسم الوجبة</th>
                    <th class="py-3.5 px-4">التصنيف</th>
                    <th class="py-3.5 px-4">الباركود / الرمز</th>
                    <th class="py-3.5 px-4">سعر البيع</th>
                    <th class="py-3.5 px-4">التكلفة التقديرية</th>
                    <th class="py-3.5 px-4">هامش الربح</th>
                    <th class="py-3.5 px-4">الوصفة</th>
                    <th class="py-3.5 px-4">الحالة</th>
                    <th class="py-3.5 px-4 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-10 text-slate-400">لا توجد أصناف مسجلة حتى الآن</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <?php 
                        $margin = $p['price'] > 0 ? round((($p['price'] - $p['cost_price']) / $p['price']) * 100, 1) : 0;
                        ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 text-sm">
                                        <i class="fa-solid fa-utensils"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-900 block"><?= htmlspecialchars($p['name']) ?></span>
                                        <span class="text-[10px] text-slate-400"><?= htmlspecialchars($p['unit']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold" style="background-color: <?= $p['category_color'] ?>15; color: <?= $p['category_color'] ?>;">
                                    <?= htmlspecialchars($p['category_name']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px]"><?= $p['barcode'] ?: $p['sku'] ?></td>
                            <td class="py-3.5 px-4 font-black text-indigo-700"><?= number_format($p['price'], 2) ?> <?= $currencySymbol ?></td>
                            <td class="py-3.5 px-4 font-semibold text-slate-600"><?= number_format($p['cost_price'], 2) ?> <?= $currencySymbol ?></td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $margin >= 50 ? 'bg-emerald-100 text-emerald-800' : ($margin >= 30 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') ?>">
                                    <?= $margin ?>%
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <?php if ($p['recipe_exists']): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 flex items-center gap-1 w-max">
                                        <i class="fa-solid fa-check text-[9px]"></i> مربوطة
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400 text-[11px]">بدون وصفة</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $p['is_available'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>">
                                    <?= $p['is_available'] ? 'متاح للبيع' : 'غير متوفر' ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="<?= base_url('products/' . $p['id'] . '/edit') ?>" class="p-1.5 text-slate-400 hover:text-indigo-600 transition" title="تعديل">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form action="<?= base_url('products/' . $p['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('هل تريد حذف/تعطيل هذا الصنف؟')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 transition" title="حذف">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
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
