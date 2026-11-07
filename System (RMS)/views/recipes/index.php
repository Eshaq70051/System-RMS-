<?php
$pageTitle = 'إدارة الوصفات وتكلفة الوجبات (Food Costing)';
$pageSubtitle = 'ربط الوجبات بالمواد الخام، حساب التكلفة الدقيقة وهوامش الأرباح التلقائية';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
ob_start();
?>

<!-- Header Action -->
<div class="flex items-center justify-between mb-6">
    <p class="text-xs text-slate-500">
        يتم خصم مكونات كل وجبة من أرصدة المواد الخام بالمستودع آلياً عند كل عملية بيع في الكاشير.
    </p>
    <button onclick="openRecipeModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-300 transition flex items-center gap-1.5">
        <i class="fa-solid fa-plus"></i>
        <span>إنشاء وصفة جديدة</span>
    </button>
</div>

<!-- Recipes Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php if (empty($recipes)): ?>
        <div class="col-span-full py-16 bg-white rounded-3xl border border-slate-200 text-center text-slate-400">
            <i class="fa-solid fa-calculator text-4xl mb-3 text-slate-300"></i>
            <h4 class="font-bold text-slate-700">لا توجد وصفات مسجلة حتى الآن</h4>
            <p class="text-xs text-slate-400 mt-1">ابدأ بإنشاء وصفة واربط الوجبة بمكوناتها الخام لحساب التكلفة والربح التلقائي</p>
        </div>
    <?php else: ?>
        <?php foreach ($recipes as $r): ?>
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 flex flex-col justify-between hover:shadow-md transition">
                <div>
                    <!-- Header -->
                    <div class="flex items-start justify-between border-b border-slate-100 pb-3 mb-3">
                        <div>
                            <span class="text-[10px] font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg mb-1 inline-block"><?= htmlspecialchars($r['category_name']) ?></span>
                            <h4 class="font-black text-slate-800 text-sm leading-tight"><?= htmlspecialchars($r['product_name']) ?></h4>
                        </div>
                        <form action="<?= base_url('recipes/' . $r['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('هل تريد حذف هذه الوصفة؟')">
                            <?= csrf_field() ?>
                            <button type="submit" class="p-1 text-slate-400 hover:text-rose-500 transition" title="حذف الوصفة">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Costing & Margin KPI Badges -->
                    <div class="grid grid-cols-3 gap-2 p-2.5 bg-slate-50 rounded-2xl mb-4 text-center">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold block">سعر البيع</span>
                            <span class="font-black text-slate-900 text-xs"><?= number_format($r['selling_price'], 2) ?> <?= $currencySymbol ?></span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold block">تكلفة المواد</span>
                            <span class="font-black text-rose-600 text-xs"><?= number_format($r['current_calculated_cost'], 2) ?> <?= $currencySymbol ?></span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold block">هامش الربح</span>
                            <span class="font-black text-emerald-600 text-xs"><?= $r['profit_margin'] ?>%</span>
                        </div>
                    </div>

                    <!-- Ingredients List -->
                    <div class="space-y-1.5 mb-4">
                        <span class="text-[11px] font-bold text-slate-500 block mb-1">المكونات والمقادير المستخدمة:</span>
                        <?php foreach ($r['ingredients'] as $ing): ?>
                            <div class="flex items-center justify-between text-xs py-1 border-b border-slate-100 last:border-0">
                                <span class="text-slate-700 font-medium"><?= htmlspecialchars($ing['item_name']) ?></span>
                                <div class="text-left">
                                    <span class="font-bold text-slate-900"><?= (float)$ing['quantity_required'] ?> <?= $ing['unit'] ?></span>
                                    <span class="text-[10px] text-slate-400 block">(<?= number_format($ing['ingredient_cost'], 2) ?> <?= $currencySymbol ?>)</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                    <span class="flex items-center gap-1"><i class="fa-regular fa-clock"></i> تحضير: <?= $r['prep_time_minutes'] ?> دقيقة</span>
                    <span class="font-bold text-emerald-600">خصم تلقائي نشط ✓</span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Recipe Builder Modal -->
