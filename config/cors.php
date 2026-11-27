<?php

declare(strict_types=1);

$rawOrigins = (string) env('CORS_ALLOWED_ORIGINS', '*');
$allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $rawOrigins)), static fn (string $o): bool => $o !== ''));

if ($allowedOrigins === []) {
    $allowedOrigins = ['*'];
}

return [
    'paths' => ['api/*', 'orders/*/invoice', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [
        '#^http://localhost(:\d+)?$#',
        '#^http://127\.0\.0\.1(:\d+)?$#',
        '#^http://10\.0\.2\.2(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-Cart-Token',
        'X-Order-Token',
        'X-Locale',
        'X-Currency',
    ],

    'max_age' => 86400,

    'supports_credentials' => false,
];
