<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Mail\OrderStatusUpdatedMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_can_access_admin_panel(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'admin@reset.store',
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin');

        $response->assertOk()
            ->assertSee('logo.svg')
            ->assertSee('logo-white.svg');
    }

    public function test_customer_user_is_forbidden_from_admin_panel(): void
    {
        $customer = User::factory()->create([
            'role' => UserRole::Customer,
            'email' => 'customer@reset.store',
        ]);

        $response = $this->actingAs($customer, 'admin')->get('/admin');

        $response->assertForbidden();
    }

    public function test_customer_logged_into_web_is_redirected_to_admin_login(): void
    {
        $customer = User::factory()->create([
            'role' => UserRole::Customer,
            'email' => 'customer@reset.store',
        ]);

        // Customer session on storefront (web guard) visiting /admin is isolated
        // and redirected to /admin/login rather than encountering a 403 collision
        $response = $this->actingAs($customer, 'web')->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_customer_does_not_see_admin_panel_link_in_navigation(): void
    {
        $customer = User::factory()->create([
            'role' => UserRole::Customer,
            'name' => 'rathana kongchhun',
            'email' => 'customer@reset.store',
        ]);

        $response = $this->actingAs($customer, 'web')->get(route('home'));

        $response->assertOk()
            ->assertSee('rathana kongchhun')
            ->assertDontSeeText(__('store.admin_panel'));
    }

    public function test_customer_login_clears_existing_admin_session(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'admin@reset.store',
        ]);

        $customer = User::factory()->create([
            'role' => UserRole::Customer,
            'email' => 'customer@reset.store',
            'password' => bcrypt('password123'),
        ]);

        // Simulate admin authenticated in admin guard
        auth()->guard('admin')->login($admin);
        $this->assertTrue(auth()->guard('admin')->check());

        // Now customer logs in via storefront
        $response = $this->post('/login', [
            'email' => 'customer@reset.store',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertFalse(auth()->guard('admin')->check());
    }

    public function test_admin_login_page_renders_redesigned_store_theme(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk()
            ->assertSee('RESET STUDIO • ADMIN PORTAL')
            ->assertSee('ADMIN SIGN IN')
            ->assertSee('SIGN IN TO PORTAL →')
            ->assertSee('Return to Storefront');
    }

    public function test_admin_can_login_via_post_request(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'admin@reset.store',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'data' => [
                'email' => 'admin@reset.store',
                'password' => 'password123',
            ],
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_customer_cannot_login_to_admin_via_post_request(): void
    {
        User::factory()->create([
            'role' => UserRole::Customer,
            'email' => 'customer@reset.store',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'data' => [
                'email' => 'customer@reset.store',
                'password' => 'password123',
            ],
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_admin_can_view_orders_resource(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/orders');

        $response->assertOk();
    }

    public function test_admin_can_view_products_resource(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/products');

        $response->assertOk();
    }

    public function test_admin_can_view_payments_resource(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/payments');

        $response->assertOk();
    }

    public function test_admin_can_view_paginated_products_without_intl_error(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $category = Category::factory()->create();
        Product::factory()->count(25)->create(['category_id' => $category->id]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/products');

        $response->assertOk();
    }

    public function test_admin_can_view_categories_resource(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        Category::factory()->count(5)->create();

        $response = $this->actingAs($admin, 'admin')->get('/admin/categories');

        $response->assertOk();
    }

    public function test_admin_can_view_users_resource(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        User::factory()->count(3)->create(['role' => UserRole::Customer]);
        User::factory()->create(['role' => UserRole::Staff]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/users');

        $response->assertOk();
    }

    public function test_admin_can_view_all_other_resources(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin, 'admin')->get('/admin/discounts')->assertOk();
        $this->actingAs($admin, 'admin')->get('/admin/reviews')->assertOk();
        $this->actingAs($admin, 'admin')->get('/admin/settings')->assertOk();
        $this->actingAs($admin, 'admin')->get('/admin/contact-messages')->assertOk();
    }

    public function test_admin_dashboard_renders_redesigned_widgets(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'name' => 'Sokha Admin',
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin');

        $response->assertOk()
            ->assertSee('Welcome back, Sokha Admin')
            ->assertSee('Total Settled Revenue')
            ->assertSee('Daily Settled Revenue')
            ->assertSee('Orders by Fulfillment Status')
            ->assertSee('Recent Orders & Quick Fulfillment')
            ->assertSee('Critical Inventory & Low Stock Alerts');
    }

    public function test_admin_sees_product_price_formatted_in_khr(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'RESET Raw Edge Heavyweight Tee',
            'base_price_cents' => 100,
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/products');

        $response->assertOk()
            ->assertSee('100 ៛');
    }

    public function test_order_status_updated_mail_generates_default_and_custom_messages(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Packed,
            'number' => 'RESET-TEST-9999',
            'email' => 'customer@example.com',
        ]);

        // Default message based on status
        $defaultMail = new OrderStatusUpdatedMail($order);
        $this->assertStringContainsString('packaged', $defaultMail->statusMessage);
        $this->assertEquals('Update on your Order #RESET-TEST-9999 - RESET Store', $defaultMail->envelope()->subject);

        // Custom message override
        $customMail = new OrderStatusUpdatedMail($order, 'Your garment has been tagged and dispatched via Virak Buntham.');
        $this->assertEquals('Your garment has been tagged and dispatched via Virak Buntham.', $customMail->statusMessage);
    }
}
