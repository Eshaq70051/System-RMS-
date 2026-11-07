<?php
$pageTitle = 'لوحة التحكم التنفيذية';
$pageSubtitle = 'نظرة شاملة ومباشرة على أداء المبيعات والمخزون والمالية';
ob_start();
?>

<!-- شريط التصفية والإجراءات السريعة -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div>
        <h3 class="text-xl font-bold text-slate-800">مرحباً بك، <?= htmlspecialchars(auth_user()['name'] ?? 'المستخدم') ?> </h3>
        <p class="text-xs text-slate-500 mt-0.5">ملخص النشاط التجاري لفرع: <span class="font-bold text-indigo-600"><?= htmlspecialchars($branchName ?? 'جميع الفروع') ?></span></p>
    </div>

    <!-- تصفية الفروع وبدء وردية جديدة -->
    <div class="flex items-center gap-3 w-full sm:w-auto">
        <form method="GET" action="<?= base_url() ?>" class="flex items-center gap-2">
            <select name="branch_id" onchange="this.form.submit()" class="bg-white border border-slate-200 text-xs font-semibold rounded-xl px-3 py-2 text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">جميع الفروع (شامل)</option>
                <?php foreach ($branches as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= ($branchId == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <a href="<?= base_url('pos') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold text-xs rounded-xl hover:from-emerald-700 hover:to-teal-700 transition shadow-md shadow-emerald-500/20">
            <i class="fa-solid fa-plus-circle"></i>
            <span>طلب كاشير جديد</span>
        </a>
    </div>
</div>

<!-- بطاقات مؤشرات الأداء الرئيسية -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">

    <!-- البطاقة الأولى: مبيعات اليوم -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">مبيعات اليوم</span>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
        </div>
        <div class="text-2xl font-black text-slate-900"><?= currency_format($todaySales) ?></div>
        <div class="flex items-center justify-between mt-2 text-xs">
            <span class="text-slate-500"><?= $todayOrdersCount ?> طلب اليوم</span>
            <span class="text-indigo-600 font-bold">متوسط: <?= currency_format($avgOrderValue) ?></span>
        </div>
    </div>

    <!-- البطاقة الثانية: مبيعات الشهر -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">إجمالي مبيعات الشهر</span>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-chart-line"></i>
            </div>
        </div>
        <div class="text-2xl font-black text-slate-900"><?= currency_format($monthSales) ?></div>
        <div class="flex items-center justify-between mt-2 text-xs">
            <span class="text-slate-500">صافي بعد المرتجع</span>
            <span class="text-emerald-600 font-bold"><?= currency_format($netSales) ?></span>
        </div>
    </div>

    <!-- البطاقة الثالثة: صافي الربح الفعلي -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">صافي الأرباح الحقيقية</span>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
        </div>
        <div class="text-2xl font-black <?= $netProfit >= 0 ? 'text-emerald-600' : 'text-rose-600' ?>">
            <?= currency_format($netProfit) ?>
        </div>
        <div class="flex items-center justify-between mt-2 text-xs">
            <span class="text-slate-500">المبيعات - (المخزون + المصاريف)</span>
            <span class="text-amber-600 font-bold"><?= $netSales > 0 ? round(($netProfit / $netSales) * 100, 1) : 0 ?>% هامش</span>
        </div>
    </div>

    <!-- البطاقة الرابعة: المصروفات التشغيلية -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">مصروفات هذا الشهر</span>
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-arrow-trend-down"></i>
            </div>
        </div>
        <div class="text-2xl font-black text-slate-900"><?= currency_format($monthExpenses) ?></div>
        <div class="flex items-center justify-between mt-2 text-xs">
            <span class="text-slate-500">تكلفة المواد المباعة</span>
            <span class="text-rose-600 font-bold"><?= currency_format($monthCOGS) ?></span>
        </div>
    </div>

</div>

<!-- مؤشرات حالات الطلبات -->
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 text-center">
        <span class="block text-xs font-bold text-slate-500">بانتظار التأكيد</span>
        <span class="text-lg font-black text-amber-500"><?= $pendingOrders ?></span>
    </div>
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 text-center">
        <span class="block text-xs font-bold text-slate-500">في المطبخ الآن</span>
        <span class="text-lg font-black text-blue-500"><?= $kitchenOrders ?></span>
    </div>
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 text-center">
        <span class="block text-xs font-bold text-slate-500">جاهز للتسليم</span>
        <span class="text-lg font-black text-emerald-500"><?= $readyOrders ?></span>
    </div>
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 text-center">
        <span class="block text-xs font-bold text-slate-500">طلبات مكتملة</span>
        <span class="text-lg font-black text-indigo-600"><?= $completedOrders ?></span>
    </div>
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 text-center">
        <span class="block text-xs font-bold text-slate-500">طلبات ملغاة</span>
        <span class="text-lg font-black text-rose-500"><?= $cancelledOrders ?></span>
    </div>
</div>

<!-- الرسوم البيانية وأبرز المؤشرات -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <!-- مخطط الأيام السبعة (يمتد على عمودين) -->
    <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h4 class="font-bold text-slate-800 text-base">منحنى المبيعات والأرباح اليومية (آخر 7 أيام)</h4>
                <p class="text-xs text-slate-400">مقارنة الإيرادات المحققة مع صافي الأرباح بعد تكلفة المواد والمصروفات</p>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-xs bg-indigo-50 text-indigo-600 font-bold">تحديث فوري</span>
        </div>
        <div class="h-64">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- المنتجات الأكثر مبيعاً وتنبيه انخفاض المخزون -->
    <div class="space-y-6">
        
        <!-- المنتجات الأكثر مبيعاً -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-3 border-b border-slate-100 pb-2">
                <h4 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-fire text-amber-500"></i>
                    <span>الأكثر مبيعاً</span>
                </h4>
                <a href="<?= base_url('products') ?>" class="text-[11px] text-indigo-600 hover:underline font-bold">عرض الكل</a>
            </div>
            <div class="space-y-2.5">
                <?php if (empty($topProducts)): ?>
                    <p class="text-xs text-slate-400 text-center py-4">لا توجد بيانات مبيعات حتى الآن</p>
                <?php else: ?>
                    <?php foreach ($topProducts as $idx => $tp): ?>
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 font-bold flex items-center justify-center text-[10px]">
                                    <?= $idx + 1 ?>
                                </span>
                                <span class="font-bold text-slate-800 truncate w-36"><?= htmlspecialchars($tp['product_name']) ?></span>
                            </div>
                            <span class="font-semibold text-emerald-600"><?= $tp['total_qty'] ?> طلب</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- تنبيه المواد منخفضة المخزون -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-3 border-b border-slate-100 pb-2">
                <h4 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-500"></i>
                    <span>تنبيهات نقص المخزون</span>
                </h4>
                <a href="<?= base_url('inventory?status=low') ?>" class="text-[11px] text-rose-600 hover:underline font-bold">شراء مواد</a>
            </div>
            <div class="space-y-2 text-xs">
                <?php if (empty($lowStockItems)): ?>
                    <div class="text-center py-3 text-emerald-600 font-medium text-xs flex items-center justify-center gap-1">
                        <i class="fa-solid fa-check-circle"></i>
                        <span>جميع المواد الخام بمستويات آمنة</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($lowStockItems as $lItem): ?>
                        <div class="flex items-center justify-between p-2 rounded-lg bg-rose-50/50 border border-rose-100">
                            <span class="font-bold text-slate-800"><?= htmlspecialchars($lItem['name']) ?></span>
                            <span class="font-bold text-rose-600 bg-rose-100 px-2 py-0.5 rounded text-[11px]">
                                <?= htmlspecialchars((string)$lItem['current_stock'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$lItem['unit'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<!-- جدول الطلبات الأخيرة -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <div>
            <h4 class="font-bold text-slate-800 text-base">آخر عمليات البيع والطلبات</h4>
            <p class="text-xs text-slate-400">سجل فوري ومحدث للطلبات المنفذة في النظام</p>
        </div>
        <a href="<?= base_url('orders') ?>" class="text-xs font-bold text-indigo-600 hover:underline flex items-center gap-1">
            <span>عرض كل الطلبات</span>
            <i class="fa-solid fa-arrow-left text-[10px]"></i>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                <tr>
                    <th class="py-3 px-4">رقم الطلب</th>
                    <th class="py-3 px-4">النوع / الطاولة</th>
                    <th class="py-3 px-4">الكاشير</th>
                    <th class="py-3 px-4">الإجمالي</th>
                    <th class="py-3 px-4">طريقة الدفع</th>
                    <th class="py-3 px-4">الحالة</th>
                    <th class="py-3 px-4">الوقت</th>
                    <th class="py-3 px-4 text-center">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($recentOrders)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-6 text-slate-400">لا توجد طلبات مسجلة بعد</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentOrders as $ro): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-bold text-indigo-600">
                                <a href="<?= base_url('orders/' . $ro['id']) ?>"><?= htmlspecialchars((string)$ro['order_number'], ENT_QUOTES, 'UTF-8') ?></a>
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-700">
                                <?php if ($ro['order_type'] === 'dine_in'): ?>
                                    <span class="inline-flex items-center gap-1 text-slate-800">
                                        <i class="fa-solid fa-chair text-indigo-500"></i>
                                        <span>طاولة <?= htmlspecialchars((string)($ro['table_number'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </span>
                                <?php elseif ($ro['order_type'] === 'takeaway'): ?>
                                    <span class="text-amber-600">سفري</span>
                                <?php else: ?>
                                    <span class="text-blue-600">توصيل</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($ro['cashier_name'] ?? 'كاشير') ?></td>
                            <td class="py-3 px-4 font-bold text-slate-900"><?= currency_format($ro['total_amount']) ?></td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $ro['payment_method'] === 'cash' ? 'bg-emerald-100 text-emerald-800' : ($ro['payment_method'] === 'card' ? 'bg-indigo-100 text-indigo-800' : 'bg-purple-100 text-purple-800') ?>">
                                    <?= $ro['payment_method'] === 'cash' ? 'نقدي' : ($ro['payment_method'] === 'card' ? 'بطاقة/شبكة' : 'مختلط') ?>
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <?php 
                                $statusClass = match($ro['status']) {
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'in_kitchen' => 'bg-blue-100 text-blue-800',
                                    'ready' => 'bg-teal-100 text-teal-800',
                                    'completed' => 'bg-emerald-100 text-emerald-800',
                                    'cancelled' => 'bg-rose-100 text-rose-800',
                                    default => 'bg-slate-100 text-slate-800'
                                };
                                $statusLabel = match($ro['status']) {
                                    'pending' => 'انتظار',
                                    'in_kitchen' => 'تحضير',
                                    'ready' => 'جاهز',
                                    'completed' => 'مكتمل',
                                    'cancelled' => 'ملغي',
                                    default => $ro['status']
                                };
                                ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $statusClass ?>">
                                    <?= htmlspecialchars((string)$statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-500 font-mono text-[11px]"><?= date('H:i', strtotime($ro['created_at'])) ?></td>
                            <td class="py-3 px-4 text-center">
                                <a href="<?= base_url('orders/' . $ro['id']) ?>" class="p-1.5 text-slate-400 hover:text-indigo-600 transition" title="عرض التفاصيل">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="<?= base_url('pos/receipt/' . $ro['id']) ?>" target="_blank" class="p-1.5 text-slate-400 hover:text-amber-600 transition" title="طباعة الفاتورة">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- تهيئة مخطط المبيعات -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartCanvas = document.getElementById('salesChart');
        if (!chartCanvas || typeof Chart === 'undefined') {
            return;
        }

        const ctx = chartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartDays, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                datasets: [
                    {
                        label: <?= json_encode('إجمالي المبيعات (' . $currencySymbol . ')', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                        data: <?= json_encode($chartSales, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.08)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#4f46e5',
                        pointRadius: 4
                    },
                    {
                        label: <?= json_encode('صافي الأرباح (' . $currencySymbol . ')', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                        data: <?= json_encode($chartProfits, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        tension: 0.4,
                        borderWidth: 2,
                        pointBackgroundColor: '#10b981',
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: 'Cairo', weight: 'bold', size: 11 }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { font: { family: 'Cairo' } },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        ticks: { font: { family: 'Cairo' } },
                        grid: { display: false }
                    }
                }
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
