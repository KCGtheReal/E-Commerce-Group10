<?php

declare(strict_types=1);

namespace App\Models;

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

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            if (! $setting) {
                return $default;
            }

            $decoded = json_decode($setting->value, true);

            return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $setting->value;
        });
    }

    public static function set(string $key, mixed $value, string $group = 'general'): self
    {
        $stringValue = is_array($value) ? json_encode($value) : (string) $value;

        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $stringValue, 'group' => $group]
        );

        Cache::forget("setting_{$key}");

        return $setting;
    }
}
