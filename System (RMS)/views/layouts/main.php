<?php
$user = auth_user();
$branchName = $user['branch_name'] ?? 'الفرع الرئيسي';
$roleName = $user['role_name'] ?? 'موظف';
$flash = get_flash();
$activeShift = $user ? get_active_shift($user['id'], $user['branch_id'] ?? 1) : null;
$appName = get_setting('restaurant_name', 'نظام إدارة المطاعم الذكي');
$appLogo = get_setting('restaurant_logo');
$currencySymbol = get_setting('currency_symbol', 'ر.س');
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'نظام إدارة المطاعم' ?> | <?= htmlspecialchars($appName) ?></title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                    },
                    fontFamily: {
                        sans: ['Cairo', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Cairo', sans-serif; }
        /* Independent custom scrollbars */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 6px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        aside nav::-webkit-scrollbar-thumb { background: #334155; border-radius: 6px; }
        aside nav::-webkit-scrollbar-thumb:hover { background: #475569; }
        .active-nav {
            background-color: #4f46e5 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased h-screen overflow-hidden flex">

    <!-- Sidebar (Scrolls independently, fixed height) -->
    <aside class="w-64 h-screen bg-slate-900 text-slate-300 flex flex-col flex-shrink-0 z-30 shadow-xl border-l border-slate-800 overflow-hidden">
        <!-- Brand Header (Fixed) -->
        <div class="h-16 flex-shrink-0 flex items-center justify-between px-5 bg-slate-950 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <?php if ($appLogo && file_exists(__DIR__ . '/../../public/' . $appLogo)): ?>
                    <img src="<?= base_url($appLogo) ?>" alt="شعار المطعم" class="w-10 h-10 rounded-xl object-contain bg-white p-0.5 shadow-lg border border-slate-700">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-indigo-600 flex items-center justify-center text-white shadow-lg">
                        <i class="fa-solid fa-utensils text-lg"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <h1 class="font-bold text-white text-base leading-tight truncate w-36"><?= htmlspecialchars($appName) ?></h1>
                    <span class="text-xs text-amber-400 font-semibold flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        نظام متكامل Pro
                    </span>
                </div>
            </div>
        </div>

        <!-- Current Shift Status Widget (Fixed) -->
        <?php if (has_role('admin', 'branch_manager', 'cashier')): ?>
            <div class="flex-shrink-0 px-4 py-3 bg-slate-850 mx-3 my-3 rounded-xl border border-slate-800/80">
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="text-slate-400">حالة الوردية:</span>
                    <?php if ($activeShift): ?>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            مفتوحة #<?= $activeShift['id'] ?>
                        </span>
                    <?php else: ?>
                        <span class="px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-400 font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                            مغلقة
                        </span>
                    <?php endif; ?>
                </div>
                <div class="text-[11px] text-slate-400 flex items-center justify-between">
                    <span>الفرع:</span>
                    <span class="text-slate-200 font-medium"><?= htmlspecialchars($branchName) ?></span>
                </div>
            </div>
        <?php else: ?>
            <div class="px-4 py-2.5 bg-slate-850 mx-3 my-3 rounded-xl border border-slate-800/80 text-xs">
                <div class="text-slate-400 flex items-center justify-between">
                    <span>الفرع النشط:</span>
                    <span class="text-indigo-300 font-bold"><?= htmlspecialchars($branchName) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Navigation Menu -->
        <nav class="flex-1 overflow-y-auto px-3 py-2 space-y-1 text-sm">
            
            <?php if (has_role('admin', 'branch_manager', 'accountant')): ?>
                <a href="<?= base_url() ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= (str_ends_with($currentUri, '/') || str_contains($currentUri, 'dashboard')) ? 'active-nav' : '' ?>">
                    <i class="fa-solid fa-chart-pie w-5 text-center text-base"></i>
                    <span>لوحة التحكم الرئيسية</span>
                </a>
            <?php endif; ?>

            <?php if (has_role('admin', 'branch_manager', 'cashier')): ?>
                <!-- POS Button highlighted -->
                <a href="<?= base_url('pos') ?>" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-bold transition hover:bg-slate-800 <?= str_contains($currentUri, 'pos') ? 'active-nav' : 'bg-gradient-to-r from-indigo-900/60 to-purple-900/40 text-indigo-300 border border-indigo-700/40' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-cash-register w-5 text-center text-base text-amber-400"></i>
                        <span>نقطة البيع (الكاشير)</span>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] bg-amber-500/20 text-amber-300 font-bold">POS</span>
                </a>
            <?php endif; ?>

            <?php if (has_role('admin', 'branch_manager', 'kitchen')): ?>
                <!-- Kitchen Display -->
                <a href="<?= base_url('kds') ?>" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'kds') ? 'active-nav' : '' ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-kitchen-set w-5 text-center text-base text-rose-400"></i>
                        <span>شاشة المطبخ (KDS)</span>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] bg-rose-500/20 text-rose-300 font-bold">مباشر</span>
                </a>
            <?php endif; ?>

            <?php if (has_role('admin', 'branch_manager', 'cashier', 'accountant')): ?>
                <!-- Orders -->
                <a href="<?= base_url('orders') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'orders') ? 'active-nav' : '' ?>">
                    <i class="fa-solid fa-receipt w-5 text-center text-base"></i>
                    <span>إدارة الطلبات والمبيعات</span>
                </a>
            <?php endif; ?>

            <?php if (has_role('admin', 'branch_manager', 'cashier')): ?>
                <!-- Dining Tables -->
                <a href="<?= base_url('tables') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'tables') ? 'active-nav' : '' ?>">
                    <i class="fa-solid fa-chair w-5 text-center text-base"></i>
                    <span>إدارة الصالة والطاولات</span>
                </a>
            <?php endif; ?>

            <?php if (has_role('admin', 'branch_manager', 'inventory_manager', 'kitchen', 'accountant')): ?>
                <div class="pt-3 pb-1 px-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">المنتجات والتكاليف</div>

                <?php if (has_role('admin', 'branch_manager')): ?>
                    <!-- Products -->
                    <a href="<?= base_url('products') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'products') ? 'active-nav' : '' ?>">
                        <i class="fa-solid fa-burger w-5 text-center text-base"></i>
                        <span>قائمة الوجبات والأصناف</span>
                    </a>

                    <!-- Categories -->
                    <a href="<?= base_url('categories') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'categories') ? 'active-nav' : '' ?>">
                        <i class="fa-solid fa-layer-group w-5 text-center text-base"></i>
                        <span>تصنيفات الطعام</span>
                    </a>
                <?php endif; ?>

                <?php if (has_role('admin', 'branch_manager', 'inventory_manager', 'accountant')): ?>
                    <!-- Inventory -->
                    <a href="<?= base_url('inventory') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'inventory') ? 'active-nav' : '' ?>">
                        <i class="fa-solid fa-boxes-stacked w-5 text-center text-base"></i>
                        <span>المخزون والمواد الخام</span>
                    </a>
                <?php endif; ?>

                <?php if (has_role('admin', 'branch_manager', 'kitchen', 'inventory_manager')): ?>
                    <!-- Recipes & Food Cost -->
                    <a href="<?= base_url('recipes') ?>" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'recipes') ? 'active-nav' : '' ?>">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-calculator w-5 text-center text-base text-emerald-400"></i>
                            <span>الوصفات وتكلفة الوجبات</span>
                        </div>
                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-emerald-500/20 text-emerald-400 font-bold">Food Cost</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (has_role('admin', 'branch_manager', 'inventory_manager', 'accountant')): ?>
                <div class="pt-3 pb-1 px-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">المشتريات والمالية</div>

                <!-- Purchases -->
                <a href="<?= base_url('purchases') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'purchases') ? 'active-nav' : '' ?>">
                    <i class="fa-solid fa-cart-shopping w-5 text-center text-base"></i>
                    <span>المشتريات وفواتير المواد</span>
                </a>

                <?php if (has_role('admin', 'branch_manager', 'accountant')): ?>
                    <!-- Suppliers -->
                    <a href="<?= base_url('suppliers') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'suppliers') ? 'active-nav' : '' ?>">
                        <i class="fa-solid fa-truck-field w-5 text-center text-base"></i>
                        <span>الموردين وحسابات الديون</span>
                    </a>

                    <!-- Financials & Profit/Loss -->
                    <a href="<?= base_url('financial') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'financial') ? 'active-nav' : '' ?>">
                        <i class="fa-solid fa-scale-balanced w-5 text-center text-base"></i>
                        <span>الأرباح والخسائر والمحاسبة</span>
                    </a>

                    <!-- Expenses -->
                    <a href="<?= base_url('expenses') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'expenses') ? 'active-nav' : '' ?>">
                        <i class="fa-solid fa-money-bill-transfer w-5 text-center text-base"></i>
                        <span>المصروفات التشغيلية</span>
                    </a>

                    <!-- Cash Drawer -->
                    <a href="<?= base_url('drawer') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'drawer') ? 'active-nav' : '' ?>">
                        <i class="fa-solid fa-vault w-5 text-center text-base"></i>
                        <span>حركة الصندوق والخزينة</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <div class="pt-3 pb-1 px-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">الموارد البشرية والتقارير</div>

            <?php if (has_role('admin', 'branch_manager')): ?>
                <!-- Employees -->
                <a href="<?= base_url('employees') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'employees') ? 'active-nav' : '' ?>">
                    <i class="fa-solid fa-users w-5 text-center text-base"></i>
                    <span>الموظفين والموارد البشرية</span>
                </a>
            <?php endif; ?>

            <!-- Attendance for all staff -->
            <a href="<?= base_url('attendance') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'attendance') ? 'active-nav' : '' ?>">
                <i class="fa-solid fa-user-clock w-5 text-center text-base"></i>
                <span>سجل الحضور والانصراف</span>
            </a>

            <?php if (has_role('admin', 'branch_manager', 'accountant')): ?>
                <!-- Payroll -->
                <a href="<?= base_url('payroll') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'payroll') ? 'active-nav' : '' ?>">
                    <i class="fa-solid fa-file-invoice-dollar w-5 text-center text-base"></i>
                    <span>مسيرات الرواتب</span>
                </a>

                <!-- Reports -->
                <a href="<?= base_url('reports') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'reports') ? 'active-nav' : '' ?>">
                    <i class="fa-solid fa-chart-line w-5 text-center text-base"></i>
                    <span>التقارير والإحصائيات</span>
                </a>
            <?php endif; ?>

            <?php if (has_role('admin')): ?>
                <!-- Settings -->
                <a href="<?= base_url('settings') ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition hover:bg-slate-800 hover:text-white <?= str_contains($currentUri, 'settings') ? 'active-nav' : '' ?>">
                    <i class="fa-solid fa-gear w-5 text-center text-base"></i>
                    <span>إعدادات النظام والأمان</span>
                </a>
            <?php endif; ?>
        </nav>

        <!-- User Profile Footer (Fixed) -->
        <div class="flex-shrink-0 p-3 bg-slate-950 border-t border-slate-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow">
                        <?= mb_substr($user['name'] ?? 'م', 0, 1) ?>
                    </div>
                    <div class="text-xs">
                        <div class="font-bold text-white"><?= htmlspecialchars($user['name'] ?? 'مستخدم') ?></div>
                        <div class="text-slate-400"><?= htmlspecialchars($roleName) ?></div>
                    </div>
                </div>
                <a href="<?= base_url('logout') ?>" class="p-2 text-slate-400 hover:text-rose-400 transition" title="تسجيل الخروج">
                    <i class="fa-solid fa-right-from-bracket text-base"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area (Independent scroll) -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden min-w-0">
        <!-- Top Navbar (Fixed) -->
        <header class="h-16 flex-shrink-0 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 shadow-sm">
            <div class="flex items-center gap-4">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <?= $pageTitle ?? 'نظام إدارة المطاعم' ?>
                </h2>
                <?php if (isset($pageSubtitle)): ?>
                    <span class="text-xs text-slate-400 border-r border-slate-300 pr-3 mr-1"><?= $pageSubtitle ?></span>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-3">
                <?php if (has_role('admin', 'branch_manager', 'cashier')): ?>
                    <!-- Quick POS Button -->
                    <a href="<?= base_url('pos') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white font-bold text-xs rounded-xl hover:bg-indigo-700 transition shadow-sm shadow-indigo-200">
                        <i class="fa-solid fa-cash-register"></i>
                        <span>نقطة البيع POS</span>
                    </a>
                <?php endif; ?>

                <?php if (has_role('admin', 'branch_manager', 'kitchen')): ?>
                    <!-- Quick Kitchen Button -->
                    <a href="<?= base_url('kds') ?>" class="inline-flex items-center gap-2 px-3.5 py-2 bg-rose-50 text-rose-600 border border-rose-200 font-bold text-xs rounded-xl hover:bg-rose-100 transition">
                        <i class="fa-solid fa-fire-burner"></i>
                        <span>شاشة المطبخ</span>
                    </a>
                <?php endif; ?>

                <!-- Fullscreen Toggle -->
                <button onclick="toggleFullScreen()" class="w-9 h-9 rounded-xl border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition" title="شاشة كاملة">
                    <i class="fa-solid fa-expand text-sm"></i>
                </button>

                <!-- Clock -->
                <div class="hidden sm:flex items-center gap-2 text-xs font-semibold bg-slate-100 text-slate-700 px-3 py-1.5 rounded-xl border border-slate-200">
                    <i class="fa-regular fa-clock text-indigo-600"></i>
                    <span id="liveClock"><?= date('H:i:s') ?></span>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php if ($flash): ?>
            <div class="flex-shrink-0 mx-6 mt-4">
                <div class="p-4 rounded-xl flex items-center gap-3 text-sm font-semibold shadow-sm border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : ($flash['type'] === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-amber-50 text-amber-800 border-amber-200') ?>">
                    <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check text-emerald-500' : ($flash['type'] === 'error' ? 'fa-circle-exclamation text-rose-500' : 'fa-triangle-exclamation text-amber-500') ?> text-lg"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Page Body -->
        <main class="flex-1 overflow-y-auto p-6 bg-slate-50">
            <?= $content ?? '' ?>
        </main>
    </div>

    <!-- Live Clock Script & Fullscreen -->
    <script>
        function updateClock() {
            const clockEl = document.getElementById('liveClock');
            if (clockEl) {
                const now = new Date();
                clockEl.textContent = now.toLocaleTimeString('ar-SA');
            }
        }
        setInterval(updateClock, 1000);

        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }
    </script>
</body>
</html>
