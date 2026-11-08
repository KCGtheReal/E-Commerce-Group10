<?php

declare(strict_types=1);

/**
 * RESET E-Commerce - Vercel Serverless Gateway & High-Performance Fallback
 *
 * When the user runs 'run-presentation.bat' on their PC in Cambodia, dynamic payment
 * routes are seamlessly handled with domestic IP/DNS.
 *
 * If the PC backend is offline, requests immediately execute on Vercel's Edge Serverless
 * without slow connection timeouts.
 */
$backendUrl = getenv('BACKEND_PC_URL') ?: 'https://reset-store-cambodia.loca.lt';
$offlineMarker = '/tmp/pc_tunnel_offline';

// Check if PC tunnel was recently marked offline (cached for 45s)
$isRecentlyOffline = file_exists($offlineMarker) && (time() - filemtime($offlineMarker) < 45);

if (! empty($backendUrl) && ! $isRecentlyOffline && extension_loaded('curl')) {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
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

    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $val) {
            $lower = strtolower($name);
            if (in_array($lower, ['host', 'content-length', 'connection', 'accept-encoding'], true)) {
                continue;
            }
            $forwardHeaders[] = "$name: $val";
        }
    }

    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $body = file_get_contents('php://input');
        if ($body !== false && $body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 400); // Super fast 400ms connect timeout!
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
