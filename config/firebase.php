<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Firestore Sync Configuration (Opt-In)
    |--------------------------------------------------------------------------
    |
    | SQLite (database/database.sqlite) remains the authoritative ACID primary
    | database for Filament v3 Admin and Bakong KHQR transactions. When enabled,
    | non-PII catalog, settings, approved reviews, and sanitized order/payment
    | status mirrors are pushed to Cloud Firestore via pure-PHP REST API v1.
    |
    */

    'enabled' => filter_var(env('FIREBASE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'project_id' => (string) env('FIREBASE_PROJECT_ID', ''),

    'database_id' => (string) env('FIREBASE_DATABASE_ID', '(default)'),

    'credentials_path' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/service-account.json')),

    'credentials_json' => env('FIREBASE_CREDENTIALS_JSON'),

    'collections_prefix' => (string) env('FIREBASE_COLLECTIONS_PREFIX', env('FIREBASE_COLLECTION_PREFIX', '')),

    'sync_on_write' => filter_var(env('FIREBASE_SYNC_ON_WRITE', true), FILTER_VALIDATE_BOOLEAN),

    'timeout_seconds' => (int) env('FIREBASE_TIMEOUT', 10),
];
