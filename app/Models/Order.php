<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Services\CurrencyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'user_id',
        'email',
        'phone',
        'shipping_address',
        'billing_address',
        'subtotal_cents',
        'discount_cents',
        'discount_code',
        'shipping_fee_cents',
        'shipping_method',
        'total_cents',
        'currency',
        'exchange_rate',
        'total_charged_currency_amount',
        'status',
        'tracking_number',
        'shipping_carrier',
        'customer_notes',
        'refund_amount_cents',
        'refund_note',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'shipping_fee_cents' => 'integer',
            'total_cents' => 'integer',
            'exchange_rate' => 'integer',
            'total_charged_currency_amount' => 'decimal:2',
            'refund_amount_cents' => 'integer',
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return CurrencyService::formatCents($this->subtotal_cents, $this->currency);
    }

    public function getFormattedDiscountAttribute(): string
    {
        return '-'.CurrencyService::formatCents($this->discount_cents, $this->currency);
    }

    public function getFormattedShippingAttribute(): string
    {
        if ($this->shipping_fee_cents === 0) {
            return $this->currency === 'USD' ? 'FREE' : '0 ៛';
        }

        return CurrencyService::formatCents($this->shipping_fee_cents, $this->currency);
    }

    public function getFormattedTotalAttribute(): string
    {
        return CurrencyService::formatCents($this->total_cents, $this->currency);
    }

    public function getFormattedChargedTotalAttribute(): string
    {
        return CurrencyService::formatAmount((float) $this->total_charged_currency_amount, $this->currency);
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid
            || $this->status === OrderStatus::Packed
            || $this->status === OrderStatus::Shipped
            || $this->status === OrderStatus::Delivered;
    }

    public function canBeCancelled(): bool
    {
        return $this->status === OrderStatus::PendingPayment;
    }
}
