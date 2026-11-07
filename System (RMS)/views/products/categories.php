<?php
$pageTitle = 'إدارة تصنيفات الطعام والمشروبات';
$pageSubtitle = 'تنظيم القوائم والألوان والأيقونات المعروضة في شاشات الكاشير';
ob_start();
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Add Category Form -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm h-fit">
        <h4 class="font-black text-slate-800 text-base mb-1">إضافة تصنيف جديد</h4>
        <p class="text-xs text-slate-400 mb-4">أدخل اسم التصنيف وحدد لونه وأيقونته المميزة</p>

        <form action="<?= base_url('categories') ?>" method="POST" class="space-y-4 text-xs font-semibold">
            <?= csrf_field() ?>
            <div>
                <label class="block text-slate-700 mb-1">اسم التصنيف <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="مثال: الشاورما والساندوتشات" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">اللون التعريفي</label>
                    <input type="color" name="color" value="#4f46e5" class="w-full h-9 p-1 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">الترتيب</label>
                    <input type="number" name="sort_order" value="1" min="0" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">رمز الأيقونة (FontAwesome)</label>
                <input type="text" name="icon" value="utensils" placeholder="hamburger, pizza-slice, etc." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>

            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-300 transition">
                إضافة التصنيف
            </button>
        </form>
    </div>

    <!-- Categories List -->
    <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h4 class="font-bold text-slate-800 text-sm">التصنيفات المتاحة (<?= count($categories) ?> تصنيف)</h4>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-4">التصنيف</th>
                        <th class="py-3 px-4">الأيقونة</th>
                        <th class="py-3 px-4">اللون</th>
                        <th class="py-3 px-4">عدد الأصناف</th>
                        <th class="py-3 px-4">الترتيب</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($categories as $cat): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-bold text-slate-900"><?= htmlspecialchars($cat['name']) ?></td>
                            <td class="py-3 px-4">
                                <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs" style="background-color: <?= $cat['color'] ?>20; color: <?= $cat['color'] ?>;">
                                    <i class="fa-solid fa-<?= $cat['icon'] ?: 'utensils' ?>"></i>
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px]"><?= $cat['color'] ?></td>
                            <td class="py-3 px-4 font-bold text-indigo-600"><?= $cat['products_count'] ?> وجبة</td>
                            <td class="py-3 px-4 text-slate-500"><?= $cat['sort_order'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
