<?php
$appName = get_setting('restaurant_name', 'مطعم القصر الفاخر');
$appLogo = get_setting('restaurant_logo');
$taxNumber = get_setting('tax_number', '310987654300003');
$cr = get_setting('commercial_registration', '1010987654');
$address = get_setting('restaurant_address', 'الرياض، المملكة العربية السعودية');
$phone = get_setting('restaurant_phone', '0112345678');
$footerMsg = get_setting('invoice_footer', 'شكراً لزيارتكم! نتطلع لرؤيتكم مجدداً.');
$currencySymbol = get_setting('currency_symbol', 'ر.س');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة ضريبية مبسطة #<?= $order['order_number'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        @page {
            size: 80mm auto;
            margin: 0mm;
        }
        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 10px;
            background: #f8fafc;
            color: #000;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .receipt-container {
            width: 80mm;
            background: #fff;
            padding: 12px;
            font-size: 11px;
            line-height: 1.4;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .font-black { font-weight: 900; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .divider-double { border-top: 2px solid #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 11px; }
        th { border-bottom: 1px solid #000; padding: 4px 2px; }
        td { padding: 4px 2px; }
        .total-row { font-size: 12px; font-weight: bold; }
        .grand-total { font-size: 14px; font-weight: 900; }
        .qr-box {
            width: 90px;
            height: 90px;
            margin: 10px auto;
            border: 1px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            font-size: 9px;
            text-align: center;
        }
        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .receipt-container { width: 100% !important; max-width: 80mm !important; box-shadow: none !important; padding: 4mm 2mm !important; border-radius: 0 !important; }
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
                <span>🖨️ طباعة الفاتورة</span>
            </button>
            <button onclick="window.print()" style="flex: 1; padding: 9px 6px; background: #059669; color: #fff; border: none; border-radius: 8px; font-weight: bold; font-size: 11px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px; box-shadow: 0 2px 4px rgba(5,150,105,0.25);">
                <span>📄 حفظ كـ PDF</span>
            </button>
        </div>
        <div style="font-size: 10px; color: #64748b; text-align: center; line-height: 1.4;">
            💡 <b>تنبيه:</b> إذا لم تكن الطابعة الحرارية متصلة، اختر الوجهة <b>Save as PDF</b> في نافذة الطباعة لحفظ الفاتورة كملف.
        </div>
    </div>

    <!-- Header -->
    <div class="text-center">
        <?php if ($appLogo && file_exists(__DIR__ . '/../../public/' . $appLogo)): ?>
            <div style="margin-bottom: 8px;">
                <img src="<?= base_url($appLogo) ?>" alt="Logo" style="max-height: 48px; max-width: 140px; object-fit: contain;">
            </div>
        <?php endif; ?>
        <h2 style="margin: 0; font-size: 16px; font-weight: 900;"><?= htmlspecialchars($appName) ?></h2>
        <div style="font-size: 10px;"><?= htmlspecialchars($order['branch_name']) ?></div>
        <div style="font-size: 10px;"><?= htmlspecialchars($address) ?></div>
        <div style="font-size: 10px;">هاتف: <?= htmlspecialchars($phone) ?></div>
        <div class="divider"></div>
        <div class="font-bold" style="font-size: 12px;">فاتورة ضريبية مبسطة</div>
        <div style="font-size: 10px;">الرقم الضريبي: <b><?= htmlspecialchars($taxNumber) ?></b></div>
        <?php if ($cr): ?>
            <div style="font-size: 10px;">س.ت: <?= htmlspecialchars($cr) ?></div>
        <?php endif; ?>
    </div>

    <div class="divider"></div>

    <!-- Meta Info -->
    <div style="font-size: 10px;">
        <div style="display: flex; justify-content: space-between;">
            <span>رقم الفاتورة:</span>
            <b><?= $order['order_number'] ?></b>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>التاريخ والوقت:</span>
            <span><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>الكاشير:</span>
            <span><?= htmlspecialchars($order['cashier_name'] ?? 'الكاشير') ?></span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>نوع الطلب:</span>
            <b>
                <?php $ot = $order['order_type'] ?? 'dine_in'; ?>
                <?php if ($ot === 'dine_in'): ?>
                    صالة - طاولة (<?= $order['table_number'] ?? '-' ?>)
                <?php elseif ($ot === 'takeaway'): ?>
                    سفري (Takeaway)
                <?php else: ?>
                    توصيل (Delivery)
                <?php endif; ?>
            </b>
        </div>
        <?php if (!empty($order['customer_name']) && $order['customer_name'] !== 'عميل صالة'): ?>
            <div style="display: flex; justify-content: space-between;">
                <span>العميل:</span>
                <span><?= htmlspecialchars($order['customer_name']) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="divider"></div>

    <!-- Items Table -->
    <table>
        <thead>
            <tr>
                <th class="text-right">الصنف</th>
                <th class="text-center">الكمية</th>
                <th class="text-left">السعر</th>
                <th class="text-left">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td class="text-right font-bold"><?= htmlspecialchars($item['product_name']) ?></td>
                    <td class="text-center font-bold"><?= $item['quantity'] ?></td>
                    <td class="text-left"><?= number_format($item['unit_price'], 2) ?></td>
                    <td class="text-left font-bold"><?= number_format($item['total_price'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <!-- Totals -->
    <div>
        <div style="display: flex; justify-content: space-between;" class="total-row">
            <span>المجموع الخاضع للضريبة:</span>
            <span><?= number_format($order['subtotal'] ?? $order['total_amount'], 2) ?> <?= $currencySymbol ?></span>
        </div>
        <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
            <div style="display: flex; justify-content: space-between; color: red;" class="total-row">
                <span>الخصم:</span>
                <span>-<?= number_format($order['discount_amount'], 2) ?> <?= $currencySymbol ?></span>
            </div>
        <?php endif; ?>
        <?php if ($order['tax_amount'] > 0): ?>
            <div style="display: flex; justify-content: space-between;" class="total-row">
                <span>ضريبة القيمة المضافة (15%):</span>
                <span><?= number_format($order['tax_amount'], 2) ?> <?= $currencySymbol ?></span>
            </div>
        <?php endif; ?>
        
        <div class="divider-double"></div>

        <div style="display: flex; justify-content: space-between;" class="grand-total">
            <span>المبلغ الإجمالي المستحق:</span>
            <span><?= number_format($order['total_amount'], 2) ?> <?= $currencySymbol ?></span>
        </div>

        <div class="divider"></div>

        <div style="display: flex; justify-content: space-between; font-size: 10px;">
            <span>طريقة الدفع:</span>
            <b><?= $order['payment_method'] === 'cash' ? 'نقدي (Cash)' : ($order['payment_method'] === 'card' ? 'شبكة (Card)' : 'مختلط') ?></b>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 10px;">
            <span>المبلغ المدفوع:</span>
            <span><?= number_format($order['paid_amount'], 2) ?> <?= $currencySymbol ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 10px;">
            <span>المتبقي (الباقي):</span>
            <b><?= number_format($order['change_amount'], 2) ?> <?= $currencySymbol ?></b>
        </div>
    </div>

    <div class="divider"></div>

    <!-- QR Code & ZATCA Simulation -->
    <div class="text-center">
        <div class="qr-box">
            <div style="padding: 4px;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=85x85&data=<?= urlencode($appName . '|' . $taxNumber . '|' . $order['created_at'] . '|' . $order['total_amount'] . '|' . $order['tax_amount']) ?>" alt="QR" style="width: 100%; height: auto;">
            </div>
        </div>
        <p style="margin: 4px 0 0; font-size: 10px; font-weight: bold;"><?= htmlspecialchars($footerMsg) ?></p>
        <div style="font-size: 9px; color: #555; margin-top: 4px;">نظام إدارة المطاعم الذكي v2.5</div>
    </div>

</div>

<script>
    // Automatically trigger print dialog on page load
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
