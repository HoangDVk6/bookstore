@echo off
chcp 65001 >nul
title Nha sach Tri Thuc - May chu PHP (dong cua so nay de tat web)

rem Tim php.exe cua XAMPP (thu C:, roi D:, roi E:)
set "PHP="
if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
if not defined PHP if exist "D:\xampp\php\php.exe" set "PHP=D:\xampp\php\php.exe"
if not defined PHP if exist "E:\xampp\php\php.exe" set "PHP=E:\xampp\php\php.exe"
if not defined PHP (
    echo Khong tim thay XAMPP. Hay sua dong "set PHP=" trong file nay cho dung duong dan php.exe.
    pause
    exit /b 1
)

cd /d "%~dp0"
echo ============================================
echo  Website: http://localhost:8000/
echo  Kiem tra ket noi: http://localhost:8000/check-connection.php
echo  Dong cua so nay (hoac bam Ctrl+C) de tat web.
echo ============================================

rem Mo trinh duyet sau 2 giay (cho may chu kip khoi dong)
start "" /b cmd /c "timeout /t 2 >nul & start http://localhost:8000/"

"%PHP%" -S localhost:8000 -t .
pause
