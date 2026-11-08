<?php

declare(strict_types=1);

/**
 * RESET E-Commerce - Vercel Serverless Gateway & High-Performance Fallback
 *
 * When the user runs 'run-presentation.bat' on their PC in Cambodia, all dynamic
 * requests are seamlessly proxied to the PC backend so the National Bank of Cambodia
 * (NBC) Bakong KHQR verification succeeds from Cambodian IP/DNS with zero session split.
 *
 * If the PC backend is offline, requests immediately execute on Vercel's Edge Serverless
 * fallback without long timeouts.
 */
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';

$backendUrl = getenv('BACKEND_PC_URL');
if (empty($backendUrl)) {
    $backendUrl = 'https://reset-store-cambodia.loca.lt';
}

$offlineMarker = '/tmp/pc_tunnel_offline';
$isRecentlyOffline = file_exists($offlineMarker) && (time() - filemtime($offlineMarker) < 30);

if (! empty($backendUrl) && ! $isRecentlyOffline && extension_loaded('curl')) {
    $targetUrl = rtrim($backendUrl, '/').$uri;

    $ch = curl_init($targetUrl);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    // Forward client headers
    $forwardHeaders = [
        'Bypass-Tunnel-Reminder: true',
        'X-Forwarded-Host: '.($_SERVER['HTTP_HOST'] ?? 'e-commerce-clothing-gray.vercel.app'),
        'X-Forwarded-Proto: https',
        'X-Forwarded-For: '.($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
    ];

    $headersCaptured = [];
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $val) {
            $lower = strtolower($name);
            if (in_array($lower, ['host', 'content-length', 'connection', 'accept-encoding'], true)) {
                continue;
            }
            $headersCaptured[$lower] = true;
            $forwardHeaders[] = "$name: $val";
        }
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? null;
    if (! isset($headersCaptured['content-type']) && ! empty($contentType)) {
        $forwardHeaders[] = 'Content-Type: '.$contentType;
        $headersCaptured['content-type'] = true;
    }

    if (! isset($headersCaptured['cookie']) && ! empty($_SERVER['HTTP_COOKIE'])) {
        $forwardHeaders[] = 'Cookie: '.$_SERVER['HTTP_COOKIE'];
        $headersCaptured['cookie'] = true;
    }

    if (! isset($headersCaptured['accept']) && ! empty($_SERVER['HTTP_ACCEPT'])) {
        $forwardHeaders[] = 'Accept: '.$_SERVER['HTTP_ACCEPT'];
        $headersCaptured['accept'] = true;
    }

    if (! isset($headersCaptured['authorization']) && ! empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $forwardHeaders[] = 'Authorization: '.$_SERVER['HTTP_AUTHORIZATION'];
        $headersCaptured['authorization'] = true;
    }

    if (! isset($headersCaptured['x-csrf-token']) && ! empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $forwardHeaders[] = 'X-CSRF-TOKEN: '.$_SERVER['HTTP_X_CSRF_TOKEN'];
        $headersCaptured['x-csrf-token'] = true;
    }

    if (! isset($headersCaptured['x-xsrf-token']) && ! empty($_SERVER['HTTP_X_XSRF_TOKEN'])) {
        $forwardHeaders[] = 'X-XSRF-TOKEN: '.$_SERVER['HTTP_X_XSRF_TOKEN'];
        $headersCaptured['x-xsrf-token'] = true;
    }

    if (! isset($headersCaptured['user-agent']) && ! empty($_SERVER['HTTP_USER_AGENT'])) {
        $forwardHeaders[] = 'User-Agent: '.$_SERVER['HTTP_USER_AGENT'];
        $headersCaptured['user-agent'] = true;
    }

    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $body = file_get_contents('php://input');
        if ($body !== false && $body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } elseif (! empty($_POST)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
        }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 10000); // 10s max timeout for request execution
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 2500); // 2.5s connect timeout
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);

    if ($response !== false) {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        // If tunnel returned 502/503/504 (PC offline or tunnel dropped), fall back to local Vercel copy
        if ($httpCode !== 502 && $httpCode !== 503 && $httpCode !== 504) {
            if (file_exists($offlineMarker)) {
                @unlink($offlineMarker);
            }

            $rawHeaders = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);

            http_response_code($httpCode);

            foreach (explode("\r\n", $rawHeaders) as $line) {
                if (empty($line) || stripos($line, 'HTTP/') === 0) {
                    continue;
                }
                $lower = strtolower($line);
                if (str_starts_with($lower, 'transfer-encoding:') || str_starts_with($lower, 'connection:')) {
                    continue;
                }
                header($line, false);
            }

            echo $body;
            exit;
        } else {
            @touch($offlineMarker);
        }
    } else {
        curl_close($ch);
        @touch($offlineMarker);
    }
}

// ---------------------------------------------------------
// Local Vercel Serverless Fallback (if PC is offline)
// ---------------------------------------------------------
$dirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($dirs as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

$dbSource = dirname(__DIR__).'/database/database.sqlite';
$dbTarget = '/tmp/database.sqlite';

if (! file_exists($dbTarget)) {
    if (file_exists($dbSource)) {
        @copy($dbSource, $dbTarget);
    } else {
        @touch($dbTarget);
    }
}

require __DIR__.'/../public/index.php';
