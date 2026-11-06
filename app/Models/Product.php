<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\CurrencyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'name_km',
        'slug',
        'sku_prefix',
        'tagline',
        'tagline_km',
        'description',
        'description_km',
        'details',
        'base_price_cents',
        'compare_at_price_cents',
        'is_featured',
        'is_new_arrival',
        'is_best_seller',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'base_price_cents' => 'integer',
            'compare_at_price_cents' => 'integer',
            'is_featured' => 'boolean',
            'is_new_arrival' => 'boolean',
            'is_best_seller' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true)->latest();
    }

    public function allReviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function primaryImage(): ?ProductImage
    {
        return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
    }

    public function getPrimaryImageUrlAttribute(): string
    {
        return $this->primaryImage()?->image_url ?? 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80';
    }

    public function getLocalizedNameAttribute(): string
    {
        if (app()->getLocale() === 'km' && ! empty($this->name_km)) {
            return $this->name_km;
        }

        return $this->name;
    }

    public function getLocalizedTaglineAttribute(): ?string
    {
        if (app()->getLocale() === 'km' && ! empty($this->tagline_km)) {
            return $this->tagline_km;
        }

        return $this->tagline;
    }

    public function getLocalizedDescriptionAttribute(): string
    {
        if (app()->getLocale() === 'km' && ! empty($this->description_km)) {
            return $this->description_km;
        }

        return $this->description;
    }

    public function getFormattedPriceAttribute(): string
    {
        return CurrencyService::formatCents($this->base_price_cents);
    }

    public function getFormattedCompareAtPriceAttribute(): ?string
    {
        if (! $this->compare_at_price_cents) {
            return null;
        }

        return CurrencyService::formatCents($this->compare_at_price_cents);
    }

    public function getTotalStockAttribute(): int
    {
        return (int) $this->variants->sum(fn (ProductVariant $v) => $v->available_stock);
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->total_stock > 0;
    }

    public function getAverageRatingAttribute(): float
    {
        return (float) ($this->reviews()->avg('rating') ?: 5.0);
    }

    public function getReviewCountAttribute(): int
    {
        return (int) $this->reviews()->count();
    }
}
