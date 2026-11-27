param(
    [Parameter(Mandatory = $true)]
    [string]$CloudflaredExe,

    [Parameter(Mandatory = $true)]
    [string]$WebsiteDir,

    [string]$LanApiUrl = '',
    [string]$LanGatewayUrl = ''
)

$phpCmd = Get-Command php -ErrorAction SilentlyContinue
$phpExe = if ($phpCmd) { $phpCmd.Source } else { 'C:\Users\Kongc\.config\herd-lite\bin\php.exe' }
$published = $false

& $CloudflaredExe tunnel --url http://127.0.0.1:8000 2>&1 | ForEach-Object {
    $line = $_.ToString()
    Write-Host $line

    if (-not $published -and $line -match '(https://[a-z0-9-]+\.trycloudflare\.com)') {
        $tunnelUrl = $Matches[1]
        $published = $true
        try {
            $cmdArgs = @('artisan', 'app:firebase-runtime', "--tunnel=$tunnelUrl")
            if ($LanApiUrl) { $cmdArgs += "--lan-api=$LanApiUrl" }
            if ($LanGatewayUrl) { $cmdArgs += "--lan-gateway=$LanGatewayUrl" }

            Push-Location $WebsiteDir
            try {
                $output = & $phpExe @cmdArgs 2>&1
                $exitCode = $LASTEXITCODE
            } finally {
                Pop-Location
            }
            if ($exitCode -eq 0) {
                Write-Host "[Mobile Sync] Published live tunnel for Android APK: $tunnelUrl" -ForegroundColor Cyan
            } else {
                Write-Host "[Mobile Sync WARN] Publish exited $exitCode : $output" -ForegroundColor Yellow
            }
        } catch {
            Write-Host "[Mobile Sync WARN] $($_.Exception.Message)" -ForegroundColor Yellow
        }
    }
}
