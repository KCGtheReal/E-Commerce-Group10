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

echo [2/4] Starting Unified Website Backend ^(Laravel + Filament Admin^) on port 8000 ...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8000 " ^| findstr LISTENING') do taskkill /f /pid %%a > nul 2>&1
pushd "%WEBSITE_DIR%"
start "RESET Website Backend (Port 8000)" /min cmd /c "php artisan serve --host=0.0.0.0 --port=8000"
popd

set "LAN_IP="
for /f "usebackq delims=" %%I in (`powershell -NoProfile -Command "$ip = Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '169.254.*' -and $_.InterfaceAlias -notmatch 'Loopback|vEthernet|VMware|VirtualBox|Hyper-V|WSL|Bluetooth' -and $_.AddressState -eq 'Preferred' } | Sort-Object @{Expression={if($_.InterfaceAlias -match 'Wi-Fi|WLAN|Ethernet'){0}else{1}}}, InterfaceMetric | Select-Object -First 1 -ExpandProperty IPAddress; if ($ip) { Write-Output $ip }"`) do set "LAN_IP=%%I"
set "LAN_API_URL="
set "LAN_GATEWAY_URL="
if not "%LAN_IP%"=="" (
    set "LAN_API_URL=http://%LAN_IP%:8000"
    set "LAN_GATEWAY_URL=http://%LAN_IP%:4000"
)

powershell -NoProfile -Command "$deadline = (Get-Date).AddSeconds(20); while ((Get-Date) -lt $deadline) { $up8000 = $false; try { $c = New-Object System.Net.Sockets.TcpClient('127.0.0.1', 8000); $c.Close(); $up8000 = $true } catch {}; $up4000 = -not (Test-Path (Join-Path $env:API_DIR 'package.json')); if (-not $up4000) { try { $c2 = New-Object System.Net.Sockets.TcpClient('127.0.0.1', 4000); $c2.Close(); $up4000 = $true } catch {} }; if ($up8000 -and $up4000) { break }; Start-Sleep -Milliseconds 300 }" > nul 2>&1

if not "%LAN_API_URL%"=="" (
    pushd "%WEBSITE_DIR%"
    call php artisan app:firebase-runtime --lan-api="%LAN_API_URL%" --lan-gateway="%LAN_GATEWAY_URL%" > nul 2>&1
    popd
)

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
echo  ANDROID PHONE APP ^(APK Auto-Discovery via Cloud Firestore^):
if not "%LAN_API_URL%"=="" (
    echo   - Same Wi-Fi / LAN:      %LAN_API_URL% ^& %LAN_GATEWAY_URL%
)
echo   - Anywhere ^(4G/Wi-Fi^):   Auto-synced Cloudflare tunnel ^(once connected below^)
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

set "CF_EXE=cloudflared"
if exist "C:\Users\Kongc\bin\cloudflared.exe" set "CF_EXE=C:\Users\Kongc\bin\cloudflared.exe"
powershell -NoProfile -Command "$cf = $env:CF_EXE; $webDir = $env:WEBSITE_DIR; $lanApi = $env:LAN_API_URL; $lanGw = $env:LAN_GATEWAY_URL; $published = $false; & $cf tunnel --url http://127.0.0.1:8000 2>&1 | ForEach-Object { $line = $_.ToString(); Write-Host $line; if (-not $published -and $line -match '(https://[a-z0-9-]+\.trycloudflare\.com)') { $tunnelUrl = $Matches[1]; $published = $true; try { $cmdArgs = @('artisan','app:firebase-runtime','--tunnel=' + $tunnelUrl); if ($lanApi) { $cmdArgs += ('--lan-api=' + $lanApi) }; if ($lanGw) { $cmdArgs += ('--lan-gateway=' + $lanGw) }; Start-Process -FilePath 'php' -ArgumentList $cmdArgs -WorkingDirectory $webDir -WindowStyle Hidden | Out-Null; Write-Host ('[Mobile Sync] Published live tunnel for Android APK: ' + $tunnelUrl) -ForegroundColor Cyan } catch {} } }"
