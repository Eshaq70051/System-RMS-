<?php
$pageTitle = 'إعدادات النظام والأمان';
$pageSubtitle = 'إدارة بيانات المطعم، الضرائب، الفروع، المستخدمين، والصلاحيات';
ob_start();
?>

<div class="space-y-8">

    <!-- General Settings Form -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
            <div>
                <h4 class="font-black text-slate-800 text-base">إعدادات المطعم والفواتير</h4>
                <p class="text-xs text-slate-400">تظهر هذه البيانات على الفواتير الضريبية وشاشات النظام</p>
            </div>
            <a href="<?= base_url('settings/backup') ?>" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-download"></i>
                <span>نسخ احتياطي لقاعدة البيانات</span>
            </a>
        </div>

        <form action="<?= base_url('settings') ?>" method="POST" enctype="multipart/form-data" class="space-y-6 text-xs font-semibold">
            <?= csrf_field() ?>

            <!-- Logo Upload & Branding Section -->
            <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-2xl flex flex-col md:flex-row items-center justify-between gap-5">
                <div class="flex items-center gap-4">
                    <!-- Current Logo / Preview Box -->
                    <div class="relative group">
                        <div id="logoPreviewWrapper" class="w-20 h-20 rounded-2xl bg-white border-2 border-dashed border-slate-300 flex items-center justify-center overflow-hidden shadow-sm">
                            <?php $currentLogo = $settingsMap['restaurant_logo'] ?? ''; ?>
                            <?php if ($currentLogo && file_exists(__DIR__ . '/../../public/' . $currentLogo)): ?>
                                <img id="logoPreviewImg" src="<?= base_url($currentLogo) ?>" alt="شعار المطعم" class="w-full h-full object-contain p-1">
                                <div id="logoDefaultPlaceholder" class="hidden w-full h-full bg-gradient-to-tr from-amber-500 to-indigo-600 flex items-center justify-center text-white text-2xl">
                                    <i class="fa-solid fa-utensils"></i>
                                </div>
                            <?php else: ?>
                                <div id="logoDefaultPlaceholder" class="w-full h-full bg-gradient-to-tr from-amber-500 to-indigo-600 flex items-center justify-center text-white text-2xl">
                                    <i class="fa-solid fa-utensils"></i>
                                </div>
                                <img id="logoPreviewImg" src="" alt="شعار المطعم" class="hidden w-full h-full object-contain p-1">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <h5 class="text-sm font-black text-slate-800">شعار التطبيق والمطعم (Brand Logo)</h5>
                        <p class="text-[11px] text-slate-500 mt-0.5">يظهر الشعار في الشريط الجانبي للنظام، شاشة الدخول، وفواتير الكاشير المطبوعة</p>
                        <p class="text-[10px] text-slate-400 mt-1">الصيغ المدعومة: PNG, JPG, WEBP, SVG | الحجم الأقصى: 3MB</p>
                    </div>
                </div>

                <!-- Upload and Remove Controls -->
                <div class="flex items-center gap-3">
                    <label class="cursor-pointer px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-xl border border-indigo-200 transition flex items-center gap-2 shadow-sm">
                        <i class="fa-solid fa-cloud-arrow-up text-sm text-indigo-600"></i>
                        <span>رفع وتغيير الشعار</span>
                        <input type="file" name="restaurant_logo" id="logoFileInput" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="hidden" onchange="previewLogoFile(event)">
                    </label>

                    <?php if (!empty($settingsMap['restaurant_logo'])): ?>
                        <label class="cursor-pointer px-3.5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-xl border border-rose-200 transition flex items-center gap-1.5" title="حذف الشعار واستعادة الأيقونة الافتراضية">
                            <input type="checkbox" name="remove_logo" value="1" class="w-3.5 h-3.5 text-rose-600 rounded">
                            <span>حذف الشعار</span>
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-700 mb-1">اسم المطعم / المنشأة</label>
                    <input type="text" name="restaurant_name" value="<?= htmlspecialchars($settingsMap['restaurant_name'] ?? '') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">رقم الهاتف للتواصل</label>
                    <input type="text" name="restaurant_phone" value="<?= htmlspecialchars($settingsMap['restaurant_phone'] ?? '') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">البريد الإلكتروني</label>
                    <input type="email" name="restaurant_email" value="<?= htmlspecialchars($settingsMap['restaurant_email'] ?? '') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-700 mb-1">الرقم الضريبي (VAT)</label>
                    <input type="text" name="tax_number" value="<?= htmlspecialchars($settingsMap['tax_number'] ?? '') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">السجل التجاري (CR)</label>
                    <input type="text" name="commercial_registration" value="<?= htmlspecialchars($settingsMap['commercial_registration'] ?? '') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">نسبة ضريبة القيمة المضافة (%)</label>
                    <input type="number" step="0.5" name="tax_rate" value="<?= htmlspecialchars($settingsMap['tax_rate'] ?? '15') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-700 mb-1">رمز العملة (العربي)</label>
                    <input type="text" name="currency_symbol" value="<?= htmlspecialchars($settingsMap['currency_symbol'] ?? 'ر.ي') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">بادئة أرقام الطلبات</label>
                    <input type="text" name="order_prefix" value="<?= htmlspecialchars($settingsMap['order_prefix'] ?? 'ORD-') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">العنوان والموقع الجغرافي</label>
                <input type="text" name="restaurant_address" value="<?= htmlspecialchars($settingsMap['restaurant_address'] ?? '') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">نص أسفل الفاتورة الحرارية (Footer)</label>
                <input type="text" name="invoice_footer" value="<?= htmlspecialchars($settingsMap['invoice_footer'] ?? '') ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>

            <div class="flex items-center gap-6 py-2 border-t border-slate-100">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="tax_enabled" value="1" <?= ($settingsMap['tax_enabled'] ?? '1') == '1' ? 'checked' : '' ?> class="w-4 h-4 rounded text-indigo-600">
                    <span class="text-slate-800">تطبيق وحساب ضريبة القيمة المضافة على الفواتير</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enable_recipe_deduction" value="1" <?= ($settingsMap['enable_recipe_deduction'] ?? '1') == '1' ? 'checked' : '' ?> class="w-4 h-4 rounded text-indigo-600">
                    <span class="text-slate-800">تفعيل خصم المواد الخام من المخزون تلقائياً بالوصفات</span>
                </label>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-300">
                    حفظ الإعدادات العامة
                </button>
            </div>
        </form>
    </div>

    <!-- Users & Security Section -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <div>
                <h4 class="font-black text-slate-800 text-base">المستخدمين وصلاحيات الدخول</h4>
                <p class="text-xs text-slate-400">إدارة حسابات المدراء، الكاشير، الطهاة، والمحاسبين</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= base_url('settings/audit-logs') ?>" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>سجل التدقيق والأمان</span>
                </a>
                <button onclick="openAddUserModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-300 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>إضافة مستخدم جديد</span>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">الاسم الكامل</th>
                        <th class="py-3 px-4">اسم المستخدم</th>
                        <th class="py-3 px-4">الدور الوظيفي</th>
                        <th class="py-3 px-4">الفرع</th>
                        <th class="py-3 px-4">آخر تسجيل دخول</th>
                        <th class="py-3 px-4">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-bold text-slate-900"><?= htmlspecialchars($u['name']) ?></td>
                            <td class="py-3 px-4 font-mono text-indigo-600"><?= htmlspecialchars($u['username']) ?></td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700">
                                    <?= htmlspecialchars($u['role_name']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($u['branch_name'] ?? 'الفرع الرئيسي') ?></td>
                            <td class="py-3 px-4 font-mono text-slate-400 text-[11px]"><?= $u['last_login'] ?: 'لم يسجل دخول بعد' ?></td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">نشط ✓</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <h4 class="font-black text-slate-800 text-base mb-1">إضافة مستخدم جديد للنظام</h4>
        <p class="text-xs text-slate-400 mb-4">حدد اسم المستخدم والدور والفرع وكلمة المرور</p>

        <form action="<?= base_url('settings/users') ?>" method="POST" class="space-y-4 text-xs font-semibold">
            <?= csrf_field() ?>
            <div>
                <label class="block text-slate-700 mb-1">الاسم الكامل <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="مثال: حسام العبدالله" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">اسم الدخول (Username) <span class="text-rose-500">*</span></label>
                    <input type="text" name="username" required placeholder="user1" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">كلمة المرور <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-700 mb-1">الدور والصلاحية <span class="text-rose-500">*</span></label>
                    <select name="role_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">الفرع</label>
                    <select name="branch_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">البريد الإلكتروني (اختياري)</label>
                <input type="email" name="email" placeholder="user@restaurant.com" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="button" onclick="closeAddUserModal()" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</button>
                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-300">إنشاء المستخدم</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddUserModal() { document.getElementById('addUserModal').classList.remove('hidden'); }
    function closeAddUserModal() { document.getElementById('addUserModal').classList.add('hidden'); }

    function previewLogoFile(event) {
        const file = event.target.files[0];
        if (!file) return;

        const img = document.getElementById('logoPreviewImg');
        const placeholder = document.getElementById('logoDefaultPlaceholder');
        const reader = new FileReader();

        reader.onload = function(e) {
            img.src = e.target.result;
            img.classList.remove('hidden');
            if (placeholder) {
                placeholder.classList.add('hidden');
            }
        };

        reader.readAsDataURL(file);
    }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
