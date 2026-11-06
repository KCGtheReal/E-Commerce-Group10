<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscountType;
use App\Services\CurrencyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_spend_cents',
        'max_uses',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value' => 'integer',
            'min_spend_cents' => 'integer',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isValidFor(int $subtotalCents): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return false;
        }

        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return false;
        }

        if ($subtotalCents < $this->min_spend_cents) {
            return false;
        }

        return true;
    }

    public function getFormattedValueAttribute(): string
    {
        return match ($this->type) {
            DiscountType::Percentage => "{$this->value}%",
            DiscountType::FixedAmount => CurrencyService::formatCents($this->value),
            DiscountType::FreeShipping => 'Free Shipping',
        };
    }

    public function calculateDiscount(int $subtotalCents, int $shippingFeeCents = 0): int
    {
        if (! $this->isValidFor($subtotalCents)) {
            return 0;
        }

        return match ($this->type) {
            DiscountType::Percentage => (int) round(($subtotalCents * $this->value) / 100),
            DiscountType::FixedAmount => min($subtotalCents, $this->value),
            DiscountType::FreeShipping => $shippingFeeCents,
        };
    }
}
