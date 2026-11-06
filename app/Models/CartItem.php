<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\CurrencyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_variant_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getSubtotalCentsAttribute(): int
    {
        $price = $this->variant ? $this->variant->price_cents : 0;

        return (int) ($price * $this->quantity);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return CurrencyService::formatCents($this->subtotal_cents);
    }
}
