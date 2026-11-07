@echo off
title Smart RMS - Restaurant Management System
chcp 65001 >nul
echo ========================================================
echo   Smart RMS - Restaurant Management System Pro v2.5
echo   نظام إدارة المطاعم الذكي المتكامل
echo ========================================================
echo.
echo تشغيل خادم النظام السحابي المحلي...
echo الرابط: http://localhost:8000
echo.
echo للحسابات التجريبية:
echo  - المدير العام: admin / admin123
echo  - الكاشير: cashier / cashier123
echo  - الشيف (المطبخ): kitchen / kitchen123
echo  - المحاسب: accountant / acc123
echo.
php -S localhost:8000 -t public
pause
