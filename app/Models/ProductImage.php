<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\CatalogCacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(static fn (): int => CatalogCacheService::bumpCatalogEpoch());
        static::deleted(static fn (): int => CatalogCacheService::bumpCatalogEpoch());
    }

    protected $fillable = [
        'product_id',
        'image_url',
        'alt_text',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
