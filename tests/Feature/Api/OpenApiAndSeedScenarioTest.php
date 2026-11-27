<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OpenApiAndSeedScenarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_spec_and_swagger_ui_routes_are_valid(): void
    {
        $specRes = $this->getJson('/api/openapi.json');
        $specRes->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonPath('info.title', 'RESET Store REST API');

        $paths = array_keys((array) $specRes->json('paths'));
        $expectedPaths = [
            '/api/v1/bootstrap',
            '/api/v1/home',
            '/api/v1/products',
            '/api/v1/search/suggest',
            '/api/v1/products/{slug}',
            '/api/v1/products/{slug}/reviews',
            '/api/v1/wishlist',
            '/api/v1/wishlist/toggle',
            '/api/v1/cart',
            '/api/v1/cart/items',
            '/api/v1/cart/items/{item}',
            '/api/v1/cart/discount',
            '/api/v1/checkout',
            '/api/v1/orders/{orderNumber}/payment',
            '/api/v1/payments/{payment}/status',
            '/api/v1/orders/{orderNumber}/regenerate-qr',
            '/api/v1/payments/{payment}/simulate-pay',
            '/api/v1/orders/{orderNumber}/invoice',
            '/api/v1/auth/register',
            '/api/v1/auth/login',
            '/api/v1/auth/logout',
            '/api/v1/auth/forgot-password',
            '/api/v1/auth/me',
            '/api/v1/auth/password',
            '/api/v1/account/profile',
            '/api/v1/account/orders',
            '/api/v1/account/orders/{orderNumber}',
            '/api/v1/account/addresses',
            '/api/v1/account/addresses/{address}',
            '/api/v1/pages/{slug}',
            '/api/v1/contact',
            '/api/v1/newsletter',
        ];

        foreach ($expectedPaths as $expectedPath) {
            $this->assertContains($expectedPath, $paths, "Missing OpenAPI path: {$expectedPath}");
        }

        $docsRes = $this->get('/api/docs');
        $docsRes->assertOk()
            ->assertSee('SwaggerUIBundle')
            ->assertSee('/api/openapi.json');

        $this->artisan('app:export-openapi')->assertSuccessful();
        $this->assertFileExists(base_path('openapi.json'));
        $this->assertFileExists(public_path('openapi.json'));
    }

    public function test_tri_scenario_seed_clean_demo_stress_in_memory(): void
    {
        $this->artisan('app:seed-scenario', ['scenario' => 'clean'])
            ->assertSuccessful();
        $this->assertSame(0, Category::count());
        $this->assertSame(0, Product::count());

        // Verify empty-state API still works cleanly with 0 records
        $this->getJson('/api/v1/bootstrap')->assertOk()->assertJsonCount(0, 'categories');
        $this->getJson('/api/v1/home')->assertOk()->assertJsonCount(0, 'featured_products');

        $this->artisan('app:seed-scenario', ['scenario' => 'demo'])
            ->assertSuccessful();
        $this->assertSame(4, Category::count());
        $this->assertSame(24, Product::count());

        $this->artisan('app:seed-scenario', ['scenario' => 'stress'])
            ->assertSuccessful();
        $this->assertGreaterThanOrEqual(520, Product::count());
        $this->assertDatabaseHas('products', ['slug' => 'stress-long-unbroken-string-tee']);
        $this->assertDatabaseHas('products', ['slug' => 'stress-adversarial-xss-unicode-tee']);
    }

    public function test_seed_scenario_refuses_destructive_run_on_live_database_outside_testing(): void
    {
        $originalEnv = $this->app['env'];
        $originalDb = config('database.connections.sqlite.database');

        try {
            $this->app['env'] = 'local';
            config(['database.connections.sqlite.database' => database_path('database.sqlite')]);

            $exitCode = Artisan::call('app:seed-scenario', ['scenario' => 'clean']);
            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('Refusing destructive scenario seeding', Artisan::output());
        } finally {
            $this->app['env'] = $originalEnv;
            config(['database.connections.sqlite.database' => $originalDb]);
        }
    }
}
