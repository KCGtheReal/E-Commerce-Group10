<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_add_product_to_cart(): void
    {
        $product = Product::factory()->create(['base_price_cents' => 3500]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 3500,
            'stock_quantity' => 10,
        ]);

        // JSON / AJAX request returns 401
        $response = $this->postJson(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
        $response->assertStatus(401);

        // Standard web browser request redirects to login
        $webResponse = $this->post(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
        $webResponse->assertRedirect(route('login'));
    }

    public function test_can_add_product_variant_to_cart(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['base_price_cents' => 3500]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 3500,
            'stock_quantity' => 10,
        ]);

        $response = $this->postJson(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'cart' => [
                    'item_count' => 2,
                    'subtotal_cents' => 7000,
                ],
            ]);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['base_price_cents' => 3000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 3,
            'reserved_quantity' => 0,
        ]);

        $response = $this->postJson(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_can_update_item_quantity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create(['base_price_cents' => 2500]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 2500,
            'stock_quantity' => 10,
        ]);

        $addResponse = $this->postJson(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $itemId = $addResponse->json('cart.items.0.id');

        $response = $this->patchJson(route('cart.update', $itemId), [
            'quantity' => 3,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'cart' => [
                    'item_count' => 3,
                    'subtotal_cents' => 7500,
                ],
            ]);
    }

    public function test_can_remove_item_from_cart(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $addResponse = $this->postJson(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $itemId = $addResponse->json('cart.items.0.id');

        $response = $this->deleteJson(route('cart.remove', $itemId));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'cart' => [
                    'item_count' => 0,
                ],
            ]);
    }

    public function test_can_apply_percentage_discount_code(): void
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
            'code' => 'RESET10',
            'type' => DiscountType::Percentage,
            'value' => 10,
        ]);

        $this->postJson(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response = $this->postJson(route('cart.discount.apply'), [
            'code' => 'RESET10',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'cart' => [
                    'discount_cents' => 1000,
                    'discount_code' => 'RESET10',
                ],
            ]);
    }

    public function test_rejects_invalid_discount_code(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->postJson(route('cart.add'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response = $this->postJson(route('cart.discount.apply'), [
            'code' => 'NONEXISTENT',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_merges_guest_cart_into_user_cart_on_login(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['base_price_cents' => 2000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 2000,
            'stock_quantity' => 10,
        ]);

        // Existing session cart created before session login
        $guestCart = Cart::create([
            'session_id' => session()->getId(),
            'user_id' => null,
        ]);
        $guestCart->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
        session()->put('cart_id', $guestCart->id);

        $cartService = app(CartService::class);
        $cartService->mergeGuestCartToUser($user);

        Auth::login($user);
        $userCart = $cartService->getCart();

        $this->assertEquals($user->id, $userCart->user_id);
        $this->assertEquals(2, $userCart->item_count);
    }
}
