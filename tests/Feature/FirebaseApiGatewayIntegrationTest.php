<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\ApiToken;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\FirebaseApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class FirebaseApiGatewayIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function enableGateway(string $apiKey = 'test-local-gateway-key'): void
    {
        config([
            'firebase_api.enabled' => true,
            'firebase_api.base_url' => 'http://127.0.0.1:4000',
            'firebase_api.api_key' => $apiKey,
            'firebase_api.timeout_seconds' => 2.0,
            'firebase_api.connect_timeout_seconds' => 0.5,
        ]);
    }

    public function test_api_v1_catalog_and_blade_suggest_delegate_to_firebase_gateway_when_enabled(): void
    {
        $this->enableGateway();

        $user = User::factory()->create(['name' => 'Sokha Meas', 'email' => 'sokha@reset.store']);
        $token = ApiToken::issueFor($user)['plainTextToken'];

        $category = Category::factory()->create(['name' => 'SQLite Cat', 'slug' => 'sqlite-cat']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'SQLite Tee',
            'slug' => 'sqlite-tee',
            'base_price_cents' => 82000,
            'is_published' => true,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 82000,
            'stock_quantity' => 5,
        ]);

        $cart = Cart::create([
            'user_id' => $user->id,
            'cart_token' => 'cart-tok-123',
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        config([
            'bakong.mock' => true,
            'bakong.currency' => 'USD',
            'bakong.qr_ttl_seconds' => 420,
        ]);

        Http::fake([
            'http://127.0.0.1:4000/api/firebase/home' => Http::response([
                'source' => 'firebase',
                'cached' => true,
                'categories' => [['id' => 99, 'name' => 'Firebase Tees', 'name_en' => 'Firebase Tees', 'name_km' => null, 'slug' => 'firebase-tees', 'description' => null, 'description_en' => null, 'description_km' => null, 'image_url' => null, 'sort_order' => 1, 'products_count' => 1]],
                'featured_products' => [['id' => 701, 'name' => 'Gateway Boxy Tee', 'slug' => 'gateway-boxy-tee']],
                'new_arrivals' => [],
                'best_sellers' => [],
            ], 200),
            'http://127.0.0.1:4000/api/firebase/products?*' => Http::response([
                'source' => 'firebase',
                'cached' => false,
                'data' => [['id' => 701, 'name' => 'Gateway Filtered Tee', 'slug' => 'gateway-filtered-tee']],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 12, 'total' => 1, 'from' => 1, 'to' => 1, 'has_more_pages' => false],
                'facets' => ['all_products_count' => 1, 'categories' => [], 'available_sizes' => ['M', 'L'], 'available_colors' => [], 'active_filter_count' => 3, 'currency' => 'USD', 'exchange_rate' => 4100],
            ], 200),
            'http://127.0.0.1:4000/api/firebase/search/suggest*' => Http::response([
                'source' => 'firebase',
                'cached' => true,
                'query' => 'boxy',
                'popular_searches' => [],
                'categories' => [['name' => 'Firebase Tees', 'slug' => 'firebase-tees']],
                'products' => [[
                    'id' => 701,
                    'name' => 'Gateway Boxy Tee',
                    'slug' => 'gateway-boxy-tee',
                    'image_url' => 'https://example.com/boxy.jpg',
                    'price' => '$18.00',
                    'compare_at_price' => null,
                    'price_cents' => 73800,
                    'compare_at_price_cents' => null,
                    'category' => 'Firebase Tees',
                    'is_in_stock' => true,
                ]],
                'total' => 1,
            ], 200),
            'http://127.0.0.1:4000/api/firebase/products/gateway-boxy-tee' => Http::response([
                'source' => 'firebase',
                'cached' => true,
                'product' => ['id' => 701, 'name' => 'Gateway Boxy Tee', 'slug' => 'gateway-boxy-tee'],
                'has_purchased' => false,
                'in_wishlist' => false,
                'related_products' => [],
            ], 200),
            'http://127.0.0.1:4000/api/firebase/bootstrap' => Http::response([
                'source' => 'firebase',
                'cached' => true,
                'locale' => 'km',
                'currency' => 'USD',
                'settings' => [
                    'store_name' => 'RESET Firebase Store',
                    'store_tagline' => 'Start fresh.',
                    'store_tagline_km' => 'ចាប់ផ្តើមថ្មី។',
                    'store_email' => 'support@reset.store',
                    'store_phone' => '+855 23 999 123',
                    'store_address' => 'Phnom Penh',
                    'announcement_banner_en' => 'Banner EN',
                    'announcement_banner_km' => 'Banner KM',
                    'announcement_banner' => 'Banner KM',
                    'exchange_rate' => 4100,
                    'free_shipping_threshold' => 200000,
                    'formatted_free_shipping_threshold' => '$48.78',
                    'express_shipping_fee_khr' => 12000,
                    'express_shipping_province' => 'Phnom Penh',
                    'province_shipping_fees' => ['Phnom Penh' => 0],
                    'provinces' => ['Phnom Penh'],
                    'bakong_mock' => false,
                    'bakong_currency' => 'KHR',
                    'qr_ttl_seconds' => 999,
                ],
                'categories' => [['id' => 99, 'name' => 'Firebase Tees', 'name_en' => 'Firebase Tees', 'name_km' => 'អាវយឺត', 'slug' => 'firebase-tees', 'description' => null, 'description_en' => null, 'description_km' => null, 'image_url' => null, 'sort_order' => 1, 'products_count' => 1]],
                'authenticated' => false,
                'user' => null,
                'cart' => null,
            ], 200),
        ]);

        // 1. /api/v1/home
        $this->withHeaders(['X-Locale' => 'km', 'X-Currency' => 'USD'])
            ->getJson('/api/v1/home')
            ->assertOk()
            ->assertJsonPath('source', 'firebase')
            ->assertJsonPath('featured_products.0.slug', 'gateway-boxy-tee');

        // 2. /api/v1/products
        $this->withHeaders(['X-Locale' => 'km', 'X-Currency' => 'USD'])
            ->getJson('/api/v1/products?max_price=15&in_stock=1&size[]=M&size[]=L')
            ->assertOk()
            ->assertJsonPath('source', 'firebase')
            ->assertJsonPath('data.0.slug', 'gateway-filtered-tee');

        // 3. /api/v1/search/suggest
        $this->withHeaders(['X-Locale' => 'en', 'X-Currency' => 'USD'])
            ->getJson('/api/v1/search/suggest?q=boxy')
            ->assertOk()
            ->assertJsonPath('source', 'firebase')
            ->assertJsonPath('products.0.slug', 'gateway-boxy-tee');

        // 4. /api/v1/products/{slug} (unauthenticated)
        $this->getJson('/api/v1/products/gateway-boxy-tee')
            ->assertOk()
            ->assertJsonPath('source', 'firebase')
            ->assertJsonPath('product.slug', 'gateway-boxy-tee');

        // 5. /api/v1/bootstrap merges authoritative Bakong config, user, and cart
        $this->withHeaders(['X-Locale' => 'km', 'X-Currency' => 'USD'])
            ->withToken($token)
            ->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('settings.store_name', 'RESET Firebase Store')
            ->assertJsonPath('settings.bakong_mock', true)
            ->assertJsonPath('settings.bakong_currency', 'USD')
            ->assertJsonPath('settings.qr_ttl_seconds', 420)
            ->assertJsonPath('categories.0.slug', 'firebase-tees')
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('user.email', 'sokha@reset.store')
            ->assertJsonPath('cart.item_count', 2);

        // 6. Blade /search/suggest adds url & view_all_url
        $this->getJson(route('search.suggest', ['q' => 'boxy']))
            ->assertOk()
            ->assertJsonPath('query', 'boxy')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('products.0.url', route('product.show', 'gateway-boxy-tee'))
            ->assertJsonPath('categories.0.url', route('shop.index', ['category' => 'firebase-tees']))
            ->assertJsonPath('view_all_url', route('shop.index', ['q' => 'boxy']));

        Http::assertSent(static function (HttpClientRequest $req): bool {
            return str_contains($req->url(), '/api/firebase/products?')
                && $req->hasHeader('X-Locale', 'km')
                && $req->hasHeader('X-Currency', 'USD')
                && $req['size'] === ['M', 'L'];
        });
    }

    public function test_unauthenticated_product_detail_preserves_404_when_firebase_reports_not_found(): void
    {
        $this->enableGateway();

        $category = Category::factory()->create(['slug' => 'tees']);
        Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'unpublished-in-firestore',
            'is_published' => true,
        ]);

        Http::fake([
            'http://127.0.0.1:4000/api/firebase/products/unpublished-in-firestore' => Http::response([
                'success' => false,
                'source' => 'firebase',
                'message' => "Product 'unpublished-in-firestore' was not found.",
                'code' => 'NOT_FOUND',
            ], 404),
        ]);

        $this->getJson('/api/v1/products/unpublished-in-firestore')
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_authenticated_product_detail_computes_has_purchased_and_in_wishlist_from_sqlite(): void
    {
        $user = User::factory()->create();
        $token = ApiToken::issueFor($user)['plainTextToken'];

        $category = Category::factory()->create(['slug' => 'tees']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'auth-verified-tee',
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
            'stock_quantity' => 5,
        ]);

        Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

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

        $this->enableGateway();
        Http::fake([
            '*' => Http::response(['unexpected' => true], 500),
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/products/auth-verified-tee')
            ->assertOk()
            ->assertJsonPath('product.slug', 'auth-verified-tee')
            ->assertJsonPath('has_purchased', true)
            ->assertJsonPath('in_wishlist', true);

        Http::assertNothingSent();
    }

    public function test_automatic_sqlite_fallback_succeeds_on_500_or_connection_exception(): void
    {
        $this->enableGateway();
        Setting::set('store_name', 'SQLite Fallback Store');
        Setting::set('exchange_rate', 4100);

        $category = Category::factory()->create(['name' => 'Fallback Tees', 'slug' => 'fallback-tees', 'is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'SQLite Fallback Boxy Tee',
            'slug' => 'sqlite-fallback-boxy-tee',
            'base_price_cents' => 120000,
            'is_featured' => true,
            'is_published' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'size' => 'M',
            'price_cents' => 120000,
            'stock_quantity' => 8,
        ]);

        // 1. Gateway returns HTTP 500
        Http::fake([
            'http://127.0.0.1:4000/*' => Http::response(['message' => 'Gateway error'], 500),
        ]);

        $this->getJson('/api/v1/home')
            ->assertOk()
            ->assertJsonPath('featured_products.0.slug', 'sqlite-fallback-boxy-tee');

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'sqlite-fallback-boxy-tee');

        $this->getJson('/api/v1/search/suggest?q=Fallback')
            ->assertOk()
            ->assertJsonPath('products.0.slug', 'sqlite-fallback-boxy-tee');

        $this->getJson('/api/v1/products/sqlite-fallback-boxy-tee')
            ->assertOk()
            ->assertJsonPath('product.slug', 'sqlite-fallback-boxy-tee');

        $this->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('settings.store_name', 'SQLite Fallback Store')
            ->assertJsonPath('categories.0.slug', 'fallback-tees');

        $this->getJson(route('search.suggest', ['q' => 'Fallback']))
            ->assertOk()
            ->assertJsonPath('products.0.slug', 'sqlite-fallback-boxy-tee')
            ->assertJsonPath('products.0.url', route('product.show', 'sqlite-fallback-boxy-tee'));

        // 2. Gateway throws ConnectionException (server offline)
        Http::fake([
            'http://127.0.0.1:4000/*' => static function (): never {
                throw new ConnectionException('Connection refused to 127.0.0.1:4000');
            },
        ]);

        $this->getJson('/api/v1/home')
            ->assertOk()
            ->assertJsonPath('featured_products.0.slug', 'sqlite-fallback-boxy-tee');

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'sqlite-fallback-boxy-tee');

        $this->getJson('/api/v1/search/suggest?q=Fallback')
            ->assertOk()
            ->assertJsonPath('products.0.slug', 'sqlite-fallback-boxy-tee');

        $this->getJson('/api/v1/products/sqlite-fallback-boxy-tee')
            ->assertOk()
            ->assertJsonPath('product.slug', 'sqlite-fallback-boxy-tee');

        $this->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('settings.store_name', 'SQLite Fallback Store');

        $this->getJson(route('search.suggest'))
            ->assertOk()
            ->assertJsonPath('categories.0.slug', 'fallback-tees')
            ->assertJsonPath('view_all_url', route('shop.index'));
    }

    public function test_observer_invalidates_gateway_cache_with_api_key_and_never_breaks_saves(): void
    {
        $this->enableGateway('local-test-api-key');

        Http::fake([
            'http://127.0.0.1:4000/api/firebase/cache/invalidate' => Http::response([
                'source' => 'firebase',
                'invalidated_scopes' => ['categories'],
                'cleared_entries' => 2,
                'timestamp' => '2026-11-27T00:00:00Z',
            ], 200),
        ]);

        // 1. Rolled-back transaction must NOT invalidate cache
        try {
            DB::transaction(static function (): void {
                Category::factory()->create(['slug' => 'rolled-back-gateway-cat']);
                throw new RuntimeException('Rollback');
            });
        } catch (RuntimeException) {
        }

        Http::assertNothingSent();

        // 2. Committed Category & Product saves trigger POST /api/firebase/cache/invalidate with X-API-Key
        $category = Category::factory()->create(['slug' => 'cache-bust-cat']);
        Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'cache-bust-prod',
        ]);

        Http::assertSent(static function (HttpClientRequest $req): bool {
            return $req->method() === 'POST'
                && $req->url() === 'http://127.0.0.1:4000/api/firebase/cache/invalidate'
                && $req->hasHeader('X-API-Key', 'local-test-api-key')
                && ($req->data()['scopes'] ?? null) === ['categories'];
        });

        Http::assertSent(static function (HttpClientRequest $req): bool {
            return $req->method() === 'POST'
                && $req->url() === 'http://127.0.0.1:4000/api/firebase/cache/invalidate'
                && $req->hasHeader('X-API-Key', 'local-test-api-key')
                && ($req->data()['scopes'] ?? null) === ['products'];
        });

        // 3. Cache invalidation failure (connection error / 500) must never throw or fail local DB save
        Http::fake([
            'http://127.0.0.1:4000/api/firebase/cache/invalidate' => static function (): never {
                throw new ConnectionException('Gateway offline');
            },
        ]);

        $category->update(['name' => 'Updated Safely Offline']);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Safely Offline',
        ]);
    }

    public function test_firebase_api_client_additional_endpoints_work_and_fail_open(): void
    {
        $this->enableGateway();

        Http::fake([
            'http://127.0.0.1:4000/health' => Http::response(['status' => 'ok', 'source' => 'firebase'], 200),
            'http://127.0.0.1:4000/api/firebase/categories' => Http::response(['source' => 'firebase', 'data' => []], 200),
            'http://127.0.0.1:4000/api/firebase/settings' => Http::response(['source' => 'firebase', 'data' => ['store_name' => 'RESET Store']], 200),
            'http://127.0.0.1:4000/api/firebase/reviews*' => Http::response(['source' => 'firebase', 'data' => []], 200),
        ]);

        /** @var FirebaseApiClient $client */
        $client = app(FirebaseApiClient::class);

        $this->assertTrue($client->isEnabled());
        $this->assertSame('ok', $client->health()['status'] ?? null);
        $this->assertSame('firebase', $client->categories('en', 'KHR')['source'] ?? null);
        $this->assertSame('RESET Store', $client->settings()['data']['store_name'] ?? null);
        $this->assertSame('firebase', $client->reviews(['product_id' => 1])['source'] ?? null);

        Http::fake([
            'http://127.0.0.1:4000/*' => static function (): never {
                throw new ConnectionException('Timeout');
            },
        ]);

        $this->assertNull($client->health());
        $this->assertNull($client->categories('en', 'KHR'));
        $this->assertNull($client->settings());
        $this->assertNull($client->reviews(['product_id' => 1]));
    }
}
