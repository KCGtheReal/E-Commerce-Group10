<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\BakongService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use KHQR\BakongKHQR;
use Tests\TestCase;

class BakongPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_bakong_khqr_for_order(): void
    {
        $order = Order::factory()->create([
            'total_cents' => 4500,
            'currency' => 'USD',
            'total_charged_currency_amount' => 45.00,
        ]);

        $bakongService = app(BakongService::class);
        $result = $bakongService->generatePayment($order);

        $this->assertInstanceOf(Payment::class, $result['payment']);
        $this->assertNotEmpty($result['qr_string']);
        $this->assertNotEmpty($result['qr_svg']);
        $this->assertEquals(32, strlen($result['md5']));
        $this->assertEquals(45.00, $result['amount']);
        $this->assertEquals('USD', $result['currency']);
        $this->assertEquals(PaymentStatus::Pending, $result['payment']->status);

        // Verify standard KHQR compliance
        $crcValidation = BakongKHQR::verify($result['qr_string']);
        $this->assertTrue($crcValidation->isValid, 'Generated QR string must pass EMVCo KHQR CRC and specification validation.');

        // Verify Tag 99 timestamp template contains both creation (00) and expiration (01)
        $this->assertStringContainsString('99340013', $result['qr_string'], 'Dynamic KHQR must contain Tag 99 with creation and expiration sub-tags.');
        $this->assertStringNotContainsString('#', $result['qr_string'], 'KHQR string must not contain forbidden character #');
    }

    public function test_generated_khqr_in_khr_formats_integer_riels_without_decimals(): void
    {
        $order = Order::factory()->create([
            'currency' => 'KHR',
            'total_charged_currency_amount' => 155800.00,
        ]);

        $bakongService = app(BakongService::class);
        $result = $bakongService->generatePayment($order);

        $this->assertEquals('KHR', $result['currency']);
        $this->assertEquals(155800, $result['amount']);
        $this->assertStringContainsString('5303116', $result['qr_string']); // 116 = KHR
        $this->assertStringContainsString('5406155800', $result['qr_string']); // integer without decimals
        $this->assertTrue(BakongKHQR::verify($result['qr_string'])->isValid);
    }

    public function test_generated_khqr_for_100_khr_order(): void
    {
        $order = Order::factory()->create([
            'currency' => 'KHR',
            'total_charged_currency_amount' => 100.00,
        ]);

        $bakongService = app(BakongService::class);
        $result = $bakongService->generatePayment($order);

        $this->assertEquals('KHR', $result['currency']);
        $this->assertEquals(100, $result['amount']);
        $this->assertStringContainsString('5303116', $result['qr_string']); // 116 = KHR
        $this->assertStringContainsString('5403100', $result['qr_string']); // length 03, value 100
        $this->assertTrue(BakongKHQR::verify($result['qr_string'])->isValid);
    }

    public function test_generated_khqr_for_individual_account_omits_corporate_terminal_labels(): void
    {
        $order = Order::factory()->create([
            'number' => 'RST-INDIV-001',
            'currency' => 'KHR',
            'total_charged_currency_amount' => 50000,
        ]);

        $bakongService = app(BakongService::class);
        $result = $bakongService->generatePayment($order);

        // Individual Tag 29 accounts must not have storeLabel (03) or terminalLabel (07)
        $decoded = BakongKHQR::decode($result['qr_string'])->data;
        $this->assertEquals('29', $decoded['merchantType']);
        $this->assertNull($decoded['terminalLabel']);
        $this->assertNull($decoded['storeLabel']);
        $this->assertEquals('RST-INDIV-001', $decoded['billNumber']);
    }

    public function test_payment_status_endpoint_returns_pending_initially(): void
    {
        $order = Order::factory()->create(['number' => 'RST-TEST-001']);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::Pending,
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
        ]);

        session(['active_order_'.$order->number => true]);

        $response = $this->getJson(route('payment.status', $payment->id));

        $response->assertOk()
            ->assertJson([
                'status' => 'pending',
            ]);
    }

    public function test_simulating_payment_marks_order_and_payment_as_paid(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 2500,
            'stock_quantity' => 10,
            'reserved_quantity' => 2,
        ]);

        $order = Order::factory()->create([
            'number' => 'RST-TEST-PAID',
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

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::Pending,
        ]);

        session(['active_order_'.$order->number => true]);

        $response = $this->postJson(route('payment.simulate', $payment->id));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'redirect_url' => route('payment.success', ['order' => $order->number]),
            ]);

        $order->refresh();
        $payment->refresh();
        $variant->refresh();

        $this->assertEquals(OrderStatus::Paid, $order->status);
        $this->assertEquals(PaymentStatus::Paid, $payment->status);
        $this->assertNotNull($order->paid_at);
        $this->assertEquals(8, $variant->stock_quantity); // 10 - 2 = 8
        $this->assertEquals(0, $variant->reserved_quantity);
    }

    public function test_can_expire_stale_payments_and_restore_inventory(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 2500,
            'stock_quantity' => 10,
            'reserved_quantity' => 2,
        ]);

        $order = Order::factory()->create(['status' => OrderStatus::PendingPayment]);
        $order->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_label' => $variant->label,
            'sku' => $variant->sku,
            'quantity' => 2,
            'price_cents' => 2500,
            'subtotal_cents' => 5000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::Pending,
            'expires_at' => now()->subMinutes(1),
        ]);

        $orderService = app(OrderService::class);
        $orderService->expirePayment($payment);

        $payment->refresh();
        $order->refresh();
        $variant->refresh();

        $this->assertEquals(PaymentStatus::Expired, $payment->status);
        $this->assertEquals(OrderStatus::Expired, $order->status);
        $this->assertEquals(0, $variant->reserved_quantity);
    }

    public function test_can_regenerate_payment_for_expired_order(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 2500,
            'stock_quantity' => 5,
            'reserved_quantity' => 0,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::Expired,
            'number' => 'RST-REGEN-001',
        ]);
        $order->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_label' => $variant->label,
            'sku' => $variant->sku,
            'quantity' => 1,
            'price_cents' => 2500,
            'subtotal_cents' => 2500,
        ]);

        session(['active_order_'.$order->number => true]);

        $response = $this->post(route('payment.regenerate', $order->number));

        $response->assertRedirect(route('payment.show', ['order' => $order->number]));

        $order->refresh();
        $variant->refresh();

        $this->assertEquals(OrderStatus::PendingPayment, $order->status);
        $this->assertEquals(1, $variant->reserved_quantity);
        $this->assertNotNull($order->latestPayment);
        $this->assertEquals(PaymentStatus::Pending, $order->latestPayment->status);
    }

    public function test_payment_page_renders_correct_khr_amount_matching_order_total(): void
    {
        $order = Order::factory()->create([
            'total_cents' => 100,
            'currency' => 'KHR',
            'exchange_rate' => 4100,
            'total_charged_currency_amount' => 100.00,
            'number' => 'RST-TEST-KHR100',
        ]);

        $response = $this->withSession(['active_order_'.$order->number => true])
            ->get(route('payment.show', ['order' => $order->number]));

        $response->assertOk();
        $response->assertSee('100 ៛');
        $response->assertDontSee('4,100 ៛');
    }

    public function test_order_success_page_renders_correct_khr_amount(): void
    {
        $order = Order::factory()->create([
            'total_cents' => 100,
            'currency' => 'KHR',
            'exchange_rate' => 4100,
            'total_charged_currency_amount' => 100.00,
            'status' => OrderStatus::Paid,
            'number' => 'RST-TEST-PAID100',
        ]);

        $response = $this->withSession(['active_order_'.$order->number => true])
            ->get(route('payment.success', ['order' => $order->number]));

        $response->assertOk();
        $response->assertSee('100 ៛');
        $response->assertDontSee('$1.00');
    }

    public function test_payment_status_polling_triggers_check_after_throttle_delay(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PendingPayment]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::Pending,
            'last_checked_at' => now()->subSeconds(5),
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->withSession(['active_order_'.$order->number => true])
            ->get(route('payment.status', $payment->id));

        $response->assertOk();
        $payment->refresh();
        $this->assertTrue(abs(now()->diffInSeconds($payment->last_checked_at)) < 2);
    }

    public function test_payment_status_polling_finalizes_paid_order_even_if_expires_at_has_passed(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PendingPayment]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::Pending,
            'amount' => 100.00,
            'currency' => 'KHR',
            'expires_at' => now()->subSeconds(5), // Expired 5 seconds ago!
        ]);

        // Mock BakongService to report this transaction was actually paid on the gateway
        $mockBakong = $this->createMock(BakongService::class);
        $mockBakong->expects($this->once())
            ->method('checkTransaction')
            ->willReturn([
                'paid' => true,
                'data' => [
                    'responseCode' => 0,
                    'data' => ['hash' => 'test-hash-123', 'amount' => 100, 'currency' => 'KHR'],
                ],
                'error' => null,
            ]);
        $this->app->instance(BakongService::class, $mockBakong);

        $response = $this->withSession(['active_order_'.$order->number => true])
            ->get(route('payment.status', $payment->id));

        $response->assertOk()
            ->assertJson(['status' => 'paid']);

        $payment->refresh();
        $order->refresh();

        $this->assertEquals(PaymentStatus::Paid, $payment->status);
        $this->assertEquals(OrderStatus::Paid, $order->status);
    }
}
