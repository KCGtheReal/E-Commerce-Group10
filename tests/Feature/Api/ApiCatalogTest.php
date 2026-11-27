<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Models\ApiToken;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_and_home_honor_locale_and_currency_headers(): void
    {
        Setting::set('exchange_rate', 4100);
        Setting::set('free_shipping_threshold', 200000);
        Setting::set('announcement_banner_km', 'ការដឹកជញ្ជូនឥតគិតថ្លៃ KM');

        $category = Category::factory()->create([
            'name' => 'Tees & Tops',
            'name_km' => 'អាវយឺត',
            'slug' => 'tees-and-tops',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Boxy Tee',
            'name_km' => 'អាវយឺតធំ',
            'base_price_cents' => 41000,
            'compare_at_price_cents' => 82000,
            'is_featured' => true,
            'is_new_arrival' => true,
            'is_best_seller' => true,
            'is_published' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 41000,
            'stock_quantity' => 5,
        ]);

        $bootstrap = $this->withHeaders([
            'X-Locale' => 'km',
            'X-Currency' => 'USD',
        ])->getJson('/api/v1/bootstrap');

        $bootstrap->assertOk()
            ->assertJsonPath('locale', 'km')
            ->assertJsonPath('currency', 'USD')
            ->assertJsonPath('settings.express_shipping_fee_khr', 12000)
            ->assertJsonPath('settings.express_shipping_province', 'Phnom Penh')
            ->assertJsonPath('settings.announcement_banner', 'ការដឹកជញ្ជូនឥតគិតថ្លៃ KM')
            ->assertJsonCount(25, 'settings.provinces')
            ->assertJsonCount(2, 'size_guide.tables')
            ->assertJsonCount(6, 'pages');

        $home = $this->withHeaders([
            'X-Locale' => 'km',
            'X-Currency' => 'USD',
        ])->getJson('/api/v1/home');

        $home->assertOk()
            ->assertJsonPath('categories.0.name', 'អាវយឺត')
            ->assertJsonPath('featured_products.0.name', 'អាវយឺតធំ')
            ->assertJsonPath('featured_products.0.formatted_price', '$10.00')
            ->assertJsonPath('featured_products.0.is_on_sale', true);
    }

    public function test_products_filtering_sorting_currency_price_bounds_and_search_suggest(): void
    {
        Setting::set('exchange_rate', 4100);

        $tees = Category::factory()->create(['name' => 'Tees', 'slug' => 'tees']);
        $hoodies = Category::factory()->create(['name' => 'Hoodies', 'slug' => 'hoodies']);

        $p1 = Product::factory()->create([
            'category_id' => $tees->id,
            'name' => 'Onyx Heavy Tee',
            'slug' => 'onyx-heavy-tee',
            'base_price_cents' => 41000, // $10
            'is_published' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $p1->id,
            'size' => 'M',
            'color' => 'Onyx Black',
            'price_cents' => 41000,
            'stock_quantity' => 4,
        ]);

        $p2 = Product::factory()->create([
            'category_id' => $hoodies->id,
            'name' => 'Bone French Hoodie',
            'slug' => 'bone-french-hoodie',
            'base_price_cents' => 205000, // $50
            'is_published' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $p2->id,
            'size' => 'XL',
            'color' => 'Raw Bone',
            'price_cents' => 205000,
            'stock_quantity' => 0,
        ]);

        // Currency-aware USD price range filter: max_price=15 USD (= 61,500 KHR) matches only $10 tee
        $res = $this->withHeaders(['X-Currency' => 'USD'])
            ->getJson('/api/v1/products?max_price=15&in_stock=1&size[]=M&size[]=L');

        $res->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'onyx-heavy-tee')
            ->assertJsonPath('facets.currency', 'USD')
            ->assertJsonPath('facets.all_products_count', 2);

        $emptySuggest = $this->getJson('/api/v1/search/suggest');
        $emptySuggest->assertOk()
            ->assertJsonCount(5, 'popular_searches')
            ->assertJsonPath('total', 0);

        $suggest = $this->getJson('/api/v1/search/suggest?q=onyx');
        $suggest->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('products.0.slug', 'onyx-heavy-tee')
            ->assertJsonPath('products.0.price_cents', 41000);
    }

    public function test_product_detail_emits_5_tabs_and_authenticated_review_and_wishlist_work(): void
    {
        $user = User::factory()->create(['name' => 'Dara Sok']);
        $token = ApiToken::issueFor($user)['plainTextToken'];

        $category = Category::factory()->create(['slug' => 'tees']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'minimal-tee',
            'details' => [
                'fabric' => '280 GSM Cotton',
                'care' => 'Cold wash only',
            ],
            'is_published' => true,
        ]);
        ProductImage::create([
            'product_id' => $product->id,
            'image_url' => 'https://example.com/tee.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Onyx Black',
            'stock_quantity' => 5,
        ]);

        // Verified buyer order
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Paid,
        ]);
        $order->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_label' => $variant->label,
            'sku' => $variant->sku,
            'price_cents' => 100000,
            'quantity' => 1,
            'subtotal_cents' => 100000,
        ]);

        // Toggle wishlist ON
        $this->withToken($token)
            ->postJson('/api/v1/wishlist/toggle', ['product_id' => $product->id])
            ->assertOk()
            ->assertJsonPath('added', true)
            ->assertJsonPath('total_wishlists', 1);

        $this->withToken($token)
            ->getJson('/api/v1/wishlist')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.slug', 'minimal-tee');

        // Submit review
        $this->withToken($token)
            ->postJson('/api/v1/products/minimal-tee/reviews', [
                'rating' => 5,
                'title' => 'Solid drape',
                'body' => 'Great heavyweight cotton feel.',
            ])
            ->assertCreated()
            ->assertJsonPath('review.is_verified_purchase', true)
            ->assertJsonPath('review_count', 1);

        // Fetch detail
        $detail = $this->withToken($token)->getJson('/api/v1/products/minimal-tee');
        $detail->assertOk()
            ->assertJsonPath('has_purchased', true)
            ->assertJsonPath('in_wishlist', true)
            ->assertJsonCount(5, 'product.detail_tabs')
            ->assertJsonPath('product.detail_tabs.0.key', 'description')
            ->assertJsonPath('product.detail_tabs.3.key', 'fit')
            ->assertJsonPath('product.detail_tabs.4.key', 'shipping')
            ->assertJsonCount(1, 'product.reviews');
    }
}
