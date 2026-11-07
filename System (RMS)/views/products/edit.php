<?php
$pageTitle = 'تعديل الوجبة: ' . htmlspecialchars($product['name']);
$pageSubtitle = 'تحديث بيانات وسعر وتكاليف الصنف';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<div class="max-w-2xl mx-auto bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
        <h4 class="font-black text-slate-800 text-base">تعديل بيانات الصنف</h4>
        <a href="<?= base_url('products') ?>" class="text-xs font-bold text-slate-400 hover:text-indigo-600">إلغاء وعودة</a>
    </div>

    <form action="<?= base_url('products/' . $product['id']) ?>" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-slate-700 mb-1">اسم الوجبة <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required value="<?= htmlspecialchars($product['name']) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">التصنيف <span class="text-rose-500">*</span></label>
                <select name="category_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-slate-700 mb-1">سعر البيع (<?= $currencySymbol ?>) <span class="text-rose-500">*</span></label>
                <input type="number" step="0.5" min="0" name="price" required value="<?= $product['price'] ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:outline-none">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">التكلفة التقديرية (<?= $currencySymbol ?>)</label>
                <input type="number" step="0.5" min="0" name="cost_price" value="<?= $product['cost_price'] ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:outline-none">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">وحدة القياس</label>
                <input type="text" name="unit" value="<?= htmlspecialchars($product['unit']) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-slate-700 mb-1">الباركود</label>
                <input type="text" name="barcode" value="<?= htmlspecialchars($product['barcode'] ?? '') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:outline-none">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">رمز الـ SKU</label>
                <input type="text" name="sku" value="<?= htmlspecialchars($product['sku'] ?? '') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:outline-none">
            </div>
        </div>

        <div>
            <label class="block text-slate-700 mb-1">وصف المكونات والوجبة</label>
            <textarea name="description" rows="2" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
        </div>

        <!-- Toggles -->
        <div class="flex items-center gap-6 py-2 border-t border-slate-100">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="has_recipe" value="1" <?= $product['has_recipe'] ? 'checked' : '' ?> class="w-4 h-4 rounded text-indigo-600">
                <span class="text-slate-800">تفعيل الوصفة وخصم المخزون التلقائي</span>
            </label>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_available" value="1" <?= $product['is_available'] ? 'checked' : '' ?> class="w-4 h-4 rounded text-indigo-600">
                <span class="text-slate-800">متاح في شاشة الكاشير</span>
            </label>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="<?= base_url('products') ?>" class="px-5 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</a>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-300">حفظ التعديلات</button>
        </div>

    </form>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
