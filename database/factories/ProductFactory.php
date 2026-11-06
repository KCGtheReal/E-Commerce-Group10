<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => ucwords($name),
            'name_km' => 'អាវ '.ucwords($name),
            'slug' => Str::slug($name),
            'sku_prefix' => 'RST-'.strtoupper(fake()->lexify('???')),
            'tagline' => 'Start fresh.',
            'tagline_km' => 'ចាប់ផ្តើមថ្មី។',
            'description' => fake()->paragraph(),
            'description_km' => fake()->paragraph(),
            'details' => [
                'fabric' => '100% Organic Heavyweight Cotton, 260 GSM',
                'care' => 'Machine wash cold inside out. Hang dry.',
                'fit' => 'Boxy relaxed fit with drop shoulders.',
            ],
            'base_price_cents' => fake()->randomElement([2500, 3200, 4500, 5800]),
            'compare_at_price_cents' => null,
            'is_featured' => false,
            'is_new_arrival' => true,
            'is_best_seller' => false,
            'is_published' => true,
        ];
    }
}
