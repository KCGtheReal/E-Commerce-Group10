<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'bakong_khqr',
            'md5' => md5(fake()->uuid()),
            'qr_string' => '00020101021229...mock',
            'account_id' => 'reset_store@devb',
            'amount' => 51.50,
            'currency' => 'USD',
            'status' => PaymentStatus::Pending,
            'expires_at' => now()->addSeconds(300),
        ];
    }
}
