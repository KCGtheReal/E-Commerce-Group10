<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\CurrencyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_variant_id',
        'product_name',
        'product_name_km',
        'variant_label',
        'sku',
        'price_cents',
        'quantity',
        'subtotal_cents',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'quantity' => 'integer',
            'subtotal_cents' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getLocalizedProductNameAttribute(): string
    {
        if (app()->getLocale() === 'km' && ! empty($this->product_name_km)) {
            return $this->product_name_km;
        }

        return $this->product_name;
    }

    public function getFormattedPriceAttribute(): string
    {
        return CurrencyService::formatCents($this->price_cents, $this->order?->currency);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return CurrencyService::formatCents($this->subtotal_cents, $this->order?->currency);
    }
}
