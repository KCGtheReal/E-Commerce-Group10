<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCartCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cart_supports_crud_stock_guard_and_promo_codes(): void
    {
        $product = Product::factory()->create(['base_price_cents' => 100000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 100000,
            'stock_quantity' => 3,
        ]);

        Discount::factory()->create([
            'code' => 'RESET10',
            'type' => DiscountType::Percentage,
            'value' => 10,
            'min_spend_cents' => 100000,
            'is_active' => true,
        ]);

        $token = 'guest-cart-api-test';

        $addRes = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/v1/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $addRes->assertOk()
            ->assertJsonPath('cart.cart_token', $token)
            ->assertJsonPath('cart.item_count', 2)
            ->assertJsonPath('cart.subtotal_cents', 200000);

        $itemId = $addRes->json('cart.items.0.id');

        // Stock limit guard
        $this->withHeaders(['X-Cart-Token' => $token])
            ->patchJson("/api/v1/cart/items/{$itemId}", ['quantity' => 4])
            ->assertStatus(422)
            ->assertJsonPath('code', 'INSUFFICIENT_STOCK');

        // Apply RESET10
        $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/v1/cart/discount', ['code' => 'reset10'])
            ->assertOk()
            ->assertJsonPath('cart.discount_code', 'RESET10')
            ->assertJsonPath('cart.discount_cents', 20000);

        // Invalid discount
        $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/v1/cart/discount', ['code' => 'BOGUS'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_DISCOUNT');

        // Remove discount & remove item
        $this->withHeaders(['X-Cart-Token' => $token])
            ->deleteJson('/api/v1/cart/discount')
            ->assertOk()
            ->assertJsonPath('cart.discount_cents', 0);

        $this->withHeaders(['X-Cart-Token' => $token])
            ->deleteJson("/api/v1/cart/items/{$itemId}")
            ->assertOk()
            ->assertJsonPath('cart.item_count', 0);
    }

    public function test_express_shipping_parity_and_checkout_reservation(): void
    {
        $product = Product::factory()->create(['base_price_cents' => 164000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 164000,
            'stock_quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $cartToken = 'guest-checkout-express';

        $this->withHeaders(['X-Cart-Token' => $cartToken])
            ->postJson('/api/v1/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
                'province' => 'Phnom Penh',
                'shipping_method' => 'express',
            ])
            ->assertOk()
            ->assertJsonPath('cart.shipping_method', 'express')
            ->assertJsonPath('cart.shipping_fee_cents', CartService::EXPRESS_SHIPPING_FEE_KHR)
            ->assertJsonPath('cart.total_cents', 176000);

        // Cart preview auto-resets express -> standard outside Phnom Penh
        $this->withHeaders(['X-Cart-Token' => $cartToken])
            ->getJson('/api/v1/cart?province=Siem%20Reap&shipping_method=express')
            ->assertOk()
            ->assertJsonPath('cart.shipping_method', 'standard')
            ->assertJsonPath('cart.shipping_fee_cents', 0);

        // Checkout strictly rejects non-Phnom Penh express with 422
        $this->withHeaders(['X-Cart-Token' => $cartToken])
            ->postJson('/api/v1/checkout', [
                'name' => 'Guest Buyer',
                'email' => 'guest@reset.store',
                'phone' => '012345678',
                'province' => 'Siem Reap',
                'street_address' => 'Pub Street',
                'shipping_method' => 'express',
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['shipping_method']]);

        // Valid Phnom Penh express checkout succeeds
        $checkout = $this->withHeaders(['X-Cart-Token' => $cartToken])
            ->postJson('/api/v1/checkout', [
                'name' => 'Guest Buyer',
                'email' => 'guest@reset.store',
                'phone' => '012345678',
                'province' => 'Phnom Penh',
                'district' => 'Daun Penh',
                'street_address' => 'Street 178',
                'shipping_method' => 'express',
            ]);

        $checkout->assertCreated()
            ->assertJsonPath('order.shipping_method', 'express')
            ->assertJsonPath('order.shipping_fee_cents', 12000)
            ->assertJsonPath('order.total_cents', 176000)
            ->assertJsonPath('payment.status', 'pending')
            ->assertJsonPath('payment.khr_amount', 176000);

        $this->assertNotEmpty($checkout->json('order_access_token'));

        $variant->refresh();
        $this->assertEquals(1, $variant->reserved_quantity);
        $this->assertEquals(10, $variant->stock_quantity);
    }
}
