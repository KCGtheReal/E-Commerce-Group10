<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'md5',
        'qr_string',
        'account_id',
        'amount',
        'currency',
        'status',
        'expires_at',
        'paid_at',
        'last_checked_at',
        'bakong_response',
        'error_message',
        'manual_paid_note',
        'manual_paid_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'bakong_response' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function manualPaidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manual_paid_by');
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function isExpired(): bool
    {
        return $this->status === PaymentStatus::Expired || ($this->isPending() && now()->gte($this->expires_at));
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::Failed;
    }

    public function getTimeRemainingSecondsAttribute(): int
    {
        if (! $this->isPending()) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($this->expires_at, false));
    }

    public function getFormattedAmountAttribute(): string
    {
        if ($this->currency === 'KHR') {
            return number_format((float) $this->amount, 0).' ៛';
        }

        return '$'.number_format((float) $this->amount, 2);
    }
}
