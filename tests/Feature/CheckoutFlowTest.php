<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_checkout_and_is_redirected_to_login(): void
    {
        $response = $this->get(route('checkout.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_submit_checkout_and_is_redirected_to_login(): void
    {
        $response = $this->post(route('checkout.store'), [
            'email' => 'guest@example.com',
            'phone' => '012999888',
            'province' => 'Phnom Penh',
            'street_address' => 'House 42, Street 178',
        ]);
        $response->assertRedirect(route('login'));
    }

    public function test_checkout_page_renders_with_cart_items_and_shipping(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['base_price_cents' => 3000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 3000,
            'stock_quantity' => 10,
        ]);

        $this->post(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response = $this->get(route('checkout.index'));

        $response->assertOk()
            ->assertSee('Order Summary')
            ->assertSee('Standard Delivery');
    }

    public function test_authenticated_user_can_checkout_and_reach_khqr_payment(): void
    {
        $user = User::factory()->create([
            'email' => 'sophea@example.com',
        ]);
        $this->actingAs($user);

        $product = Product::factory()->create(['base_price_cents' => 3000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 3000,
            'stock_quantity' => 10,
        ]);

        $this->post(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response = $this->post(route('checkout.store'), [
            'first_name' => 'Sophea',
            'last_name' => 'Chan',
            'email' => 'sophea@example.com',
            'phone' => '012999888',
            'province' => 'Phnom Penh',
            'district' => 'Daun Penh',
            'commune' => 'Phsar Thmei 1',
            'street_address' => 'House 42, Street 178',
            'shipping_method' => 'standard',
        ]);

        $order = Order::latest()->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('payment.show', ['order' => $order->number]));

        $this->assertEquals(OrderStatus::PendingPayment, $order->status);
        $this->assertEquals(6000, $order->subtotal_cents);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertNotNull($order->latestPayment);

        // Stock reserved
        $variant->refresh();
        $this->assertEquals(2, $variant->reserved_quantity);
        $this->assertEquals(10, $variant->stock_quantity);
    }

    public function test_checkout_validates_required_delivery_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 2500,
            'stock_quantity' => 5,
        ]);

        $this->post(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response = $this->post(route('checkout.store'), [
            'email' => 'invalid-email',
            'province' => 'Phnom Penh',
        ]);

        $response->assertSessionHasErrors(['name', 'phone', 'street_address', 'email']);
    }

    public function test_authenticated_user_can_checkout_with_single_full_name(): void
    {
        $user = User::factory()->create([
            'email' => 'kongchhunyt@gmail.com',
        ]);
        $this->actingAs($user);

        $product = Product::factory()->create(['base_price_cents' => 3000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 3000,
            'stock_quantity' => 10,
        ]);

        $this->post(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response = $this->post(route('checkout.store'), [
            'name' => 'KONGCHHUN',
            'email' => 'kongchhunyt@gmail.com',
            'phone' => '012999888',
            'province' => 'Phnom Penh',
            'street_address' => 'House 42, Street 178',
            'shipping_method' => 'standard',
        ]);

        $order = Order::latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('KONGCHHUN', $order->shipping_address['name']);
        $this->assertEquals('KONGCHHUN', $order->shipping_address['first_name']);
        $this->assertEquals($user->id, $order->user_id);
        $response->assertRedirect(route('payment.show', ['order' => $order->number]));
    }

    public function test_order_settlement_is_idempotent(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 2500,
            'stock_quantity' => 10,
            'reserved_quantity' => 2,
        ]);

        $order = Order::factory()->create([
            'number' => 'RST-IDEMP-001',
            'status' => OrderStatus::PendingPayment,
        ]);

        $order->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_label' => $variant->label,
            'sku' => $variant->sku,
            'quantity' => 2,
            'price_cents' => 2500,
            'subtotal_cents' => 5000,
        ]);

        $payment = $order->payments()->create([
            'provider' => 'bakong_khqr',
            'md5' => md5('test_idemp'),
            'qr_string' => 'mock_qr',
            'account_id' => 'reset_store@devb',
            'amount' => 50.00,
            'currency' => 'USD',
            'status' => PaymentStatus::Pending,
            'expires_at' => now()->addMinutes(5),
        ]);

        $orderService = app(OrderService::class);

        // First call
        $orderService->finalizePaidOrder($payment, ['tx_hash' => 'ABC1234']);
        $variant->refresh();
        $this->assertEquals(8, $variant->stock_quantity); // 10 - 2 = 8
        $this->assertEquals(0, $variant->reserved_quantity);

        // Second call with same payment
        $orderService->finalizePaidOrder($payment, ['tx_hash' => 'ABC1234']);
        $variant->refresh();
        $this->assertEquals(8, $variant->stock_quantity); // Still 8, NOT decremented again!
        $this->assertEquals(0, $variant->reserved_quantity);
    }

    public function test_standard_delivery_fee_is_zero_riel(): void
    {
        $totals = app(CartService::class)->calculateTotals('Phnom Penh');
        $this->assertEquals(0, $totals['shipping_fee_cents']);
        $this->assertEquals('0 ៛', $totals['formatted_shipping']);
    }

    public function test_checkout_order_summary_displays_promo_code_section(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['base_price_cents' => 3000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 3000,
            'stock_quantity' => 10,
        ]);

        $this->post(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response = $this->get(route('checkout.index'));

        $response->assertOk()
            ->assertSee('checkout_promo_code')
            ->assertSee('applyPromo()');
    }

    public function test_checkout_reflects_applied_promo_code_discount(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['base_price_cents' => 10000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 10000,
            'stock_quantity' => 10,
        ]);

        Discount::factory()->create([
            'code' => 'RESET20',
            'type' => DiscountType::Percentage,
            'value' => 20,
            'is_active' => true,
        ]);

        $this->post(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->postJson(route('cart.discount.apply'), [
            'code' => 'RESET20',
        ])->assertOk();

        $response = $this->get(route('checkout.index'));

        $response->assertOk()
            ->assertSee('RESET20')
            ->assertSee('removePromo()');
    }
}
