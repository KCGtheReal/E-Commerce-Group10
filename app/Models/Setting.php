<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\CatalogCacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    protected static function booted(): void
    {
        $invalidate = static function (self $setting): void {
            Cache::forget("setting_{$setting->key}");
            Cache::forget('settings.all');
            Cache::memo()->forget('settings.all');
            CatalogCacheService::bumpCatalogEpoch();
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /**
     * @return array<string, mixed>
     */
    public static function allValues(): array
    {
        return Cache::memo()->remember('settings.all', 3600, static function (): array {
            $map = [];
            foreach (static::all() as $setting) {
                $map[$setting->key] = static::decodeValue($setting->value);
            }

            return $map;
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::allValues();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): self
    {
        $stringValue = is_array($value) ? (string) json_encode($value) : (string) $value;

        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $stringValue, 'group' => $group]
        );

        Cache::forget("setting_{$key}");
        Cache::forget('settings.all');
        Cache::memo()->forget('settings.all');

        return $setting;
    }

    private static function decodeValue(?string $rawValue): mixed
    {
        if ($rawValue === null) {
            return null;
        }

        $decoded = json_decode($rawValue, true);

        return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $rawValue;
    }
}
