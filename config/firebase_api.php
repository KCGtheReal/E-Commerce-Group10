<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Standalone Firebase Cloud Firestore Gateway API Configuration
    |--------------------------------------------------------------------------
    |
    | Connects Website Project to the standalone Node/Fastify Firebase Gateway
    | API (default http://127.0.0.1:4000/api/firebase/*) for accelerated
    | read-only storefront endpoints, falling back seamlessly to local SQLite
    | when disabled, offline, or during APP_ENV=testing.
    |
    */

    'enabled' => filter_var(env('FIREBASE_API_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'base_url' => rtrim((string) env('FIREBASE_API_URL', 'http://127.0.0.1:4000'), '/'),

    'api_key' => (string) env('FIREBASE_API_KEY', ''),

    'timeout_seconds' => (float) env('FIREBASE_API_TIMEOUT', 2.0),

    'connect_timeout_seconds' => (float) env('FIREBASE_API_CONNECT_TIMEOUT', 0.5),
];
