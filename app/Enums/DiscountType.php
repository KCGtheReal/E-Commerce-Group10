<?php

declare(strict_types=1);

namespace App\Enums;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case FixedAmount = 'fixed_amount';
    case FreeShipping = 'free_shipping';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage Discount',
            self::FixedAmount => 'Fixed Amount Discount',
            self::FreeShipping => 'Free Shipping',
        };
    }
}
