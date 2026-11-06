@echo off
title RESET Studio - Free Online Hosting
echo ========================================================
echo      RESET STUDIO E-COMMERCE - LIVE ONLINE HOSTING
echo ========================================================
echo.
echo 1. Launching Laravel Backend (Port 8000)...
start "RESET Laravel Server" /min php artisan serve --host=127.0.0.1 --port=8000

echo 2. Connecting to Cloudflare Global Edge Network...
echo.
echo Look for your https://*.trycloudflare.com link below:
echo (Share that link with your professor, team, or phone!)
echo.
"%USERPROFILE%\bin\cloudflared.exe" tunnel --url http://127.0.0.1:8000
pause
