<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Packed = 'packed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function label(): string
    {
        $key = 'store.order_status_'.$this->value;
        $label = __($key);

        return $label !== $key ? $label : match ($this) {
            self::PendingPayment => 'Pending Payment',
            self::Paid => 'Paid',
            self::Packed => 'Packed',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
            self::Refunded => 'Refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::Paid => 'success',
            self::Packed => 'info',
            self::Shipped => 'primary',
            self::Delivered => 'success',
            self::Cancelled, self::Expired, self::Refunded => 'danger',
        };
    }
}
