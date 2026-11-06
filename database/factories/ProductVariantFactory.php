<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => 'RST-'.strtoupper(fake()->bothify('###-??')),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'color' => fake()->randomElement(['Onyx Black', 'Bone White', 'Concrete Gray']),
            'color_code' => '#111111',
            'price_cents' => 3500,
            'stock_quantity' => 15,
            'reserved_quantity' => 0,
        ];
    }
}
