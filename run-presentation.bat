@echo off
title RESET Store - Live Class Presentation
color 0A
echo ============================================================
echo         RESET STORE - LIVE PRESENTATION LAUNCHER
echo ============================================================
echo.
echo [1/2] Starting local Laravel server on port 8000...
start /b php artisan serve --host=127.0.0.1 --port=8000 > nul 2>&1
timeout /t 2 /nobreak > nul

echo [2/2] Launching Cloudflare Public Tunnel...
echo.
echo ------------------------------------------------------------
echo Share the "https://....trycloudflare.com" link below
echo with your teacher and classmates!
echo (Anyone can open it on their phone, tablet, or laptop)
echo.
echo Press Ctrl + C when your presentation is finished to stop.
echo ------------------------------------------------------------
echo.

C:\Users\Kongc\bin\cloudflared.exe tunnel --url http://127.0.0.1:8000
