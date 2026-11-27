<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseCacheSerializationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_cached_api_and_blade_endpoints_succeed_on_warm_database_cache_with_serializable_classes_false(): void
    {
        $this->assertFalse((bool) config('cache.serializable_classes'));

        config(['cache.default' => 'database']);
        Cache::setDefaultDriver('database');
        Cache::store('database')->clear();

        Setting::set('exchange_rate', 4100);
        Setting::set('free_shipping_threshold', 200000);

        $category = Category::factory()->create([
            'name' => 'Tees & Tops',
            'name_km' => 'អាវយឺត និងអាវលើ',
            'slug' => 'tees-and-tops',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $primary = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Primary Boxy Tee',
            'name_km' => 'អាវយឺតធំដើម',
            'slug' => 'primary-boxy-tee',
            'base_price_cents' => 41000,
            'is_featured' => true,
            'is_new_arrival' => true,
            'is_best_seller' => true,
            'is_published' => true,
        ]);
        ProductImage::create([
            'product_id' => $primary->id,
            'image_url' => 'https://example.com/primary.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $primary->id,
            'size' => 'M',
            'color' => 'Onyx Black',
            'price_cents' => 41000,
            'stock_quantity' => 5,
        ]);

        $related = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Related Minimal Tee',
            'name_km' => 'អាវយឺតសាមញ្ញពាក់ព័ន្ធ',
            'slug' => 'related-minimal-tee',
            'base_price_cents' => 82000,
            'is_featured' => true,
            'is_new_arrival' => true,
            'is_best_seller' => true,
            'is_published' => true,
        ]);
        ProductImage::create([
            'product_id' => $related->id,
            'image_url' => 'https://example.com/related.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $related->id,
            'size' => 'L',
            'color' => 'Raw Bone',
            'price_cents' => 82000,
            'stock_quantity' => 5,
        ]);

        $enHeaders = ['X-Locale' => 'en', 'X-Currency' => 'KHR'];
        $kmUsdHeaders = ['X-Locale' => 'km', 'X-Currency' => 'USD'];

        // 1. /api/v1/bootstrap (cold + warm EN, then cold + warm KM)
        $this->getJson('/api/v1/bootstrap', $enHeaders)
            ->assertOk()
            ->assertJsonPath('categories.0.name', 'Tees & Tops');
        $this->getJson('/api/v1/bootstrap', $enHeaders)
            ->assertOk()
            ->assertJsonPath('categories.0.name', 'Tees & Tops');

        $this->getJson('/api/v1/bootstrap', $kmUsdHeaders)
            ->assertOk()
            ->assertJsonPath('categories.0.name', 'អាវយឺត និងអាវលើ');
        $this->getJson('/api/v1/bootstrap', $kmUsdHeaders)
            ->assertOk()
            ->assertJsonPath('categories.0.name', 'អាវយឺត និងអាវលើ');

        // 2. /api/v1/home (cold + warm EN/KHR, then cold + warm KM/USD)
        $this->getJson('/api/v1/home', $enHeaders)
            ->assertOk()
            ->assertJsonPath('categories.0.name', 'Tees & Tops')
            ->assertJsonPath('featured_products.0.formatted_price', '41,000 ៛');
        $this->getJson('/api/v1/home', $enHeaders)
            ->assertOk()
            ->assertJsonPath('categories.0.name', 'Tees & Tops')
            ->assertJsonPath('featured_products.0.formatted_price', '41,000 ៛');

        $this->getJson('/api/v1/home', $kmUsdHeaders)
            ->assertOk()
            ->assertJsonPath('categories.0.name', 'អាវយឺត និងអាវលើ')
            ->assertJsonPath('featured_products.0.name', 'អាវយឺតធំដើម')
            ->assertJsonPath('featured_products.0.formatted_price', '$10.00');
        $this->getJson('/api/v1/home', $kmUsdHeaders)
            ->assertOk()
            ->assertJsonPath('categories.0.name', 'អាវយឺត និងអាវលើ')
            ->assertJsonPath('featured_products.0.name', 'អាវយឺតធំដើម')
            ->assertJsonPath('featured_products.0.formatted_price', '$10.00');

        // 3. /api/v1/products (cold + warm EN/KHR, then KM/USD on warm shop:facets cache)
        $this->getJson('/api/v1/products', $enHeaders)
            ->assertOk()
            ->assertJsonPath('facets.categories.0.name', 'Tees & Tops');
        $this->getJson('/api/v1/products', $enHeaders)
            ->assertOk()
            ->assertJsonPath('facets.categories.0.name', 'Tees & Tops');

        $this->getJson('/api/v1/products', $kmUsdHeaders)
            ->assertOk()
            ->assertJsonPath('facets.categories.0.name', 'អាវយឺត និងអាវលើ')
            ->assertJsonPath('facets.currency', 'USD');

        // 4. /api/v1/products/{slug} (cold + warm EN/KHR, then KM/USD)
        $this->getJson('/api/v1/products/primary-boxy-tee', $enHeaders)
            ->assertOk()
            ->assertJsonPath('related_products.0.slug', 'related-minimal-tee')
            ->assertJsonPath('related_products.0.formatted_price', '82,000 ៛');
        $this->getJson('/api/v1/products/primary-boxy-tee', $enHeaders)
            ->assertOk()
            ->assertJsonPath('related_products.0.slug', 'related-minimal-tee')
            ->assertJsonPath('related_products.0.formatted_price', '82,000 ៛');

        $this->getJson('/api/v1/products/primary-boxy-tee', $kmUsdHeaders)
            ->assertOk()
            ->assertJsonPath('related_products.0.name', 'អាវយឺតសាមញ្ញពាក់ព័ន្ធ')
            ->assertJsonPath('related_products.0.formatted_price', '$20.00');
        $this->getJson('/api/v1/products/primary-boxy-tee', $kmUsdHeaders)
            ->assertOk()
            ->assertJsonPath('related_products.0.name', 'អាវយឺតសាមញ្ញពាក់ព័ន្ធ')
            ->assertJsonPath('related_products.0.formatted_price', '$20.00');

        // 5. Blade /product/{slug} (cold + warm ID list cache)
        $this->get('/product/primary-boxy-tee')
            ->assertOk()
            ->assertSee('Related Minimal Tee');
        $this->get('/product/primary-boxy-tee')
            ->assertOk()
            ->assertSee('Related Minimal Tee');

        // Verify zero serialized PHP objects exist inside the database cache table
        $cachedRows = DB::table('cache')->get();
        $this->assertNotEmpty($cachedRows);
        foreach ($cachedRows as $row) {
            $raw = (string) $row->value;
            $this->assertDoesNotMatchRegularExpression('/(^|;)O:\d+:"/', $raw, "Cache key {$row->key} serialized a PHP object.");
            if (preg_match('/^[abisd]:|^a:\d+:\{/', $raw) === 1) {
                $decoded = @unserialize($raw, ['allowed_classes' => false]);
                $this->assertNotInstanceOf(\__PHP_Incomplete_Class::class, $decoded);
            }
        }
    }
}
