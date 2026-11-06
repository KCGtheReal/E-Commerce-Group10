<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'name_km' => 'ម៉ូដ '.ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'description_km' => fake()->sentence(),
            'image_url' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
