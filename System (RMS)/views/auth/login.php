<?php
$appName = get_setting('restaurant_name', 'نظام إدارة المطاعم الذكي');
$appLogo = get_setting('restaurant_logo');
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | <?= htmlspecialchars($appName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-8">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <?php if ($appLogo && file_exists(__DIR__ . '/../../public/' . $appLogo)): ?>
                <img src="<?= base_url($appLogo) ?>" alt="شعار المطعم" class="w-20 h-20 rounded-2xl object-contain bg-white p-2 mx-auto shadow-xl mb-4 border border-slate-100 shadow-indigo-500/10">
            <?php else: ?>
                <div class="w-16 h-16 bg-gradient-to-tr from-amber-500 to-indigo-600 rounded-2xl flex items-center justify-center text-white text-3xl mx-auto shadow-xl mb-4 shadow-indigo-500/20">
                    <i class="fa-solid fa-utensils"></i>
                </div>
            <?php endif; ?>
            <h1 class="text-2xl font-black text-slate-800"><?= htmlspecialchars($appName) ?></h1>
            <p class="text-slate-500 text-xs mt-1">نظام تجاري متكامل لإدارة المطاعم ونقاط البيع السحابية</p>
        </div>

        <?php if ($flash): ?>
            <div class="mb-5 p-3.5 rounded-xl flex items-center gap-2.5 text-xs font-bold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?>">
                <i class="fa-solid fa-circle-exclamation text-base"></i>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="<?= base_url('login') ?>" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">اسم المستخدم أو البريد الإلكتروني</label>
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-slate-400">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input type="text" name="username" id="loginUsername" required placeholder="admin أو cashier"
                        class="w-full pr-10 pl-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none transition font-medium">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">كلمة المرور</label>
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-slate-400">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="password" id="loginPassword" required placeholder="••••••••"
                        class="w-full pr-10 pl-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none transition font-medium">
                </div>
            </div>

            <button type="submit" class="w-full py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-bold rounded-xl text-sm transition shadow-lg shadow-indigo-500/25 flex items-center justify-center gap-2">
                <span>تسجيل الدخول</span>
                <i class="fa-solid fa-arrow-left"></i>
            </button>
        </form>

       

    </div>

    <script>
        function setCreds(user, pass) {
            document.getElementById('loginUsername').value = user;
            document.getElementById('loginPassword').value = pass;
        }
    </script>
</body>
</html>
