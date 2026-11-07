<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الصفحة غير موجودة - 404</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center border border-slate-200">
        <div class="w-20 h-20 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-5 text-3xl font-bold">
            404
        </div>
        <h1 class="text-2xl font-bold text-slate-800 mb-2">عذراً! الصفحة غير موجودة</h1>
        <p class="text-slate-500 mb-6 text-sm">الصفحة التي تحاول الوصول إليها قد تكون حُذفت أو نُقلت أو أن الرابط غير صحيح.</p>
        <a href="<?= base_url() ?>" class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200 text-sm">
            العودة للوحة التحكم
        </a>
    </div>
</body>
</html>
