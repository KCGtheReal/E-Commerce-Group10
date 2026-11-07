@echo off
title RESET Store - Live Presentation Backend (Cambodia)
color 0A

echo ============================================================
echo         RESET STORE - LIVE PRESENTATION LAUNCHER
echo ============================================================
echo.
echo [1/3] Starting local Laravel backend on port 8000...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8000 ^| findstr LISTENING') do taskkill /f /pid %%a > nul 2>&1
start /b php artisan serve --host=127.0.0.1 --port=8000 > nul 2>&1
timeout /t 2 /nobreak > nul

echo [2/3] Connecting Vercel gateway to your PC in Cambodia...
start /b npx -y localtunnel --port 8000 --subdomain reset-store-cambodia > nul 2>&1
timeout /t 3 /nobreak > nul

echo [3/3] Launching Cloudflare Direct Tunnel backup...
echo.
echo ============================================================
echo  LIVE PRESENTATION SYSTEM IS READY!
echo ============================================================
echo.
echo  OFFICIAL VERCEL LINK (Connected to this PC):
echo  >> https://e-commerce-clothing-gray.vercel.app/
echo.
echo  DIRECT LOCAL TUNNEL BACKUP:
echo  >> https://reset-store-cambodia.loca.lt
echo.
echo  ADMIN PANEL:
echo  >> https://e-commerce-clothing-gray.vercel.app/admin
echo  Account: admin@resetstore.com / password
echo.
echo  BAKONG KHQR:
echo  - Origin: Cambodia (Local PC & Cambodian ISP)
echo  - Status: ACTIVE & Live Verification Ready
echo.
echo  ------------------------------------------------------------
echo  Keep this window open during your class presentation!
echo  Press Ctrl + C when finished to stop.
echo ============================================================
echo.

C:\Users\Kongc\bin\cloudflared.exe tunnel --url http://127.0.0.1:8000
