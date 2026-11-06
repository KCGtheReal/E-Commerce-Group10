<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic background Bakong KHQR verification & expiration
Schedule::command('payments:check-pending')->everyTenSeconds()->runInBackground();
Schedule::command('payments:expire-stale')->everyMinute()->runInBackground();
