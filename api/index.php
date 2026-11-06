<?php

declare(strict_types=1);

// Ensure writable directories exist in /tmp on Vercel Serverless environment
$dirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($dirs as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Copy pre-seeded SQLite database to writable /tmp on cold start
$dbSource = __DIR__.'/../database/database.sqlite';
$dbTarget = '/tmp/database.sqlite';
if (! file_exists($dbTarget) && file_exists($dbSource)) {
    @copy($dbSource, $dbTarget);
}

// Forward request to Laravel public index entrypoint
require __DIR__.'/../public/index.php';