<div id="recipeModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
        <h4 class="font-black text-slate-800 text-base mb-1">إنشاء وصفة وتكلفة وجبة</h4>
        <p class="text-xs text-slate-400 mb-4">اختر الوجبة وحدد المواد الخام والكميات المطلوبة لتحضير وجبة واحدة</p>

        <form action="<?= base_url('recipes') ?>" method="POST" id="recipeForm" class="space-y-4 text-xs font-semibold">
            <?= csrf_field() ?>
            <div>
                <label class="block text-slate-700 mb-1">اختر الوجبة المراد عمل وصفة لها <span class="text-rose-500">*</span></label>
                <select name="product_id" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- اختر الوجبة --</option>
                    <?php foreach ($availableProducts as $ap): ?>
                        <option value="<?= $ap['id'] ?>"><?= htmlspecialchars($ap['name']) ?> (سعر البيع: <?= $ap['price'] ?> <?= $currencySymbol ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">اسم الوصفة <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="مثال: وصفة البرجر القياسية" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">وقت التجهيز (بالدقائق)</label>
                    <input type="number" name="prep_time_minutes" value="15" min="1" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
            </div>

            <!-- Dynamic Ingredients Rows -->
            <div class="space-y-2 pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">مكونات الوجبة (المواد الخام):</span>
                    <button type="button" onclick="addIngredientRow()" class="px-2.5 py-1 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 font-bold text-[11px] transition">
                        <i class="fa-solid fa-plus"></i> إضافة مادة
                    </button>
                </div>

                <div id="ingredientsContainer" class="space-y-2">
                    <!-- Row 1 -->
                    <div class="flex items-center gap-2 ingredient-row">
                        <select name="ingredients[0][item_id]" required class="flex-1 px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none">
                            <option value="">-- اختر المادة الخام --</option>
                            <?php foreach ($rawMaterials as $rm): ?>
                                <option value="<?= $rm['id'] ?>"><?= htmlspecialchars($rm['name']) ?> (<?= $rm['unit'] ?> - تكلفة: <?= $rm['cost_per_unit'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" step="0.001" name="ingredients[0][quantity]" required placeholder="الكمية (مثال: 0.180)" class="w-32 px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none font-bold">
                        <button type="button" onclick="this.closest('.ingredient-row').remove()" class="p-2 text-slate-400 hover:text-rose-500"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">إرشادات التحضير والشوي للشيف</label>
                <textarea name="instructions" rows="2" placeholder="خطوات التحضير..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeRecipeModal()" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</button>
                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-300">حفظ وحساب التكلفة</button>
            </div>
        </form>
    </div>
</div>

<script>
    let rowIdx = 1;
    const rawOptions = `<?= implode('', array_map(function($rm) { return "<option value='{$rm['id']}'>" . htmlspecialchars($rm['name']) . " ({$rm['unit']})</option>"; }, $rawMaterials)) ?>`;

    function openRecipeModal() { document.getElementById('recipeModal').classList.remove('hidden'); }
    function closeRecipeModal() { document.getElementById('recipeModal').classList.add('hidden'); }

    function addIngredientRow() {
        const container = document.getElementById('ingredientsContainer');
        const row = document.createElement('div');
        row.className = 'flex items-center gap-2 ingredient-row';
        row.innerHTML = `
            <select name="ingredients[${rowIdx}][item_id]" required class="flex-1 px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none">
                <option value="">-- اختر المادة الخام --</option>
                ${rawOptions}
            </select>
            <input type="number" step="0.001" name="ingredients[${rowIdx}][quantity]" required placeholder="الكمية" class="w-32 px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none font-bold">
            <button type="button" onclick="this.closest('.ingredient-row').remove()" class="p-2 text-slate-400 hover:text-rose-500"><i class="fa-solid fa-trash"></i></button>
        `;
        container.appendChild(row);
        rowIdx++;
    }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
