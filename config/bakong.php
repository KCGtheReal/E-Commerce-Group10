<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Bakong KHQR Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for Bakong KHQR payments, including merchant
    | identity, default currency (USD or KHR), QR code TTL in seconds,
    | and local development mock mode.
    |
    */

    'token' => env('BAKONG_TOKEN', ''),

    'account_id' => env('BAKONG_ACCOUNT_ID', 'reset_store@devb'),

    'merchant_name' => env('BAKONG_MERCHANT_NAME', 'RESET Store'),

    'merchant_city' => env('BAKONG_MERCHANT_CITY', 'Phnom Penh'),

    // Currency to charge via KHQR: USD or KHR
    'currency' => env('BAKONG_CURRENCY', 'KHR'),

    // individual or merchant
    'account_type' => env('BAKONG_ACCOUNT_TYPE', 'individual'),

    // Default expiration for dynamic KHQR (default 600 seconds = 10 minutes)
    'qr_ttl_seconds' => (int) env('BAKONG_QR_TTL_SECONDS', 600),

    'api_url' => env('BAKONG_API_URL', 'https://api-bakong.nbc.gov.kh/v1'),

    /*
    |--------------------------------------------------------------------------
    | Mock Mode (Local / Testing Only)
    |--------------------------------------------------------------------------
    |
    | When enabled, simulates instant or controlled Bakong transactions
    | without hitting NBC live servers or requiring real money.
    | Guaranteed impossible to enable in production.
    |
    */
    'mock' => env('BAKONG_MOCK', false) && env('APP_ENV') !== 'production',

    // Optional merchant ID and bank code when account_type is 'merchant'
    'merchant_id' => env('BAKONG_MERCHANT_ID', null),
    'acquiring_bank' => env('BAKONG_ACQUIRING_BANK', null),
    'mobile_number' => env('BAKONG_MOBILE_NUMBER', null),

    // Telegram Bot Notifications (optional)
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN', null),
        'chat_id' => env('TELEGRAM_CHAT_ID', null),
    ],
];
