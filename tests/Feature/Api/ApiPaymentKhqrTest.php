<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\BakongService;
use App\Services\OrderAccessTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPaymentKhqrTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_token_isolates_payment_simulation_regeneration_and_invoice(): void
    {
        $product = Product::factory()->create(['base_price_cents' => 100000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 100000,
            'stock_quantity' => 10,
            'reserved_quantity' => 1,
        ]);

        $order = Order::factory()->create([
            'number' => 'RST-API-PAY-1',
            'email' => 'guest@reset.store',
            'subtotal_cents' => 100000,
            'total_cents' => 100000,
            'currency' => 'KHR',
            'exchange_rate' => 4100,
            'total_charged_currency_amount' => 100000.00,
            'status' => OrderStatus::PendingPayment,
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

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 100000.00,
            'currency' => 'KHR',
            'status' => PaymentStatus::Pending,
            'expires_at' => now()->addMinutes(5),
        ]);

        $orderToken = app(OrderAccessTokenService::class)->issue($order);

        // Unauthorized without token
        $this->getJson("/api/v1/orders/{$order->number}/payment")->assertForbidden();
        $this->getJson("/api/v1/payments/{$payment->id}/status")->assertForbidden();
        $this->postJson("/api/v1/payments/{$payment->id}/simulate-pay")->assertForbidden();
        $this->getJson("/api/v1/orders/{$order->number}/invoice")->assertForbidden();
        $this->get("/orders/{$order->number}/invoice")->assertForbidden();

        // Tampered token rejected
        $this->withHeaders(['X-Order-Token' => $orderToken.'tampered'])
            ->getJson("/api/v1/orders/{$order->number}/payment")
            ->assertForbidden();

        // GET payment returns existing row without mutating stock or creating new payment
        $this->withHeaders(['X-Order-Token' => $orderToken])
            ->getJson("/api/v1/orders/{$order->number}/payment")
            ->assertOk()
            ->assertJsonPath('payment.id', $payment->id);
        $this->assertEquals(1, $order->payments()->count());

        // Mock guard: 403 when disabled
        config(['bakong.mock' => false]);
        $this->withHeaders(['X-Order-Token' => $orderToken])
            ->postJson("/api/v1/payments/{$payment->id}/simulate-pay")
            ->assertForbidden()
            ->assertJsonPath('code', 'MOCK_PAYMENT_DISABLED');

        // Mock enabled: simulates payment and settles order
        config(['bakong.mock' => true]);
        $this->withHeaders(['X-Order-Token' => $orderToken])
            ->postJson("/api/v1/payments/{$payment->id}/simulate-pay")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('order.status', OrderStatus::Paid->value)
            ->assertJsonPath('payment.status', PaymentStatus::Paid->value);

        $variant->refresh();
        $this->assertEquals(9, $variant->stock_quantity);
        $this->assertEquals(0, $variant->reserved_quantity);

        // JSON invoice & signed Blade web invoice both work with order token
        $this->withHeaders(['X-Order-Token' => $orderToken])
            ->getJson("/api/v1/orders/{$order->number}/invoice")
            ->assertOk()
            ->assertJsonPath('invoice.invoice_number', "INV-{$order->number}");

        $this->get("/orders/{$order->number}/invoice?order_token=".urlencode($orderToken))
            ->assertOk()
            ->assertSee($order->number);
    }

    public function test_expired_payment_releases_stock_and_can_regenerate_qr(): void
    {
        $product = Product::factory()->create(['base_price_cents' => 80000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 80000,
            'stock_quantity' => 5,
            'reserved_quantity' => 2,
        ]);

        $order = Order::factory()->create([
            'number' => 'RST-API-EXP-1',
            'email' => 'exp@reset.store',
            'total_cents' => 160000,
            'currency' => 'KHR',
            'total_charged_currency_amount' => 160000.00,
            'status' => OrderStatus::PendingPayment,
        ]);

        $order->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_label' => $variant->label,
            'sku' => $variant->sku,
            'price_cents' => 80000,
            'quantity' => 2,
            'subtotal_cents' => 160000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 160000.00,
            'currency' => 'KHR',
            'status' => PaymentStatus::Pending,
            'expires_at' => now()->subSecond(),
        ]);

        $mockBakong = $this->createMock(BakongService::class);
        $mockBakong->method('checkTransaction')->willReturn([
            'paid' => false,
            'data' => null,
            'error' => null,
        ]);
        $mockBakong->method('generatePayment')->willReturnCallback(static fn () => [
            'payment' => Payment::factory()->create([
                'order_id' => $order->id,
                'amount' => 160000.00,
                'currency' => 'KHR',
                'status' => PaymentStatus::Pending,
                'expires_at' => now()->addMinutes(5),
            ]),
            'qr_svg' => '<svg/>',
            'qr_string' => '000201010212',
            'md5' => md5('regen'.microtime()),
            'amount' => 160000.0,
            'currency' => 'KHR',
        ]);
        $this->app->instance(BakongService::class, $mockBakong);

        $orderToken = app(OrderAccessTokenService::class)->issue($order);

        $this->withHeaders(['X-Order-Token' => $orderToken])
            ->getJson("/api/v1/payments/{$payment->id}/status")
            ->assertOk()
            ->assertJsonPath('status', 'expired')
            ->assertJsonPath('order_status', 'expired');

        $variant->refresh();
        $this->assertEquals(0, $variant->reserved_quantity);

        $this->withHeaders(['X-Order-Token' => $orderToken])
            ->postJson("/api/v1/orders/{$order->number}/regenerate-qr")
            ->assertOk()
            ->assertJsonPath('order.status', 'pending_payment')
            ->assertJsonPath('payment.status', 'pending');

        $variant->refresh();
        $this->assertEquals(2, $variant->reserved_quantity);
    }
}
