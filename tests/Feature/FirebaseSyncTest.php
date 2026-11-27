<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Services\FirebaseFirestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class FirebaseSyncTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Generate a temporary RSA 2048-bit keypair in memory for RS256 JWT tests.
     *
     * @return array{private_key: string, public_key: string}
     */
    private function generateEphemeralRsaKeypair(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $tempConfig = null;
        if ($resource === false) {
            $tempConfig = tempnam(sys_get_temp_dir(), 'reset_openssl_');
            if ($tempConfig !== false) {
                file_put_contents($tempConfig, "[req]\ndistinguished_name=req_dn\n[req_dn]\n");
                $resource = openssl_pkey_new([
                    'private_key_bits' => 2048,
                    'private_key_type' => OPENSSL_KEYTYPE_RSA,
                    'config' => $tempConfig,
                ]);
            }
        }

        if ($resource === false) {
            if (is_string($tempConfig)) {
                @unlink($tempConfig);
            }
            $this->fail('OpenSSL RSA key generation failed.');
        }

        $privateKey = '';
        $exportOptions = is_string($tempConfig) ? ['config' => $tempConfig] : [];
        openssl_pkey_export($resource, $privateKey, null, $exportOptions);
        $details = openssl_pkey_get_details($resource);

        if (is_string($tempConfig)) {
            @unlink($tempConfig);
        }

        return [
            'private_key' => $privateKey,
            'public_key' => (string) ($details['key'] ?? ''),
        ];
    }

    private function base64UrlDecode(string $value): string
    {
        $padded = strtr($value, '-_', '+/');
        $rem = strlen($padded) % 4;
        if ($rem > 0) {
            $padded .= str_repeat('=', 4 - $rem);
        }

        return (string) base64_decode($padded, true);
    }

    public function test_disabled_firebase_performs_zero_outbound_http_calls(): void
    {
        Http::fake();
        config(['firebase.enabled' => false]);

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        ProductVariant::factory()->create(['product_id' => $product->id]);
        Setting::set('store_name', 'RESET Store');

        $this->artisan('app:firebase-sync')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_creates_valid_rs256_jwt_signed_with_openssl_sign(): void
    {
        $keys = $this->generateEphemeralRsaKeypair();
        $service = app(FirebaseFirestoreService::class);

        $jwt = $service->createServiceAccountJwt([
            'client_email' => 'firebase-sync@reset-store-test.iam.gserviceaccount.com',
            'private_key' => $keys['private_key'],
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ], 1700000000);

        $parts = explode('.', $jwt);
        $this->assertCount(3, $parts);

        $header = json_decode($this->base64UrlDecode($parts[0]), true);
        $claims = json_decode($this->base64UrlDecode($parts[1]), true);

        $this->assertSame('RS256', $header['alg']);
        $this->assertSame('JWT', $header['typ']);
        $this->assertSame('firebase-sync@reset-store-test.iam.gserviceaccount.com', $claims['iss']);
        $this->assertSame('https://www.googleapis.com/auth/datastore', $claims['scope']);
        $this->assertSame(1700000000, $claims['iat']);
        $this->assertSame(1700003600, $claims['exp']);

        $verified = openssl_verify(
            "{$parts[0]}.{$parts[1]}",
            $this->base64UrlDecode($parts[2]),
            $keys['public_key'],
            OPENSSL_ALGO_SHA256
        );
        $this->assertSame(1, $verified);
    }

    public function test_firestore_sync_sends_typed_payloads_and_strictly_redacts_pii(): void
    {
        Cache::flush();
        $keys = $this->generateEphemeralRsaKeypair();

        config([
            'firebase.enabled' => true,
            'firebase.project_id' => 'reset-store-demo',
            'firebase.database_id' => '(default)',
            'firebase.collections_prefix' => 'prod',
            'firebase.sync_on_write' => true,
            'firebase.credentials_json' => json_encode([
                'project_id' => 'reset-store-demo',
                'client_email' => 'sync-bot@reset-store-demo.iam.gserviceaccount.com',
                'private_key' => $keys['private_key'],
                'token_uri' => 'https://oauth2.googleapis.com/token',
            ], JSON_THROW_ON_ERROR),
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'mock-oauth2-token-123',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'https://firestore.googleapis.com/*' => Http::response(['name' => 'projects/reset-store-demo/databases/(default)/documents/ok'], 200),
        ]);

        $user = User::factory()->create([
            'name' => 'Dara Sok',
            'email' => 'secret-customer@reset.store',
            'phone' => '+85512999888',
            'role' => UserRole::Customer,
        ]);

        $category = Category::factory()->create(['name' => 'Tees', 'slug' => 'tees']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'RESET Boxy Tee',
            'base_price_cents' => 120000,
            'is_published' => true,
        ]);
        ProductImage::create([
            'product_id' => $product->id,
            'image_url' => 'https://example.com/tee.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Onyx Black',
            'price_cents' => 120000,
            'stock_quantity' => 10,
            'reserved_quantity' => 2,
        ]);

        Discount::factory()->create([
            'code' => 'RESET10',
            'type' => DiscountType::Percentage,
            'value' => 10,
        ]);

        Setting::set('exchange_rate', 4100);
        Setting::set('internal_secret_key', 'should_not_sync');

        $approvedReview = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'title' => 'Clean cut',
            'body' => 'Great fabric.',
            'is_verified_purchase' => true,
            'is_approved' => true,
        ]);

        $unapprovedReview = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 1,
            'title' => 'Spam',
            'body' => 'Spam review',
            'is_verified_purchase' => false,
            'is_approved' => false,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'number' => 'RST-FB-001',
            'email' => 'secret-customer@reset.store',
            'phone' => '+85512999888',
            'shipping_address' => [
                'name' => 'Dara Sok',
                'province' => 'Phnom Penh',
                'district' => 'Daun Penh',
                'street_address' => 'Secret House 99',
            ],
            'billing_address' => [
                'street_address' => 'Secret Billing 99',
            ],
            'customer_notes' => 'Gate code 1234',
            'total_cents' => 120000,
            'currency' => 'KHR',
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'md5' => md5('secret-qr-md5'),
            'qr_string' => '000201_SECRET_QR_PAYLOAD',
            'account_id' => 'merchant@aba',
            'amount' => 120000.00,
            'currency' => 'KHR',
            'status' => PaymentStatus::Paid,
            'bakong_response' => ['secret' => 'raw_gateway'],
            'manual_paid_note' => 'Admin internal note',
        ]);

        // Assert OAuth2 token was fetched once and cached across all 12 Firestore writes/deletes
        $oauthCalls = collect(Http::recorded())->filter(
            static fn (array $pair): bool => str_contains($pair[0]->url(), 'oauth2.googleapis.com/token')
        )->count();
        $this->assertSame(1, $oauthCalls);
        Http::assertSentCount(13);

        // Assert strict zero-PII payloads on orders, payments, reviews, and non-allowlisted settings
        Http::assertNotSent(static fn (HttpClientRequest $req): bool => str_contains($req->url(), 'internal_secret_key'));

        Http::assertSent(static function (HttpClientRequest $req) use ($order): bool {
            if ($req->method() !== 'PATCH' || ! str_contains($req->url(), "/prod_orders/{$order->number}")) {
                return false;
            }

            $body = $req->body();
            $fields = $req->data()['fields'] ?? [];

            return isset($fields['number']['stringValue'])
                && $fields['province']['stringValue'] === 'Phnom Penh'
                && $fields['total_cents']['integerValue'] === '120000'
                && ! str_contains($body, 'secret-customer@reset.store')
                && ! str_contains($body, '+85512999888')
                && ! str_contains($body, 'Secret House 99')
                && ! str_contains($body, 'Gate code 1234')
                && ! array_key_exists('email', $fields)
                && ! array_key_exists('phone', $fields)
                && ! array_key_exists('user_id', $fields)
                && ! array_key_exists('shipping_address', $fields)
                && ! array_key_exists('billing_address', $fields);
        });

        Http::assertSent(static function (HttpClientRequest $req): bool {
            if ($req->method() !== 'PATCH' || ! str_contains($req->url(), '/prod_payments/')) {
                return false;
            }

            $body = $req->body();
            $fields = $req->data()['fields'] ?? [];

            return isset($fields['status']['stringValue'])
                && ! str_contains($body, '000201_SECRET_QR_PAYLOAD')
                && ! str_contains($body, 'merchant@aba')
                && ! str_contains($body, 'Admin internal note')
                && ! array_key_exists('qr_string', $fields)
                && ! array_key_exists('md5', $fields)
                && ! array_key_exists('account_id', $fields)
                && ! array_key_exists('bakong_response', $fields);
        });

        Http::assertSent(static fn (HttpClientRequest $req): bool => $req->method() === 'DELETE'
            && str_contains($req->url(), "/prod_reviews/{$unapprovedReview->id}")
        );

        Http::assertSent(static function (HttpClientRequest $req) use ($approvedReview): bool {
            if ($req->method() !== 'PATCH' || ! str_contains($req->url(), "/prod_reviews/{$approvedReview->id}")) {
                return false;
            }
            $fields = $req->data()['fields'] ?? [];

            return ($fields['author_name']['stringValue'] ?? null) === 'Dara Sok'
                && ! array_key_exists('user_id', $fields)
                && ! array_key_exists('email', $fields);
        });
    }

    public function test_rolled_back_transaction_and_network_failure_never_break_local_writes(): void
    {
        Cache::flush();
        $keys = $this->generateEphemeralRsaKeypair();

        config([
            'firebase.enabled' => true,
            'firebase.project_id' => 'reset-store-demo',
            'firebase.sync_on_write' => true,
            'firebase.credentials_json' => json_encode([
                'project_id' => 'reset-store-demo',
                'client_email' => 'sync-bot@reset-store-demo.iam.gserviceaccount.com',
                'private_key' => $keys['private_key'],
                'token_uri' => 'https://oauth2.googleapis.com/token',
            ], JSON_THROW_ON_ERROR),
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'tok'], 200),
            'https://firestore.googleapis.com/*' => Http::response(['error' => 'unavailable'], 503),
        ]);

        // 1. Rolled-back transaction MUST trigger zero Firestore HTTP calls
        try {
            DB::transaction(static function (): void {
                Category::factory()->create(['slug' => 'rolled-back-cat']);
                throw new RuntimeException('Simulated rollback');
            });
        } catch (RuntimeException) {
        }

        Http::assertNothingSent();

        // 2. Committed write with Firestore 503 failure succeeds locally without throwing
        $cat = Category::factory()->create(['slug' => 'committed-despite-503']);
        $this->assertDatabaseHas('categories', ['id' => $cat->id]);
    }

    public function test_firebase_sync_command_dry_run_validates_all_collections_without_http(): void
    {
        Http::fake();
        config(['firebase.enabled' => false]);

        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);
        Setting::set('store_name', 'RESET Store');

        $this->artisan('app:firebase-sync', ['--dry-run' => true])
            ->assertSuccessful();

        Http::assertNothingSent();
    }
}
