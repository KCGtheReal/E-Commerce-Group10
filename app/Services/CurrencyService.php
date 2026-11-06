<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

class CurrencyService
{
    /**
     * Get the active currency ('USD' or 'KHR').
     */
    public static function current(): string
    {
        $currency = session('currency');
        if (! $currency && request()) {
            $currency = request()->cookie('currency');
        }

        $currency = strtoupper((string) ($currency ?: config('bakong.currency', 'KHR')));

        return in_array($currency, ['USD', 'KHR'], true) ? $currency : 'KHR';
    }

    /**
     * Get the current USD to KHR exchange rate.
     */
    public static function exchangeRate(): int
    {
        return (int) Setting::get('exchange_rate', 4100);
    }

    /**
     * Format a price given in base KHR into the active or specified currency string.
     */
    public static function formatCents(int $amount, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?: static::current());

        if ($currency === 'USD') {
            $rate = static::exchangeRate();
            $usd = $rate > 0 ? $amount / $rate : 0;

            return '$'.number_format($usd, 2);
        }

        return number_format($amount, 0).' ៛';
    }

    /**
     * Format a raw currency amount (already in USD or KHR) into a display string.
     */
    public static function formatAmount(float $amount, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?: static::current());

        if ($currency === 'USD') {
            return '$'.number_format($amount, 2);
        }

        return number_format(round($amount), 0).' ៛';
    }

    /**
     * Convert base KHR amount to the target currency amount.
     */
    public static function convertCents(int $amount, ?string $currency = null): float
    {
        $currency = strtoupper($currency ?: static::current());

        if ($currency === 'USD') {
            $rate = static::exchangeRate();

            return (float) round($rate > 0 ? $amount / $rate : 0, 2);
        }

        return (float) $amount;
    }
}
