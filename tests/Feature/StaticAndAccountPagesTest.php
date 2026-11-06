<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticAndAccountPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_static_pages_load_successfully(): void
    {
        $pages = [
            route('page.about'),
            route('page.faq'),
            route('page.shipping'),
            route('page.privacy'),
            route('page.terms'),
            route('page.size-guide'),
            route('page.contact'),
        ];

        foreach ($pages as $url) {
            $response = $this->get($url);
            $response->assertOk();
        }
    }

    public function test_customer_account_pages_render_without_errors(): void
    {
        $customer = User::factory()->create();

        $accountRoutes = [
            route('account.profile'),
            route('account.orders'),
            route('account.addresses'),
            route('wishlist.index'),
        ];

        foreach ($accountRoutes as $url) {
            $response = $this->actingAs($customer)->get($url);
            $response->assertOk();
        }
    }

    public function test_order_detail_and_invoice_pages_render(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'number' => 'RST-20260921-9999',
        ]);

        $response = $this->actingAs($customer)->get(route('account.orders.show', $order->number));
        $response->assertOk();

        $invoiceResponse = $this->actingAs($customer)->get(route('order.invoice', $order->number));
        $invoiceResponse->assertOk();
    }

    public function test_customer_can_login_and_navigate_account(): void
    {
        $customer = User::factory()->create([
            'email' => 'testcustomer@reset.store',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'testcustomer@reset.store',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($customer);

        $profileResponse = $this->get(route('account.profile'));
        $profileResponse->assertOk();
    }

    public function test_pages_render_in_khmer_locale(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'number' => 'RST-KM-TEST',
        ]);

        $publicPages = [
            route('home'),
            route('shop.index'),
            route('page.about'),
            route('page.faq'),
            route('page.shipping'),
            route('page.privacy'),
            route('page.terms'),
            route('page.size-guide'),
            route('page.contact'),
            route('cart.index'),
            route('login'),
            route('register'),
        ];

        foreach ($publicPages as $url) {
            $response = $this->withSession(['locale' => 'km'])->get($url);
            $response->assertOk();
        }

        $authPages = [
            route('account.profile'),
            route('account.orders'),
            route('account.addresses'),
            route('wishlist.index'),
            route('account.orders.show', $order->number),
            route('order.invoice', $order->number),
        ];

        foreach ($authPages as $url) {
            $response = $this->actingAs($customer)
                ->withSession(['locale' => 'km'])
                ->get($url);
            $response->assertOk();
        }
    }
}
