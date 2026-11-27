<?php

declare(strict_types=1);

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class CatalogCacheService
{
    private const EPOCH_KEY = 'catalog.epoch';

    public static function epoch(): int
    {
        $epoch = Cache::get(self::EPOCH_KEY);
        if ($epoch === null) {
            Cache::forever(self::EPOCH_KEY, 1);

            return 1;
        }

        return max(1, (int) $epoch);
    }

    public static function bumpCatalogEpoch(): int
    {
        if (! Cache::has(self::EPOCH_KEY)) {
            Cache::forever(self::EPOCH_KEY, 2);

            return 2;
        }

        return (int) Cache::increment(self::EPOCH_KEY);
    }

    /**
     * Cache a computed value scoped to the active catalog epoch.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function remember(string $key, int $ttlSeconds, Closure $callback): mixed
    {
        $epoch = self::epoch();

        return Cache::remember("catalog:v{$epoch}:{$key}", $ttlSeconds, $callback);
    }
}
