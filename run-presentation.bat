@echo off
setlocal
title RESET Store - Live Presentation (API + Website + Mobile + Tunnels)
color 0A

set "WEBSITE_DIR=%~dp0"
if "%WEBSITE_DIR:~-1%"=="\" set "WEBSITE_DIR=%WEBSITE_DIR:~0,-1%"
for %%I in ("%WEBSITE_DIR%\..") do set "ROOT_DIR=%%~fI"
set "API_DIR=%ROOT_DIR%\API"
set "MOBILE_DIR=%ROOT_DIR%\Mobile Project"

echo ============================================================================
echo   RESET STORE - FULL LIVE PRESENTATION LAUNCHER
echo   ^(Firebase API :4000 + Website/Admin :8000 + Mobile Client + Online Tunnel^)
echo ============================================================================
echo.

echo [1/4] Starting Firebase Cloud Firestore Gateway API on http://127.0.0.1:4000 ...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":4000 " ^| findstr LISTENING') do taskkill /f /pid %%a > nul 2>&1
if exist "%API_DIR%\package.json" (
    pushd "%API_DIR%"
    if not exist "node_modules" call npm install --no-audit --no-fund
    if not exist "dist\server.js" call npm run build
    start "RESET Firebase API (Port 4000)" /min cmd /c "npm run start"
    popd
) else (
    echo       [WARN] API folder not found at "%API_DIR%". Running in fallback mode.
)

echo [2/4] Starting Unified Website Backend ^(Laravel + Filament Admin^) on http://127.0.0.1:8000 ...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8000 " ^| findstr LISTENING') do taskkill /f /pid %%a > nul 2>&1
pushd "%WEBSITE_DIR%"
start "RESET Website Backend (Port 8000)" /min cmd /c "php artisan serve --host=127.0.0.1 --port=8000"
popd

powershell -NoProfile -Command "$deadline = (Get-Date).AddSeconds(20); while ((Get-Date) -lt $deadline) { $up8000 = $false; try { $c = New-Object System.Net.Sockets.TcpClient('127.0.0.1', 8000); $c.Close(); $up8000 = $true } catch {}; $up4000 = -not (Test-Path (Join-Path $env:API_DIR 'package.json')); if (-not $up4000) { try { $c2 = New-Object System.Net.Sockets.TcpClient('127.0.0.1', 4000); $c2.Close(); $up4000 = $true } catch {} }; if ($up8000 -and $up4000) { break }; Start-Sleep -Milliseconds 300 }" > nul 2>&1

echo [3/4] Launching Mobile Client ^(Flutter Chrome synced to :8000 ^& :4000^)...
if exist "%MOBILE_DIR%\pubspec.yaml" (
    where flutter > nul 2>&1
    if errorlevel 1 (
        echo       [WARN] Flutter CLI not found in PATH. Skipping Mobile launch.
    ) else (
        pushd "%MOBILE_DIR%"
        start "RESET Mobile Client (Flutter)" cmd /c "flutter run -d chrome --dart-define=API_BASE_URL=http://127.0.0.1:8000 --dart-define=FIREBASE_API_URL=http://127.0.0.1:4000"
        popd
    )
) else (
    echo       [WARN] Mobile Project not found at "%MOBILE_DIR%".
)

echo [4/4] Starting Online Presentation Tunnels ^(localtunnel + Cloudflare^)...
where npx > nul 2>&1
if not errorlevel 1 (
    start "RESET Localtunnel (:8000)" /min cmd /c "npx -y localtunnel --port 8000 --subdomain reset-store-cambodia"
)
timeout /t 2 /nobreak > nul

echo.
echo ============================================================================
echo  LIVE PRESENTATION SYSTEM IS READY!
echo ============================================================================
echo.
echo  LOCAL WEBSITE ^& ADMIN:
echo   - Website Storefront:    http://127.0.0.1:8000/
echo   - Shared Web Admin:      http://127.0.0.1:8000/admin
echo   - Website Swagger Docs:  http://127.0.0.1:8000/api/docs
echo.
echo  FIREBASE API ^& DOCS:
echo   - Firebase Gateway API:  http://127.0.0.1:4000/api/firebase/home
echo   - Firebase Swagger Docs: http://127.0.0.1:4000/docs
echo.
echo  MOBILE CLIENT:
echo   - Launched in Chrome ^(connected to :8000 + :4000 + Cloud Firestore^)
echo   - Android Emulator cmd:  flutter run -d android
echo.
echo  PUBLIC ONLINE LINKS ^(Connected to this PC^):
echo   - Direct Tunnel Backup:  https://reset-store-cambodia.loca.lt
echo   - Vercel Deployment:     https://e-commerce-clothing-gray.vercel.app/
echo   - Cloudflare Tunnel URL: ^(see output below^)
echo.
echo  ADMIN LOGIN:  admin@reset.store / password
echo  BAKONG KHQR:  Cambodia ISP / Local PC active
echo ============================================================================
echo  Keep this window open during your presentation. Press Ctrl + C to stop.
echo ============================================================================
echo.

if exist "C:\Users\Kongc\bin\cloudflared.exe" (
    "C:\Users\Kongc\bin\cloudflared.exe" tunnel --url http://127.0.0.1:8000
) else (
    cloudflared tunnel --url http://127.0.0.1:8000
)
