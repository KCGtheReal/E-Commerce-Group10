<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\ApiToken;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAccountPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_profile_orders_and_addresses_crud_with_isolation(): void
    {
        $user = User::factory()->create(['name' => 'Dara Sok', 'email' => 'dara@reset.store']);
        $otherUser = User::factory()->create(['email' => 'other@reset.store']);
        $token = ApiToken::issueFor($user)['plainTextToken'];

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'number' => 'RST-ACC-001',
            'status' => OrderStatus::Shipped,
            'shipping_carrier' => 'J&T Express',
            'tracking_number' => 'JT123456KH',
            'shipped_at' => now(),
        ]);

        $otherOrder = Order::factory()->create([
            'user_id' => $otherUser->id,
            'number' => 'RST-ACC-999',
        ]);

        // Profile fetch & update (email remains immutable)
        $this->withToken($token)
            ->getJson('/api/v1/account/profile')
            ->assertOk()
            ->assertJsonPath('user.email', 'dara@reset.store');

        $this->withToken($token)
            ->patchJson('/api/v1/account/profile', [
                'name' => 'Dara Updated',
                'phone' => '012999111',
                'email' => 'hacker@evil.com',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Dara Updated')
            ->assertJsonPath('user.email', 'dara@reset.store');

        // Orders list & detail timeline
        $this->withToken($token)
            ->getJson('/api/v1/account/orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.number', 'RST-ACC-001');

        $this->withToken($token)
            ->getJson('/api/v1/account/orders/RST-ACC-001')
            ->assertOk()
            ->assertJsonPath('order.tracking_number', 'JT123456KH')
            ->assertJsonCount(5, 'order.status_timeline')
            ->assertJsonPath('order.status_timeline.3.active', true)
            ->assertJsonStructure(['order', 'order_access_token']);

        // Cannot read another user's order
        $this->withToken($token)
            ->getJson("/api/v1/account/orders/{$otherOrder->number}")
            ->assertNotFound();

        // Addresses CRUD & single is_default invariant
        $addr1Res = $this->withToken($token)
            ->postJson('/api/v1/account/addresses', [
                'name' => 'Dara Sok',
                'phone' => '012345678',
                'province' => 'Phnom Penh',
                'street_address' => 'St 178',
                'is_default' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('address.is_default', true);

        $addr1Id = $addr1Res->json('address.id');

        $addr2Res = $this->withToken($token)
            ->postJson('/api/v1/account/addresses', [
                'name' => 'Dara Office',
                'phone' => '012345679',
                'province' => 'Siem Reap',
                'street_address' => 'Sivutha Blvd',
                'is_default' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('address.is_default', true);

        $addr2Id = $addr2Res->json('address.id');

        $this->assertFalse((bool) Address::find($addr1Id)?->is_default);
        $this->assertTrue((bool) Address::find($addr2Id)?->is_default);

        // Other user's address cannot be modified/deleted
        $foreignAddress = Address::create([
            'user_id' => $otherUser->id,
            'type' => 'shipping',
            'first_name' => 'Other',
            'last_name' => 'User',
            'phone' => '099888777',
            'province' => 'Kandal',
            'street_address' => 'St 21',
            'is_default' => true,
        ]);

        $this->withToken($token)
            ->putJson("/api/v1/account/addresses/{$foreignAddress->id}", [
                'name' => 'Hijack',
                'phone' => '012345678',
                'province' => 'Kandal',
                'street_address' => 'St 99',
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->deleteJson("/api/v1/account/addresses/{$foreignAddress->id}")
            ->assertForbidden();

        $this->withToken($token)
            ->deleteJson("/api/v1/account/addresses/{$addr1Id}")
            ->assertOk();
    }

    public function test_info_pages_contact_and_newsletter_endpoints(): void
    {
        foreach (['about', 'faq', 'shipping', 'size-guide', 'privacy', 'terms'] as $slug) {
            $this->withHeaders(['X-Locale' => 'en'])
                ->getJson("/api/v1/pages/{$slug}")
                ->assertOk()
                ->assertJsonPath('slug', $slug)
                ->assertJsonPath('locale', 'en');

            $this->withHeaders(['X-Locale' => 'km'])
                ->getJson("/api/v1/pages/{$slug}")
                ->assertOk()
                ->assertJsonPath('slug', $slug)
                ->assertJsonPath('locale', 'km');
        }

        $this->getJson('/api/v1/pages/size-guide')
            ->assertOk()
            ->assertJsonCount(2, 'size_guide.tables');

        $this->getJson('/api/v1/pages/unknown-slug')
            ->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');

        $this->postJson('/api/v1/contact', [
            'name' => 'Sokha',
            'email' => 'SOKHA@RESET.STORE',
            'subject' => 'Order help',
            'message' => 'Could you confirm delivery times to Siem Reap?',
        ])->assertCreated()
            ->assertJsonStructure(['message', 'contact_message_id']);

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'sokha@reset.store',
            'subject' => 'Order help',
        ]);

        $this->postJson('/api/v1/newsletter', [
            'email' => '  NEWS@Reset.Store ',
        ])->assertOk()
            ->assertJsonPath('email', 'news@reset.store')
            ->assertJsonPath('is_active', true);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'news@reset.store',
            'is_active' => true,
        ]);
    }
}
