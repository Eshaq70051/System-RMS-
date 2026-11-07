<?php
$pageTitle = 'شاشة الكاشير ونقاط البيع (POS)';
$pageSubtitle = 'واجهة كاشير سريعة وعملية للطلبات المباشرة والمحاسبة الفورية';
$currencySymbol = get_setting('currency_symbol', 'ر.س');
$taxRate = (float)get_setting('tax_rate', 15.0);
$taxEnabled = (int)get_setting('tax_enabled', 1);
ob_start();
?>

<!-- POS Container -->
<div class="flex flex-col lg:flex-row gap-5 h-[calc(100vh-130px)] -m-2">

    <!-- Right Side: Products Catalog & Categories (65% width) -->
    <div class="flex-1 flex flex-col bg-white rounded-2xl border border-slate-200 shadow-sm p-4 overflow-hidden">
        
        <!-- Search & Barcode Bar -->
        <div class="flex items-center gap-3 mb-3">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-barcode text-base"></i>
                </span>
                <input type="text" id="posSearch" placeholder="امسح الباركود أو ابحث باسم الوجبة..." 
                    class="w-full pr-10 pl-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none transition font-medium">
            </div>

            <!-- Shift Status Button -->
            <button onclick="openCloseShiftModal()" class="px-3.5 py-2.5 rounded-xl bg-amber-50 text-amber-700 hover:bg-amber-100 font-bold text-xs border border-amber-200 transition flex items-center gap-2" title="إغلاق وتسوية وردية الكاشير">
                <i class="fa-solid fa-lock text-xs"></i>
                <span>إغلاق وتسوية الوردية</span>
            </button>

            <!-- Shift Performance & Cash Drawer Report Button -->
            <button onclick="openShiftReportModal()" class="px-3.5 py-2.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs border border-indigo-200 transition flex items-center gap-2 shadow-sm" title="تقرير ورديتي ومبيعاتي وحساب العجز والزيادة">
                <i class="fa-solid fa-chart-pie text-indigo-600"></i>
                <span>تقرير ورديتي اليوم</span>
            </button>

            <!-- Held Orders Button -->
            <button onclick="showHeldOrdersModal()" class="relative px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs border border-slate-200 transition flex items-center gap-1.5" title="الطلبات المعلقة">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span id="heldOrdersCountBadge" class="hidden px-1.5 py-0.2 rounded-full bg-rose-500 text-white text-[10px]">0</span>
            </button>
        </div>

        <!-- Categories Horizontal Pills -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-3 no-scrollbar">
            <button onclick="filterCategory('all', this)" class="cat-pill active-cat px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 bg-indigo-600 text-white shadow-sm shadow-indigo-200">
                <i class="fa-solid fa-border-all"></i>
                <span>الكل</span>
            </button>
            <?php foreach ($categories as $cat): ?>
                <button onclick="filterCategory('<?= $cat['id'] ?>', this)" class="cat-pill px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/60">
                    <i class="fa-solid fa-<?= $cat['icon'] ?: 'utensils' ?>" style="color: <?= $cat['color'] ?>;"></i>
                    <span><?= htmlspecialchars($cat['name']) ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Products Grid -->
        <div id="productsGrid" class="flex-1 overflow-y-auto grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3.5 p-1">
            <?php foreach ($products as $prod): ?>
                <div onclick="addProductToCart(<?= htmlspecialchars(json_encode($prod)) ?>)" 
                    data-cat="<?= $prod['category_id'] ?>" 
                    data-name="<?= htmlspecialchars(mb_strtolower($prod['name'])) ?>"
                    data-barcode="<?= htmlspecialchars($prod['barcode'] ?? '') ?>"
                    class="product-card group bg-white border border-slate-200 hover:border-indigo-500 rounded-2xl p-3 flex flex-col justify-between cursor-pointer transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 select-none relative overflow-hidden">
                    
                    <?php if ($prod['has_recipe']): ?>
                        <span class="absolute top-2 left-2 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded px-1.5 py-0.5 text-[9px] font-bold" title="مرتبط بوصفة تخصم المخزون تلقائياً">
                            وصفة
                        </span>
                    <?php endif; ?>

                    <div>
                        <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xl mb-2 mx-auto group-hover:scale-110 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition">
                            <i class="fa-solid fa-utensils"></i>
                        </div>
                        <h5 class="font-bold text-slate-800 text-xs text-center line-clamp-2 leading-tight min-h-[32px]"><?= htmlspecialchars($prod['name']) ?></h5>
                    </div>

                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="font-black text-indigo-600"><?= number_format($prod['price'], 2) ?> <?= $currencySymbol ?></span>
                        <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs group-hover:bg-indigo-600 group-hover:text-white transition">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- Left Side: Order Ticket / Cart (35% width) -->
    <div class="w-full lg:w-96 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden">
        
        <!-- Cart Header & Order Type Tabs -->
        <div class="p-3.5 border-b border-slate-100 bg-slate-50">
            <div class="grid grid-cols-3 gap-1.5 p-1 bg-slate-200/70 rounded-xl mb-3 text-xs font-bold text-slate-600">
                <button type="button" onclick="setOrderType('dine_in', this)" class="order-type-btn active-type py-1.5 rounded-lg transition bg-white text-indigo-700 shadow-sm">
                    <i class="fa-solid fa-chair text-xs ml-1"></i> صالة
                </button>
                <button type="button" onclick="setOrderType('takeaway', this)" class="order-type-btn py-1.5 rounded-lg transition hover:text-slate-900">
                    <i class="fa-solid fa-bag-shopping text-xs ml-1"></i> سفري
                </button>
                <button type="button" onclick="setOrderType('delivery', this)" class="order-type-btn py-1.5 rounded-lg transition hover:text-slate-900">
                    <i class="fa-solid fa-motorcycle text-xs ml-1"></i> توصيل
                </button>
            </div>

            <!-- Dine-in Table Picker or Delivery customer details -->
            <div id="dineInFields" class="flex items-center gap-2">
                <select id="selectedTableId" class="flex-1 bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- اختر رقم الطاولة --</option>
                    <?php foreach ($tables as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $t['status'] === 'occupied' ? 'class="text-rose-500"' : '' ?>>
                            طاولة <?= $t['table_number'] ?> (<?= $t['section'] ?>) <?= $t['status'] === 'occupied' ? '[مشغولة]' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" id="customerName" placeholder="اسم العميل (اختياري)" class="w-36 bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div id="deliveryFields" class="hidden space-y-2 mt-2">
                <input type="text" id="deliveryAddress" placeholder="عنوان التوصيل بالتفصيل..." class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none">
                <input type="text" id="customerPhone" placeholder="رقم هاتف العميل..." class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none">
            </div>
        </div>

        <!-- Cart Items List -->
        <div id="cartItemsContainer" class="flex-1 overflow-y-auto p-3.5 space-y-2">
            <!-- Empty state placeholder -->
            <div id="cartEmptyState" class="h-full flex flex-col items-center justify-center text-slate-400 py-12">
                <i class="fa-solid fa-cart-shopping text-4xl mb-3 text-slate-300"></i>
                <p class="text-xs font-semibold">سلة الطلب فارغة</p>
                <span class="text-[11px] text-slate-400 mt-1">انقر على الأصناف لإضافتها للفاتورة</span>
            </div>
        </div>

        <!-- Cart Summary & Calculations -->
        <div class="p-3.5 bg-slate-50 border-t border-slate-100 space-y-2 text-xs">
            
            <!-- Discount & Notes row -->
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <input type="number" step="0.5" min="0" id="cartDiscount" onchange="calculateCartTotals()" placeholder="خصم (<?= $currencySymbol ?>)" 
                        class="w-full pl-2 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:outline-none">
                </div>
                <input type="text" id="kitchenNotes" placeholder="ملاحظات المطبخ..." class="flex-1 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:outline-none">
            </div>

            <div class="pt-2 border-t border-slate-200/60 space-y-1.5 font-semibold text-slate-600">
                <div class="flex justify-between">
                    <span>المجموع الفرعي:</span>
                    <span id="cartSubtotal">0.00 <?= $currencySymbol ?></span>
                </div>
                <div class="flex justify-between text-rose-600">
                    <span>الخصم:</span>
                    <span id="cartDiscountText">0.00 <?= $currencySymbol ?></span>
                </div>
                <?php if ($taxEnabled): ?>
                <div class="flex justify-between text-slate-500 text-[11px]">
                    <span>ضريبة القيمة المضافة (<?= $taxRate ?>%):</span>
                    <span id="cartTax">0.00 <?= $currencySymbol ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between items-center text-sm font-black text-slate-900 pt-2 border-t border-slate-200">
                    <span>المجموع الكلي:</span>
                    <span id="cartGrandTotal" class="text-indigo-600 text-lg">0.00 <?= $currencySymbol ?></span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-3 gap-2 pt-2">
                <button type="button" onclick="clearCart()" class="py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs transition" title="مسح السلة">
                    <i class="fa-solid fa-trash-can"></i> مسح
                </button>
                <button type="button" onclick="holdCurrentOrder()" class="py-2.5 rounded-xl bg-amber-100 hover:bg-amber-200 text-amber-800 font-bold text-xs transition" title="تعليق الطلب">
                    <i class="fa-solid fa-pause"></i> تعليق
                </button>
                <button type="button" onclick="openPaymentModal()" class="col-span-1 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-black text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-check"></i> دفع
                </button>
            </div>

        </div>

    </div>

</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-150">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <h4 class="font-black text-slate-800 text-base flex items-center gap-2">
                <i class="fa-solid fa-credit-card text-emerald-600"></i>
                <span>إتمام الدفع والمحاسبة</span>
            </h4>
            <button onclick="closePaymentModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="text-center py-3 bg-indigo-50/70 rounded-2xl mb-4 border border-indigo-100">
            <span class="text-xs font-bold text-indigo-700 block">المبلغ المطلوب سداده</span>
            <span id="payModalDueAmount" class="text-3xl font-black text-indigo-700">0.00 <?= $currencySymbol ?></span>
        </div>

        <!-- Payment Method Options -->
        <div class="grid grid-cols-3 gap-2.5 mb-4">
            <button type="button" onclick="selectPayMethod('cash', this)" class="pay-method-btn active-method p-3 rounded-2xl border-2 border-indigo-600 bg-indigo-50/40 text-center font-bold text-xs text-indigo-900 transition">
                <i class="fa-solid fa-money-bill-wave text-xl block mb-1 text-emerald-600"></i>
                <span>نقدي (كاش)</span>
            </button>
            <button type="button" onclick="selectPayMethod('card', this)" class="pay-method-btn p-3 rounded-2xl border-2 border-slate-200 hover:border-slate-300 text-center font-bold text-xs text-slate-700 transition">
                <i class="fa-solid fa-credit-card text-xl block mb-1 text-blue-600"></i>
                <span>شبكة / مدى</span>
            </button>
            <button type="button" onclick="selectPayMethod('split', this)" class="pay-method-btn p-3 rounded-2xl border-2 border-slate-200 hover:border-slate-300 text-center font-bold text-xs text-slate-700 transition">
                <i class="fa-solid fa-arrows-split-up-and-left text-xl block mb-1 text-purple-600"></i>
                <span>دفع مختلط</span>
            </button>
        </div>

        <!-- Quick Cash Shortcuts -->
        <div id="quickCashSection" class="space-y-3 mb-4">
            <span class="text-xs font-bold text-slate-500">مبالغ نقدية سريعة:</span>
            <div class="grid grid-cols-5 gap-2 text-xs font-bold">
                <button type="button" onclick="setQuickCash('exact')" class="py-2 bg-slate-100 hover:bg-indigo-100 rounded-xl text-slate-800 transition">المطابق</button>
                <button type="button" onclick="setQuickCash(50)" class="py-2 bg-slate-100 hover:bg-indigo-100 rounded-xl text-slate-800 transition">50</button>
                <button type="button" onclick="setQuickCash(100)" class="py-2 bg-slate-100 hover:bg-indigo-100 rounded-xl text-slate-800 transition">100</button>
                <button type="button" onclick="setQuickCash(200)" class="py-2 bg-slate-100 hover:bg-indigo-100 rounded-xl text-slate-800 transition">200</button>
                <button type="button" onclick="setQuickCash(500)" class="py-2 bg-slate-100 hover:bg-indigo-100 rounded-xl text-slate-800 transition">500</button>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-slate-600 mb-1">المبلغ المستلم من العميل</label>
                    <input type="number" step="0.5" id="paidAmountInput" oninput="calculateChange()" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-base font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-bold text-slate-600 mb-1">المتبقي للعميل (الباقي)</label>
                    <input type="text" id="changeAmountInput" readonly value="0.00 <?= $currencySymbol ?>" class="w-full px-3 py-2.5 bg-emerald-50 border border-emerald-200 rounded-xl text-base font-black text-emerald-700 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Split Payment fields -->
        <div id="splitCashSection" class="hidden grid grid-cols-2 gap-3 mb-4">
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">المبلغ كاش</label>
                <input type="number" step="0.5" id="splitCashInput" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">المبلغ شبكة</label>
                <input type="number" step="0.5" id="splitCardInput" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
            </div>
        </div>

        <button type="button" onclick="submitFinalCheckout()" id="btnSubmitOrder" class="w-full py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-black rounded-2xl text-sm transition shadow-lg shadow-emerald-500/25 flex items-center justify-center gap-2">
            <i class="fa-solid fa-print"></i>
            <span>تأكيد الطلب وطباعة الفاتورة</span>
        </button>
    </div>
</div>

<!-- Close Shift Modal -->
<div id="closeShiftModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <h4 class="font-black text-slate-800 text-base mb-1">إغلاق وتسوية وردية الكاشير</h4>
        <p class="text-xs text-slate-400 mb-4">احسب النقد الفعلي في الدرج وسيقوم النظام بحساب العجز أو الزيادة تلقائياً</p>
        
        <form onsubmit="handleCloseShift(event)" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">النقد الفعلي في الصندوق الآن (<?= $currencySymbol ?>)</label>
                <input type="number" step="0.5" id="closingCashInput" required placeholder="0.00" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-base font-black text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">ملاحظات الإغلاق</label>
                <input type="text" id="closingNotesInput" placeholder="أية ملاحظات حول عجز أو زيادة..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none">
            </div>
            <div class="flex items-center gap-2 pt-2">
                <button type="button" onclick="closeCloseShiftModal()" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">إلغاء</button>
                <button type="submit" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-md shadow-rose-300">إغلاق الوردية واعتمادها</button>
            </div>
        </form>
    </div>
</div>

<!-- Cashier Shift Performance & Shortage/Surplus Modal (تقرير وردية الكاشير والعجز والزيادة) -->
<div id="shiftReportModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 max-h-[92vh] flex flex-col">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="font-black text-slate-800 text-base" id="srCashierNameTitle">تقرير وردية الكاشير</h4>
                        <span id="srShiftStatusBadge" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">نشطة الآن</span>
                    </div>
                    <p class="text-xs text-slate-400">وردية رقم #<span id="srShiftIdDisplay">-</span> | بدء العمل: <span id="srShiftStartTimeDisplay">-</span></p>
                </div>
            </div>
            <button type="button" onclick="closeShiftReportModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="flex-1 overflow-y-auto py-4 space-y-4 pr-1">
            
            <!-- Key Metrics Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <!-- Hours Worked -->
                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-2xl">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1 flex items-center gap-1.5">
                        <i class="fa-regular fa-clock text-amber-500"></i>
                        <span>مدة العمل اليوم</span>
                    </span>
                    <h5 id="srDurationText" class="text-sm font-black text-slate-800">-</h5>
                </div>

                <!-- Orders Count -->
                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-2xl">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-receipt text-indigo-500"></i>
                        <span>الطلبات المنجزة</span>
                    </span>
                    <h5 id="srOrdersCountText" class="text-base font-black text-slate-800">0 طلب</h5>
                </div>

                <!-- Total Sales -->
                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-2xl">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-coins text-emerald-500"></i>
                        <span>إجمالي المبيعات</span>
                    </span>
                    <h5 id="srTotalSalesText" class="text-base font-black text-emerald-600">0.00 <?= $currencySymbol ?></h5>
                </div>

                <!-- Difference / Status -->
                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-2xl">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-scale-balanced text-purple-500"></i>
                        <span>حالة الصندوق</span>
                    </span>
                    <div id="srDiffBadgeContainer">
                        <span class="text-xs font-bold text-slate-600">قيد الحساب...</span>
                    </div>
                </div>
            </div>

            <!-- Payment Breakdown & Expected Cash (تفصيل المبالغ والمطلوب على الكاشير) -->
            <div class="bg-gradient-to-br from-slate-900 to-indigo-950 text-white p-4 rounded-2xl shadow-md">
                <h6 class="text-xs font-black text-indigo-200 mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-cash-register"></i>
                    <span>حساب المبالغ والعهدة المستحقة على الكاشير</span>
                </h6>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs mb-3 pb-3 border-b border-indigo-900/60">
                    <div class="bg-white/5 p-2.5 rounded-xl border border-white/10">
                        <span class="text-slate-400 block text-[10px] mb-0.5">💵 مبيعات نقداً (كاش):</span>
                        <b id="srCashSalesText" class="text-sm font-black text-emerald-400">0.00 <?= $currencySymbol ?></b>
                    </div>
                    <div class="bg-white/5 p-2.5 rounded-xl border border-white/10">
                        <span class="text-slate-400 block text-[10px] mb-0.5">💳 مبيعات شبكة / حوالة:</span>
                        <b id="srCardSalesText" class="text-sm font-black text-cyan-400">0.00 <?= $currencySymbol ?></b>
                    </div>
                    <div class="bg-white/5 p-2.5 rounded-xl border border-white/10">
                        <span class="text-slate-400 block text-[10px] mb-0.5">↩️ إجمالي المرتجعات:</span>
                        <b id="srReturnsText" class="text-sm font-black text-rose-400">0.00 <?= $currencySymbol ?></b>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div class="bg-white/10 p-2.5 rounded-xl">
                        <span class="text-indigo-200 block text-[10px] mb-0.5">عهدة بداية الوردية (الافتتاحي):</span>
                        <b id="srOpeningCashText" class="text-sm font-black text-white">0.00 <?= $currencySymbol ?></b>
                    </div>
                    <div class="bg-indigo-600/30 border border-indigo-400/40 p-2.5 rounded-xl">
                        <span class="text-indigo-200 block text-[10px] mb-0.5">إجمالي المبلغ المطلوب بالدرج:</span>
                        <b id="srExpectedCashText" class="text-base font-black text-amber-300">0.00 <?= $currencySymbol ?></b>
                        <span class="text-[9px] text-indigo-300 block">(الافتتاحي + كاش - المرتجع)</span>
                    </div>
                    <div class="bg-white/10 p-2.5 rounded-xl">
                        <span class="text-indigo-200 block text-[10px] mb-0.5">النقد الفعلي بالصندوق:</span>
                        <b id="srActualCashText" class="text-sm font-black text-white">0.00 <?= $currencySymbol ?></b>
                        <span id="srActualCashSub" class="text-[9px] text-slate-300 block">عند الإغلاق</span>
                    </div>
                </div>
            </div>

            <!-- Orders Table in Current Shift -->
            <div class="border border-slate-200 rounded-2xl overflow-hidden">
                <div class="p-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <h6 class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                        <i class="fa-solid fa-list-check text-indigo-600"></i>
                        <span>سجل فواتير الوردية الحالية</span>
                    </h6>
                    <span id="srOrdersBadge" class="text-[11px] font-bold text-slate-500">0 فاتورة</span>
                </div>
                <div class="max-h-48 overflow-y-auto">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-slate-100 text-slate-600 sticky top-0">
                            <tr>
                                <th class="p-2 font-bold">رقم الفاتورة</th>
                                <th class="p-2 font-bold">النوع</th>
                                <th class="p-2 font-bold">طريقة الدفع</th>
                                <th class="p-2 font-bold">الوقت</th>
                                <th class="p-2 font-bold text-left">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody id="srOrdersTableBody" class="divide-y divide-slate-100 text-slate-700">
                            <tr>
                                <td colspan="5" class="p-4 text-center text-slate-400">جاري تحميل الفواتير...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Modal Footer Actions -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
            <button type="button" onclick="closeShiftReportModal()" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                إغلاق
            </button>
            <div class="flex items-center gap-2">
                <a id="srPrintBtn" href="<?= base_url('pos/shift-report/print') ?>" target="_blank" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-300 transition flex items-center gap-2">
                    <i class="fa-solid fa-print"></i>
                    <span>طباعة إيصال الوردية (Z-Report)</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- POS Interactive JavaScript Engine -->
<script>
    let cart = [];
    let currentOrderType = 'dine_in';
    let currentPayMethod = 'cash';
    let activeShift = <?= json_encode($activeShift) ?>;
    const taxRate = <?= $taxRate ?>;
    const taxEnabled = <?= $taxEnabled ?>;
    const currency = '<?= $currencySymbol ?>';
    const csrfToken = '<?= csrf_token() ?>';

    // 1. Add Product to Cart
    function addProductToCart(product) {
        const existingIndex = cart.findIndex(item => item.product_id === product.id);
        if (existingIndex > -1) {
            cart[existingIndex].quantity += 1;
        } else {
            cart.push({
                product_id: product.id,
                name: product.name,
                unit_price: parseFloat(product.price),
                cost_price: parseFloat(product.cost_price || 0),
                quantity: 1,
                notes: '',
                addons: []
            });
        }
        renderCart();
    }

    // 2. Render Cart Items
    function renderCart() {
        const container = document.getElementById('cartItemsContainer');

        if (cart.length === 0) {
            container.innerHTML = `
                <div id="cartEmptyState" class="h-full flex flex-col items-center justify-center text-slate-400 py-12">
                    <i class="fa-solid fa-cart-shopping text-4xl mb-3 text-slate-300"></i>
                    <p class="text-xs font-semibold">سلة الطلب فارغة</p>
                    <span class="text-[11px] text-slate-400 mt-1">انقر على الأصناف لإضافتها للفاتورة</span>
                </div>
            `;
        } else {
            let html = '';
            cart.forEach((item, index) => {
                const totalItemPrice = (item.unit_price * item.quantity).toFixed(2);
                html += `
                    <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl border border-slate-200/70 text-xs">
                        <div class="flex-1 min-w-0 pr-2">
                            <h6 class="font-bold text-slate-800 truncate">${item.name}</h6>
                            <span class="text-slate-400 text-[11px] font-semibold">${item.unit_price.toFixed(2)} ${currency}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button onclick="updateQty(${index}, -1)" class="w-6 h-6 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold flex items-center justify-center transition">-</button>
                            <span class="w-6 text-center font-black text-slate-800 text-xs">${item.quantity}</span>
                            <button onclick="updateQty(${index}, 1)" class="w-6 h-6 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold flex items-center justify-center transition">+</button>
                            <span class="font-black text-indigo-600 w-16 text-left mr-1">${totalItemPrice}</span>
                            <button onclick="removeItem(${index})" class="text-slate-400 hover:text-rose-500 p-1 transition"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        calculateCartTotals();
    }

    function updateQty(index, delta) {
        if (cart[index]) {
            cart[index].quantity += delta;
            if (cart[index].quantity <= 0) {
                cart.splice(index, 1);
            }
            renderCart();
        }
    }

    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function clearCart() {
        if (cart.length === 0) return;
        Swal.fire({
            title: 'هل تريد إفراغ السلة؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، إفراغ',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                cart = [];
                renderCart();
            }
        });
    }

    // 3. Totals Calculation
    function calculateCartTotals() {
        let subtotal = 0;
        cart.forEach(item => {
            subtotal += item.unit_price * item.quantity;
        });

        const discountInput = parseFloat(document.getElementById('cartDiscount').value) || 0;
        const discountAmount = Math.min(discountInput, subtotal);
        const afterDiscount = Math.max(0, subtotal - discountAmount);
        const taxAmount = taxEnabled ? (afterDiscount * (taxRate / 100)) : 0;
        const grandTotal = afterDiscount + taxAmount;

        document.getElementById('cartSubtotal').textContent = subtotal.toFixed(2) + ' ' + currency;
        document.getElementById('cartDiscountText').textContent = discountAmount.toFixed(2) + ' ' + currency;
        if (taxEnabled) {
            document.getElementById('cartTax').textContent = taxAmount.toFixed(2) + ' ' + currency;
        }
        document.getElementById('cartGrandTotal').textContent = grandTotal.toFixed(2) + ' ' + currency;

        return { subtotal, discountAmount, taxAmount, grandTotal };
    }

    // 4. Order Type Switching
    function setOrderType(type, btn) {
        currentOrderType = type;
        document.querySelectorAll('.order-type-btn').forEach(b => {
            b.classList.remove('active-type', 'bg-white', 'text-indigo-700', 'shadow-sm');
        });
        btn.classList.add('active-type', 'bg-white', 'text-indigo-700', 'shadow-sm');

        const dineInFields = document.getElementById('dineInFields');
        const deliveryFields = document.getElementById('deliveryFields');

        if (type === 'dine_in') {
            dineInFields.classList.remove('hidden');
            deliveryFields.classList.add('hidden');
        } else if (type === 'delivery') {
            dineInFields.classList.add('hidden');
            deliveryFields.classList.remove('hidden');
        } else {
            dineInFields.classList.add('hidden');
            deliveryFields.classList.add('hidden');
        }
    }

    // 5. Payment Modal Handling
    function openPaymentModal() {
        if (cart.length === 0) {
            Swal.fire('تنبيه', 'سلة الطلب فارغة، أضف بعض الوجبات أولاً!', 'info');
            return;
        }

        const totals = calculateCartTotals();
        document.getElementById('payModalDueAmount').textContent = totals.grandTotal.toFixed(2) + ' ' + currency;
        document.getElementById('paidAmountInput').value = totals.grandTotal.toFixed(2);
        calculateChange();
        document.getElementById('paymentModal').classList.remove('hidden');
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').classList.add('hidden');
    }

    function selectPayMethod(method, btn) {
        currentPayMethod = method;
        document.querySelectorAll('.pay-method-btn').forEach(b => {
            b.classList.remove('active-method', 'border-indigo-600', 'bg-indigo-50/40');
            b.classList.add('border-slate-200');
        });
        btn.classList.add('active-method', 'border-indigo-600', 'bg-indigo-50/40');
        btn.classList.remove('border-slate-200');

        const quickSection = document.getElementById('quickCashSection');
        const splitSection = document.getElementById('splitCashSection');

        if (method === 'split') {
            quickSection.classList.add('hidden');
            splitSection.classList.remove('hidden');
            const totals = calculateCartTotals();
            document.getElementById('splitCashInput').value = (totals.grandTotal / 2).toFixed(2);
            document.getElementById('splitCardInput').value = (totals.grandTotal / 2).toFixed(2);
        } else {
            quickSection.classList.remove('hidden');
            splitSection.classList.add('hidden');
        }
    }

    function setQuickCash(amount) {
        const totals = calculateCartTotals();
        if (amount === 'exact') {
            document.getElementById('paidAmountInput').value = totals.grandTotal.toFixed(2);
        } else {
            document.getElementById('paidAmountInput').value = amount;
        }
        calculateChange();
    }

    function calculateChange() {
        const totals = calculateCartTotals();
        const paid = parseFloat(document.getElementById('paidAmountInput').value) || 0;
        const change = Math.max(0, paid - totals.grandTotal);
        document.getElementById('changeAmountInput').value = change.toFixed(2) + ' ' + currency;
    }

    // 6. Submit Final Checkout
    async function submitFinalCheckout() {
        const btn = document.getElementById('btnSubmitOrder');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري حفظ الطلب والخصم من المخزون...';

        const totals = calculateCartTotals();
        const paidAmount = parseFloat(document.getElementById('paidAmountInput').value) || totals.grandTotal;

        const orderPayload = {
            order_type: currentOrderType,
            table_id: document.getElementById('selectedTableId').value,
            customer_name: document.getElementById('customerName').value,
            customer_phone: document.getElementById('customerPhone').value,
            delivery_address: document.getElementById('deliveryAddress').value,
            payment_method: currentPayMethod,
            paid_amount: paidAmount,
            cash_amount: currentPayMethod === 'split' ? parseFloat(document.getElementById('splitCashInput').value) : (currentPayMethod === 'cash' ? totals.grandTotal : 0),
            card_amount: currentPayMethod === 'split' ? parseFloat(document.getElementById('splitCardInput').value) : (currentPayMethod === 'card' ? totals.grandTotal : 0),
            discount_value: totals.discountAmount,
            kitchen_notes: document.getElementById('kitchenNotes').value,
            items: cart
        };

        try {
            const response = await fetch('<?= base_url('pos/checkout') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(orderPayload)
            });

            const result = await response.json();

            if (result.success) {
                closePaymentModal();

                // Clear current cart immediately
                cart = [];
                document.getElementById('cartDiscount').value = '';
                document.getElementById('kitchenNotes').value = '';
                renderCart();

                // Auto-open print window with direct print & PDF option
                const receiptUrl = `<?= base_url('pos/receipt/') ?>${result.order_id}?autoprint=1`;
                window.open(receiptUrl, '_blank', 'width=460,height=700');

                Swal.fire({
                    title: 'تم إتمام الطلب بنجاح! 🎉',
                    html: `
                        <div class="text-right text-xs space-y-2.5 py-1">
                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                                <div class="flex justify-between py-0.5"><span>رقم الفاتورة:</span><b class="font-mono text-indigo-600">${result.order_number}</b></div>
                                <div class="flex justify-between py-0.5"><span>الإجمالي:</span><b class="text-slate-800">${result.grand_total} ${currency}</b></div>
                                <div class="flex justify-between py-0.5"><span>الباقي للعميل:</span><b class="text-emerald-600">${result.change_amount} ${currency}</b></div>
                            </div>
                            <div class="p-2.5 bg-indigo-50/80 text-indigo-900 rounded-2xl border border-indigo-200/80 text-[11px] leading-relaxed">
                                <b>🖨️ حالة الطباعة:</b><br>
                                • تم إرسال أمر الطباعة تلقائياً للطابعة المحددة.<br>
                                • إذا لم تكن الطابعة متصلة، يمكنك اختيار <b>حفظ بتنسيق PDF (Save as PDF)</b>.
                            </div>
                        </div>
                    `,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'إعادة فتح الفاتورة / PDF 🖨️',
                    cancelButtonText: 'طلب جديد ⚡',
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#10b981'
                }).then((r) => {
                    if (r.isConfirmed) {
                        window.open(receiptUrl, '_blank', 'width=460,height=700');
                    }
                });
            } else {
                Swal.fire('خطأ', result.message || 'حدث خطأ أثناء حفظ الطلب', 'error');
            }
        } catch (e) {
            Swal.fire('خطأ', 'تعذر الاتصال بالخادم: ' + e.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-print"></i> تأكيد الطلب وطباعة الفاتورة';
        }
    }

    // 7. Filter Products by Category or Search
    function filterCategory(catId, btn) {
        document.querySelectorAll('.cat-pill').forEach(b => {
            b.classList.remove('bg-indigo-600', 'text-white', 'shadow-sm', 'shadow-indigo-200');
            b.classList.add('bg-slate-100', 'text-slate-700');
        });
        btn.classList.add('bg-indigo-600', 'text-white', 'shadow-sm', 'shadow-indigo-200');
        btn.classList.remove('bg-slate-100', 'text-slate-700');

        const cards = document.querySelectorAll('.product-card');
        cards.forEach(card => {
            if (catId === 'all' || card.getAttribute('data-cat') === catId) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    document.getElementById('posSearch').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.product-card');
        cards.forEach(card => {
            const name = card.getAttribute('data-name');
            const barcode = card.getAttribute('data-barcode');
            if (name.includes(query) || barcode.includes(query)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // 8. Shift Modals
    function openOpenShiftModal() { document.getElementById('openShiftModal').classList.remove('hidden'); }
    function closeOpenShiftModal() { document.getElementById('openShiftModal').classList.add('hidden'); }
    function openCloseShiftModal() { document.getElementById('closeShiftModal').classList.remove('hidden'); }
    function closeCloseShiftModal() { document.getElementById('closeShiftModal').classList.add('hidden'); }

    async function handleOpenShift(e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('opening_cash', document.getElementById('openingCashInput').value);
        formData.append('notes', document.getElementById('openingNotesInput').value);
        formData.append('csrf_token', csrfToken);

        const res = await fetch('<?= base_url('pos/open-shift') ?>', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            Swal.fire('تم بنجاح', data.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('تنبيه', data.message, 'error');
        }
    }

    async function handleCloseShift(e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('closing_cash_actual', document.getElementById('closingCashInput').value);
        formData.append('notes', document.getElementById('closingNotesInput').value);
        formData.append('csrf_token', csrfToken);

        const res = await fetch('<?= base_url('pos/close-shift') ?>', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            let msg = `تم إغلاق الوردية بنجاح!<br>المتوقع: ${data.expected_cash} ${currency}<br>الفعلي: ${data.actual_cash} ${currency}<br>`;
            if (data.difference < 0) {
                msg += `<b class="text-rose-600">عجز بالصندوق: ${Math.abs(data.difference)} ${currency}</b>`;
            } else if (data.difference > 0) {
                msg += `<b class="text-emerald-600">زيادة بالصندوق: ${data.difference} ${currency}</b>`;
            } else {
                msg += `<b class="text-emerald-600">تطابق تام بالصندوق!</b>`;
            }

            Swal.fire({
                title: 'تسوية الصندوق',
                html: msg,
                icon: 'success'
            }).then(() => location.reload());
        } else {
            Swal.fire('خطأ', data.message, 'error');
        }
    }

    // 8.5 Cashier Shift Report (تقرير ورديتي والمبيعات والعجز)
    async function openShiftReportModal() {
        const modal = document.getElementById('shiftReportModal');
        modal.classList.remove('hidden');

        // Reset display with loading state
        document.getElementById('srOrdersTableBody').innerHTML = '<tr><td colspan="5" class="p-4 text-center text-slate-400"><i class="fa-solid fa-spinner fa-spin ml-2"></i> جاري جلب بيانات الوردية...</td></tr>';
        
        try {
            const res = await fetch('<?= base_url('pos/shift-report') ?>');
            const data = await res.json();
            
            if (!data.success) {
                Swal.fire('تنبيه', data.message || 'لا توجد بيانات وردية مسجلة', 'warning');
                closeShiftReportModal();
                return;
            }

            const s = data.shift;
            document.getElementById('srCashierNameTitle').textContent = `تقرير وردية: ${s.cashier_name}`;
            document.getElementById('srShiftIdDisplay').textContent = s.id;
            document.getElementById('srShiftStartTimeDisplay').textContent = s.opened_at;

            const badge = document.getElementById('srShiftStatusBadge');
            if (s.status === 'open') {
                badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800';
                badge.textContent = 'نشطة ومفتوحة الآن';
            } else {
                badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-700';
                badge.textContent = 'مغلقة ومسواة';
            }

            // Duration & counts
            document.getElementById('srDurationText').textContent = s.duration_text || '-';
            document.getElementById('srOrdersCountText').textContent = `${s.orders_count} طلب`;
            document.getElementById('srTotalSalesText').textContent = `${parseFloat(s.total_sales).toFixed(2)} ${currency}`;
            
            // Amounts breakdown
            document.getElementById('srCashSalesText').textContent = `${parseFloat(s.cash_sales).toFixed(2)} ${currency}`;
            document.getElementById('srCardSalesText').textContent = `${parseFloat(s.card_sales).toFixed(2)} ${currency}`;
            document.getElementById('srReturnsText').textContent = `${parseFloat(s.total_returns).toFixed(2)} ${currency}`;
            document.getElementById('srOpeningCashText').textContent = `${parseFloat(s.opening_cash).toFixed(2)} ${currency}`;
            document.getElementById('srExpectedCashText').textContent = `${parseFloat(s.expected_cash).toFixed(2)} ${currency}`;
            document.getElementById('srActualCashText').textContent = `${parseFloat(s.actual_cash).toFixed(2)} ${currency}`;
            
            // Difference / Shortage or Surplus
            const diffContainer = document.getElementById('srDiffBadgeContainer');
            const diff = parseFloat(s.difference);
            if (s.status === 'open') {
                diffContainer.innerHTML = '<span class="px-2.5 py-1 rounded-xl bg-amber-50 text-amber-700 text-xs font-black border border-amber-200">الوردية لا تزال مفتوحة</span>';
                document.getElementById('srActualCashSub').textContent = 'يتم حسابه عند الإغلاق';
            } else {
                document.getElementById('srActualCashSub').textContent = 'تم الجرد والاعتماد';
                if (diff < 0) {
                    diffContainer.innerHTML = `<span class="px-2.5 py-1 rounded-xl bg-rose-50 text-rose-700 text-xs font-black border border-rose-200 flex items-center gap-1"><i class="fa-solid fa-triangle-exclamation"></i> عجز: ${Math.abs(diff).toFixed(2)} ${currency}</span>`;
                } else if (diff > 0) {
                    diffContainer.innerHTML = `<span class="px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-black border border-emerald-200 flex items-center gap-1"><i class="fa-solid fa-circle-check"></i> زيادة: ${diff.toFixed(2)} ${currency}</span>`;
                } else {
                    diffContainer.innerHTML = '<span class="px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-black border border-emerald-200 flex items-center gap-1"><i class="fa-solid fa-check-double"></i> متطابق 100%</span>';
                }
            }

            // Print link with shift id
            document.getElementById('srPrintBtn').href = `<?= base_url('pos/shift-report/print') ?>?shift_id=${s.id}`;

            // Render Orders
            const orders = data.orders || [];
            document.getElementById('srOrdersBadge').textContent = `${orders.length} فاتورة`;

            if (orders.length === 0) {
                document.getElementById('srOrdersTableBody').innerHTML = '<tr><td colspan="5" class="p-4 text-center text-slate-400">لا توجد طلبات منفذة في هذه الوردية حتى الآن</td></tr>';
            } else {
                let html = '';
                orders.forEach(o => {
                    const payBadge = o.payment_method === 'cash' 
                        ? '<span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[10px] font-bold">كاش</span>'
                        : (o.payment_method === 'card' 
                            ? '<span class="px-2 py-0.5 rounded bg-cyan-50 text-cyan-700 text-[10px] font-bold">شبكة/حوالة</span>' 
                            : '<span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 text-[10px] font-bold">مختلط</span>');
                    
                    const typeMap = {
                        'dine_in': 'صالة' + (o.table_number ? ' (طاولة ' + o.table_number + ')' : ''),
                        'takeaway': 'سفري',
                        'delivery': 'توصيل'
                    };

                    html += `
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-2 font-mono font-bold text-indigo-600">${o.order_number}</td>
                            <td class="p-2">${typeMap[o.order_type] || o.order_type}</td>
                            <td class="p-2">${payBadge}</td>
                            <td class="p-2 text-slate-400 text-[11px]">${o.created_at.split(' ')[1] || o.created_at}</td>
                            <td class="p-2 font-black text-slate-800 text-left">${parseFloat(o.total_amount).toFixed(2)} ${currency}</td>
                        </tr>
                    `;
                });
                document.getElementById('srOrdersTableBody').innerHTML = html;
            }

        } catch (err) {
            console.error(err);
            Swal.fire('خطأ', 'تعذر جلب بيانات الوردية', 'error');
            closeShiftReportModal();
        }
    }

    function closeShiftReportModal() {
        document.getElementById('shiftReportModal').classList.add('hidden');
    }

    // 9. Hold / Park Current Order in Browser Memory
    let heldOrders = JSON.parse(localStorage.getItem('smart_rms_held_orders') || '[]');
    updateHeldOrdersBadge();

    function holdCurrentOrder() {
        if (cart.length === 0) return;
        const held = {
            id: Date.now(),
            time: new Date().toLocaleTimeString('ar-SA'),
            items: cart,
            table_id: document.getElementById('selectedTableId').value,
            customer_name: document.getElementById('customerName').value,
            order_type: currentOrderType
        };
        heldOrders.push(held);
        localStorage.setItem('smart_rms_held_orders', JSON.stringify(heldOrders));
        updateHeldOrdersBadge();
        cart = [];
        renderCart();
        Swal.fire('تم التعليق', 'تم تعليق الطلب، يمكنك استكماله في أي وقت', 'info');
    }

    function updateHeldOrdersBadge() {
        const badge = document.getElementById('heldOrdersCountBadge');
        if (badge) {
            badge.textContent = heldOrders.length;
            badge.classList.toggle('hidden', heldOrders.length === 0);
        }
    }

    function showHeldOrdersModal() {
        if (heldOrders.length === 0) {
            Swal.fire('الطلبات المعلقة', 'لا توجد طلبات معلقة حالياً', 'info');
            return;
        }

        let html = '<div class="space-y-2 text-xs text-right">';
        heldOrders.forEach((h, idx) => {
            html += `
                <div class="flex items-center justify-between p-2.5 bg-slate-50 border rounded-xl">
                    <div>
                        <span class="font-bold text-slate-800">طلب #${idx + 1} (${h.time})</span>
                        <p class="text-slate-500">${h.items.length} صنف</p>
                    </div>
                    <button onclick="resumeHeldOrder(${idx})" class="px-3 py-1.5 bg-indigo-600 text-white font-bold rounded-lg text-xs">استئناف</button>
                </div>
            `;
        });
        html += '</div>';

        Swal.fire({
            title: 'الطلبات المعلقة',
            html: html,
            showConfirmButton: false,
            showCloseButton: true
        });
    }

    function resumeHeldOrder(index) {
        const held = heldOrders[index];
        if (held) {
            cart = held.items;
            currentOrderType = held.order_type;
            document.getElementById('selectedTableId').value = held.table_id || '';
            document.getElementById('customerName').value = held.customer_name || '';
            heldOrders.splice(index, 1);
            localStorage.setItem('smart_rms_held_orders', JSON.stringify(heldOrders));
            updateHeldOrdersBadge();
            renderCart();
            Swal.close();
        }
    }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
