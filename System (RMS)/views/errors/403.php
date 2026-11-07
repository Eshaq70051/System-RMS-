<?php
$user = auth_user();
$homeUrl = base_url();
if ($user && ($user['role_slug'] ?? '') === 'cashier') {
    $homeUrl = base_url('pos');
} elseif ($user && ($user['role_slug'] ?? '') === 'kitchen') {
    $homeUrl = base_url('kds');
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>غير مصرح بالدخول - 403 Forbidden</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-3xl shadow-xl max-w-md w-full text-center border border-slate-200">
        <div class="w-20 h-20 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-5 text-3xl font-black shadow-inner">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h1 class="text-xl font-black text-slate-800 mb-2">عذراً! غير مصرح لك بالوصول</h1>
        <p class="text-slate-500 mb-6 text-xs leading-relaxed">
            هذه الشاشة مخصصة لأدوار وظيفية أخرى وليست ضمن صلاحيات حسابك الحالي (<?= htmlspecialchars($user['role_name'] ?? 'مستخدم') ?>).
        </p>
        <a href="<?= $homeUrl ?>" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition shadow-lg shadow-indigo-200 text-xs w-full">
            <i class="fa-solid fa-arrow-left"></i>
            <span>العودة لشاشتك المعتمدة</span>
        </a>
    </div>
</body>
</html>
