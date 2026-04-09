@echo off
setlocal

REM الانتقال إلى مجلد المشروع الحالي
cd /d "%~dp0"

REM تشغيل بيئة التطوير عبر Composer (Laravel + Vite)
start "Laravel Composer Dev" cmd /k "cd /d ""%~dp0"" && composer run dev"

REM انتظار بسيط ثم فتح الصفحة في المتصفح
timeout /t 3 >nul
start "" "http://127.0.0.1:8000"

endlocal
