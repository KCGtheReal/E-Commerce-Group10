<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Expired = 'expired';
    case Failed = 'failed';

    public function label(): string
    {
        $key = 'store.payment_status_badge_'.$this->value;
        $label = __($key);

        return $label !== $key ? $label : match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Expired => 'Expired',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Expired => 'gray',
            self::Failed => 'danger',
        };
    }
}
