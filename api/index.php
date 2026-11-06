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
        @mkdir($dir, 0777, true);
    }
}

// Ensure SQLite database exists in /tmp
$dbSource = dirname(__DIR__).'/database/database.sqlite';
$dbTarget = '/tmp/database.sqlite';

if (! file_exists($dbTarget)) {
    if (file_exists($dbSource)) {
        @copy($dbSource, $dbTarget);
    } else {
        @touch($dbTarget);
    }
}

// Forward request to Laravel public index entrypoint
require __DIR__.'/../public/index.php';
