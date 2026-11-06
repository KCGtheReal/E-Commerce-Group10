<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = 5000;
        $shipping = 150;
        $total = $subtotal + $shipping;

        return [
            'number' => 'RST-'.strtoupper(fake()->unique()->bothify('####-????')),
            'user_id' => User::factory(),
            'email' => fake()->safeEmail(),
            'phone' => '012'.fake()->numerify('######'),
            'shipping_address' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'phone' => '012345678',
                'province' => 'Phnom Penh',
                'district' => 'Daun Penh',
                'commune' => 'Phsar Thmei',
                'street_address' => 'House 12, Street 130',
            ],
            'subtotal_cents' => $subtotal,
            'discount_cents' => 0,
            'shipping_fee_cents' => $shipping,
            'shipping_method' => 'standard',
            'total_cents' => $total,
            'currency' => 'KHR',
            'exchange_rate' => 4100,
            'total_charged_currency_amount' => $total,
            'status' => OrderStatus::PendingPayment,
        ];
    }
}
