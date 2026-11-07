<?php
$appName = get_setting('restaurant_name', 'مطعم القصر الفاخر');
$appLogo = get_setting('restaurant_logo');
$currencySymbol = get_setting('currency_symbol', 'ر.س');
$isClosed = ($shift['status'] === 'closed');
$expectedCash = $expectedCash ?? ((float)$shift['opening_cash'] + (float)$shift['total_cash_sales'] - (float)$shift['total_returns']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير تسليم وردية الكاشير #<?= $shift['id'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 10px;
            background: #f8fafc;
            color: #000;
            display: flex;
            justify-content: center;
        }
        .receipt-container {
            width: 80mm;
            background: #fff;
            padding: 12px;
            font-size: 11px;
            line-height: 1.4;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .font-black { font-weight: 900; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .divider-double { border-top: 2px solid #000; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .highlight-box {
            border: 1px solid #000;
            padding: 6px;
            margin: 6px 0;
            background: #fafafa;
        }
        .btn-print {
            display: block;
            width: 100%;
            padding: 10px;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            font-weight: bold;
            font-size: 13px;
            cursor: pointer;
            margin-bottom: 12px;
        }
        @page {
            size: 80mm auto;
            margin: 0mm;
        }
        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .receipt-container { width: 100% !important; max-width: 80mm !important; box-shadow: none !important; padding: 4mm 2mm !important; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    
    <!-- Action Bar (Hidden on print) -->
    <div class="no-print" style="margin-bottom: 12px; background: #f8fafc; padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0; font-family: 'Cairo', sans-serif;">
        <div style="display: flex; gap: 6px; margin-bottom: 8px;">
            <button onclick="window.print()" style="flex: 1; padding: 9px 6px; background: #4f46e5; color: #fff; border: none; border-radius: 8px; font-weight: bold; font-size: 11px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px; box-shadow: 0 2px 4px rgba(79,70,229,0.25);">
                <span>🖨️ طباعة التقرير</span>
            </button>
            <button onclick="window.print()" style="flex: 1; padding: 9px 6px; background: #059669; color: #fff; border: none; border-radius: 8px; font-weight: bold; font-size: 11px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px; box-shadow: 0 2px 4px rgba(5,150,105,0.25);">
                <span>📄 حفظ كـ PDF</span>
            </button>
        </div>
        <div style="font-size: 10px; color: #64748b; text-align: center; line-height: 1.4;">
            💡 <b>تنبيه:</b> إذا لم تكن الطابعة متصلة، اختر الوجهة <b>Save as PDF</b> في نافذة الطباعة لحفظ التقرير كملف.
        </div>
    </div>

    <!-- Header -->
    <div class="text-center">
        <?php if ($appLogo && file_exists(__DIR__ . '/../../public/' . $appLogo)): ?>
            <div style="margin-bottom: 8px;">
                <img src="<?= base_url($appLogo) ?>" alt="Logo" style="max-height: 48px; max-width: 140px; object-fit: contain;">
            </div>
        <?php endif; ?>
        <h2 style="margin: 0; font-size: 15px; font-weight: 900;"><?= htmlspecialchars($appName) ?></h2>
        <div style="font-size: 10px;"><?= htmlspecialchars($shift['branch_name'] ?? 'الفرع الرئيسي') ?></div>
        <div class="divider"></div>
        <div class="font-black" style="font-size: 13px;">
            <?= $isClosed ? 'تقرير إغلاق الوردية (Z-Report)' : 'تقرير جرد مؤقت للوردية (X-Report)' ?>
        </div>
        <div style="font-size: 10px;">رقم الوردية: <b>#<?= $shift['id'] ?></b></div>
    </div>

    <div class="divider"></div>

    <!-- Shift Timings & Cashier Info -->
    <div>
        <div class="row">
            <span>الكاشير المسؤول:</span>
            <b><?= htmlspecialchars($shift['cashier_name']) ?></b>
        </div>
        <div class="row">
            <span>تاريخ ووقت الفتح:</span>
            <span><?= date('Y-m-d H:i', strtotime($shift['opened_at'])) ?></span>
        </div>
        <div class="row">
            <span>تاريخ ووقت الإغلاق:</span>
            <span><?= $shift['closed_at'] ? date('Y-m-d H:i', strtotime($shift['closed_at'])) : 'مفتوحة حتى الآن' ?></span>
        </div>
        <div class="row">
            <span>مدة العمل الإجمالية:</span>
            <b><?= "{$hoursWorked} ساعة و {$minutesWorked} دقيقة" ?></b>
        </div>
        <div class="row">
            <span>عدد الطلبات المنجزة:</span>
            <b><?= count($orders) ?> طلب</b>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Sales & Breakdown -->
    <div class="font-bold" style="margin-bottom: 4px;">تفاصيل المبيعات وطرق الدفع:</div>
    
    <div class="row">
        <span>💵 مبيعات نقدية (كاش):</span>
        <b><?= number_format($shift['total_cash_sales'], 2) ?> <?= $currencySymbol ?></b>
    </div>
    <div class="row">
        <span>💳 مبيعات شبكة / مدى / حوالة:</span>
        <b><?= number_format($shift['total_card_sales'], 2) ?> <?= $currencySymbol ?></b>
    </div>
    <div class="row" style="color: red;">
        <span>🔄 إجمالي المرتجعات:</span>
        <b>-<?= number_format($shift['total_returns'], 2) ?> <?= $currencySymbol ?></b>
    </div>
    
    <div class="divider"></div>

    <div class="row" style="font-size: 12px; font-weight: 900;">
        <span>إجمالي مبيعات الوردية:</span>
        <span><?= number_format($shift['total_sales'], 2) ?> <?= $currencySymbol ?></span>
    </div>

    <div class="divider-double"></div>

    <!-- Drawer Settlement Details -->
    <div class="font-black" style="font-size: 12px; margin-bottom: 4px;">تسوية صندوق النقدية (الدرج):</div>

    <div class="row">
        <span>الرصيد الافتتاحي بالصندوق:</span>
        <span><?= number_format($shift['opening_cash'], 2) ?> <?= $currencySymbol ?></span>
    </div>
    <div class="row">
        <span>+ مقبوضات المبيعات النقدية:</span>
        <span>+<?= number_format($shift['total_cash_sales'], 2) ?> <?= $currencySymbol ?></span>
    </div>
    <div class="row" style="color: red;">
        <span>- مرتجعات مدفوعة نقداً:</span>
        <span>-<?= number_format($shift['total_returns'], 2) ?> <?= $currencySymbol ?></span>
    </div>

    <div class="highlight-box">
        <div class="row" style="font-size: 12px; font-weight: 900;">
            <span>المبلغ الإجمالي المطلوب تسليمه:</span>
            <span><?= number_format($expectedCash, 2) ?> <?= $currencySymbol ?></span>
        </div>
        
        <?php if ($isClosed): ?>
            <div class="row" style="font-size: 11px; font-weight: bold; margin-top: 4px;">
                <span>المبلغ النقدي الفعلي المسلّم:</span>
                <span><?= number_format($shift['closing_cash_actual'], 2) ?> <?= $currencySymbol ?></span>
            </div>
            
            <div class="divider"></div>
            
            <div class="row" style="font-size: 12px; font-weight: 900;">
                <span>فارق الصندوق:</span>
                <?php if ($shift['cash_difference'] < 0): ?>
                    <span style="color: red;">عجز: <?= abs($shift['cash_difference']) ?> <?= $currencySymbol ?> ⚠️</span>
                <?php elseif ($shift['cash_difference'] > 0): ?>
                    <span style="color: green;">زيادة: +<?= $shift['cash_difference'] ?> <?= $currencySymbol ?></span>
                <?php else: ?>
                    <span style="color: green;">مطابق تماماً (لا يوجد عجز) ✓</span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="font-size: 10px; color: #666; margin-top: 4px; text-align: center;">
                * الوردية لا تزال مفتوحة حالياً، هذا كشف جرد لحظي.
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($shift['notes'])): ?>
        <div style="font-size: 10px; margin-top: 4px;">
            <b>ملاحظات:</b> <?= htmlspecialchars($shift['notes']) ?>
        </div>
    <?php endif; ?>

    <div class="divider"></div>

    <!-- Signatures -->
    <div style="margin-top: 20px; font-size: 10px;">
        <div class="row">
            <span>توقيع الكاشير المسلّم:</span>
            <span>توقيع المشرف / المحاسب:</span>
        </div>
        <div style="height: 35px; border-bottom: 1px dotted #999;"></div>
    </div>

    <div class="text-center" style="font-size: 9px; color: #777; margin-top: 15px;">
        تمت الطباعة آلياً من نظام إدارة المطاعم الذكي Smart RMS<br>
        <?= date('Y-m-d H:i:s') ?>
    </div>

</div>

<script>
    window.addEventListener('load', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('autoprint') !== '0') {
            setTimeout(function() {
                window.print();
            }, 300);
        }
    });
</script>

</body>
</html>
