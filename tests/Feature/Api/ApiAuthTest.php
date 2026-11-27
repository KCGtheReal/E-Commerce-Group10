<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\ApiToken;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_customer_stores_hashed_token_and_merges_guest_cart(): void
    {
        $product = Product::factory()->create(['base_price_cents' => 120000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 120000,
            'stock_quantity' => 8,
        ]);

        $guestCart = Cart::create([
            'session_id' => 'guest-cart-reg-1',
            'user_id' => null,
        ]);
        $guestCart->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response = $this->withHeaders([
            'X-Cart-Token' => 'guest-cart-reg-1',
        ])->postJson('/api/v1/auth/register', [
            'name' => 'Sophea Chan',
            'email' => '  SOPHEA@Reset.Store ',
            'phone' => '012345678',
            'password' => 'SecretPass123!',
            'password_confirmation' => 'SecretPass123!',
            'device_name' => 'pixel-8',
        ]);

        $response->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'sophea@reset.store')
            ->assertJsonPath('user.role', UserRole::Customer->value)
            ->assertJsonPath('cart.item_count', 2)
            ->assertJsonPath('cart.cart_token', null);

        $plainToken = (string) $response->json('token');
        $this->assertStringStartsWith('rst_', $plainToken);

        $user = User::where('email', 'sophea@reset.store')->firstOrFail();
        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $user->id,
            'name' => 'pixel-8',
            'token_hash' => hash('sha256', $plainToken),
        ]);
        $this->assertDatabaseMissing('carts', ['id' => $guestCart->id]);
    }

    public function test_login_merges_guest_cart_and_returns_bearer_token(): void
    {
        $user = User::factory()->create([
            'email' => 'member@reset.store',
            'password' => Hash::make('password123'),
            'role' => UserRole::Customer,
        ]);

        $product = Product::factory()->create(['base_price_cents' => 100000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_cents' => 100000,
            'stock_quantity' => 5,
        ]);

        $discount = Discount::factory()->create([
            'code' => 'RESET10',
            'value' => 10,
            'min_spend_cents' => 50000,
            'is_active' => true,
        ]);

        $guestCart = Cart::create([
            'session_id' => 'guest-cart-login-1',
            'user_id' => null,
            'discount_id' => $discount->id,
        ]);
        $guestCart->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response = $this->withHeaders([
            'X-Cart-Token' => 'guest-cart-login-1',
        ])->postJson('/api/v1/auth/login', [
            'email' => 'MEMBER@reset.store',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('cart.item_count', 2)
            ->assertJsonPath('cart.discount_code', 'RESET10');

        $token = (string) $response->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'member@reset.store');
    }

    public function test_protected_endpoints_reject_missing_expired_and_logged_out_tokens(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');

        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $expired = ApiToken::create([
            'user_id' => $user->id,
            'name' => 'expired',
            'token_hash' => hash('sha256', 'rst_expired_token'),
            'expires_at' => now()->subMinute(),
        ]);
        $this->assertTrue($expired->isExpired());

        $this->withToken('rst_expired_token')
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();

        $issued = ApiToken::issueFor($user);
        $token = $issued['plainTextToken'];

        $this->withToken($token)
            ->putJson('/api/v1/auth/password', [
                'current_password' => 'old-password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('NewPassword123!', (string) $user->fresh()?->password));

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_invalid_login_and_forgot_password_endpoints(): void
    {
        User::factory()->create([
            'email' => 'buyer@reset.store',
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'buyer@reset.store',
            'password' => 'wrong-password',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'buyer@reset.store',
        ])->assertOk()
            ->assertJsonStructure(['message', 'status']);
    }
}
