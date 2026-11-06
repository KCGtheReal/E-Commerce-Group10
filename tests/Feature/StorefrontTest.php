<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads_successfully(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(4)->create(['category_id' => $category->id]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('RESET')
            ->assertSee('Start fresh.');
    }

    public function test_shop_page_loads_with_filters(): void
    {
        $category = Category::factory()->create(['name' => 'Tees']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Architectural Heavyweight Tee',
            'base_price_cents' => 3200,
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 10]);

        $response = $this->get(route('shop.index', ['category' => $category->slug]));

        $response->assertOk()
            ->assertSee('Architectural Heavyweight Tee');
    }

    public function test_product_detail_page_loads(): void
    {
        $product = Product::factory()->create(['slug' => 'box-tee']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'size' => 'L', 'color' => 'Onyx Black']);

        $response = $this->get(route('product.show', 'box-tee'));

        $response->assertOk()
            ->assertSee($product->name)
            ->assertSee('Onyx Black');
    }

    public function test_language_switcher_updates_locale(): void
    {
        $response = $this->get(route('locale.set', 'km'));

        $response->assertRedirect();
        $this->assertEquals('km', session('locale'));

        // Visit home in Khmer
        $homeResponse = $this->withSession(['locale' => 'km'])->get(route('home'));
        $homeResponse->assertOk()
            ->assertSee('ចាប់ផ្តើមថ្មី។');
    }

    public function test_default_currency_is_khr(): void
    {
        $this->assertEquals('KHR', CurrencyService::current());
    }

    public function test_currency_switcher_updates_currency(): void
    {
        $response = $this->get(route('currency.set', 'USD'));

        $response->assertRedirect();
        $this->assertEquals('USD', session('currency'));

        $response = $this->get(route('currency.set', 'KHR'));

        $response->assertRedirect();
        $this->assertEquals('KHR', session('currency'));
    }

    public function test_wishlist_toggle_functionality(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Guest is prompted to log in
        $guestResponse = $this->postJson(route('wishlist.toggle'), ['product_id' => $product->id]);
        $guestResponse->assertStatus(401);

        // Authenticated user adds to wishlist
        $authResponse = $this->actingAs($user)->postJson(route('wishlist.toggle'), ['product_id' => $product->id]);
        $authResponse->assertOk()->assertJson(['added' => true]);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        // Toggle again removes from wishlist
        $authResponse2 = $this->actingAs($user)->postJson(route('wishlist.toggle'), ['product_id' => $product->id]);
        $authResponse2->assertOk()->assertJson(['added' => false]);

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_contact_form_submission(): void
    {
        $response = $this->post(route('page.contact.submit'), [
            'name' => 'Dara Sam',
            'email' => 'dara@example.com',
            'subject' => 'Sizing Question',
            'message' => 'Hello team, what size fits 175cm height and 70kg weight best?',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Dara Sam',
            'email' => 'dara@example.com',
        ]);
    }

    public function test_newsletter_subscription(): void
    {
        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'subscriber@example.com',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'subscriber@example.com',
            'is_active' => true,
        ]);
    }

    public function test_sitemap_returns_xml(): void
    {
        Product::factory()->count(2)->create(['is_published' => true]);

        $response = $this->get(route('sitemap'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml');
    }

    public function test_khr_price_formatting_and_usd_conversion(): void
    {
        $product = Product::factory()->create([
            'name' => 'RESET Raw Edge Heavyweight Tee',
            'base_price_cents' => 100,
        ]);

        // In KHR (default)
        $this->assertEquals('100 ៛', CurrencyService::formatCents($product->base_price_cents, 'KHR'));
        $this->assertEquals(100.0, CurrencyService::convertCents($product->base_price_cents, 'KHR'));

        // In USD
        $this->assertEquals('$0.02', CurrencyService::formatCents($product->base_price_cents, 'USD'));
        $this->assertEquals(0.02, CurrencyService::convertCents($product->base_price_cents, 'USD'));
    }

    public function test_category_navigation_highlights_active_category_tab(): void
    {
        $category = Category::factory()->create([
            'name' => 'Hoodies & Outerwear',
            'slug' => 'hoodies-and-outerwear',
        ]);

        $response = $this->get(route('shop.index', ['category' => 'hoodies-and-outerwear']));

        $response->assertOk();
        // The page header and breadcrumb should show the category name
        $response->assertSee('Hoodies &amp; Outerwear', false);
        // Active filter badge
        $response->assertSee('Active filter:');
        // The nav links should highlight the active outerwear tab
        $content = $response->getContent();
        $this->assertStringContainsString('category=hoodies-and-outerwear" class="transition relative py-1 text-brand-accent font-bold after:absolute', $content);
    }

    public function test_discount_does_not_render_with_double_minus(): void
    {
        $order = Order::factory()->create([
            'subtotal_cents' => 200,
            'discount_cents' => 100,
            'discount_code' => 'RESET10',
            'shipping_fee_cents' => 0,
            'total_cents' => 100,
            'currency' => 'KHR',
        ]);

        $response = $this->withSession(['active_order_'.$order->number => true])
            ->get(route('payment.success', $order->number));

        $response->assertOk();
        $response->assertDontSee('--100');
        $response->assertSee('-100 ៛');
    }

    public function test_search_suggest_api_returns_popular_and_matching_results(): void
    {
        $category = Category::factory()->create(['name' => 'Tees & Tops', 'slug' => 'tees-and-tops']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Architectural Boxy Heavyweight Tee',
            'is_published' => true,
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 10]);

        // Empty query: returns popular searches and categories
        $emptyResponse = $this->getJson(route('search.suggest'));
        $emptyResponse->assertOk()
            ->assertJsonStructure(['query', 'popular_searches', 'categories']);

        // Query with matching keyword
        $queryResponse = $this->getJson(route('search.suggest', ['q' => 'boxy']));
        $queryResponse->assertOk()
            ->assertJsonFragment(['name' => 'Architectural Boxy Heavyweight Tee'])
            ->assertJsonPath('total', 1);
    }

    public function test_multi_term_search_matches_across_name_and_variant_attributes(): void
    {
        $category = Category::factory()->create(['name' => 'Hoodies & Outerwear']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Minimalist Heavyweight Zip Hoodie',
            'is_published' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'color' => 'Onyx Black',
            'stock_quantity' => 5,
        ]);

        // Search with words from title AND variant color: "black hoodie"
        $response = $this->get(route('shop.index', ['q' => 'black hoodie']));

        $response->assertOk()
            ->assertSee('Minimalist Heavyweight Zip Hoodie')
            ->assertSee('Search: "black hoodie"', false);
    }
}
