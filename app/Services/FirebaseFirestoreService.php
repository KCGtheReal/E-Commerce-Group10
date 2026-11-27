<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use BackedEnum;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Throwable;

class FirebaseFirestoreService
{
    public const SUPPORTED_COLLECTIONS = [
        'categories',
        'products',
        'discounts',
        'settings',
        'reviews',
        'orders',
        'payments',
    ];

    public const SETTINGS_PUBLIC_ALLOWLIST = [
        'store_name',
        'store_tagline',
        'store_tagline_km',
        'exchange_rate',
        'free_shipping_threshold',
        'announcement_banner_en',
        'announcement_banner_km',
        'province_shipping_fees',
        'store_email',
        'store_phone',
        'store_address',
    ];

    /**
     * Defense-in-depth denylist of sensitive/PII keys stripped recursively from any synced payload.
     */
    private const FORBIDDEN_SENSITIVE_KEYS = [
        'password',
        'remember_token',
        'token_hash',
        'api_tokens',
        'email',
        'phone',
        'street_address',
        'district',
        'commune',
        'billing_address',
        'shipping_address',
        'customer_notes',
        'refund_note',
        'qr_string',
        'md5',
        'account_id',
        'bakong_response',
        'manual_paid_note',
        'manual_paid_by',
        'error_message',
    ];

    public function isEnabled(): bool
    {
        return (bool) config('firebase.enabled', false);
    }

    public function syncOnWriteEnabled(): bool
    {
        return $this->isEnabled() && (bool) config('firebase.sync_on_write', true);
    }

    public function collectionName(string $collection): string
    {
        $prefix = trim((string) config('firebase.collections_prefix', ''));
        if ($prefix === '') {
            return $collection;
        }

        $normalizedPrefix = rtrim($prefix, '_-');

        return "{$normalizedPrefix}_{$collection}";
    }

    public function documentUrl(string $collection, string $documentId, ?string $projectId = null): string
    {
        $resolvedProjectId = $projectId ?: $this->resolveProjectId();
        $databaseId = trim((string) config('firebase.database_id', '(default)')) ?: '(default)';
        $collectionPath = rawurlencode($this->collectionName($collection));
        $docId = rawurlencode($documentId);

        return "https://firestore.googleapis.com/v1/projects/{$resolvedProjectId}/databases/{$databaseId}/documents/{$collectionPath}/{$docId}";
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveCredentials(): ?array
    {
        $jsonInline = config('firebase.credentials_json');
        if (is_string($jsonInline) && trim($jsonInline) !== '') {
            $decoded = json_decode($jsonInline, true);
            if (is_array($decoded) && ! empty($decoded['client_email']) && ! empty($decoded['private_key'])) {
                return $decoded;
            }
        }

        $path = (string) config('firebase.credentials_path', '');
        if ($path !== '' && is_file($path) && is_readable($path)) {
            $contents = file_get_contents($path);
            if ($contents !== false) {
                $decoded = json_decode($contents, true);
                if (is_array($decoded) && ! empty($decoded['client_email']) && ! empty($decoded['private_key'])) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $credentials
     */
    public function resolveProjectId(?array $credentials = null): string
    {
        $configured = trim((string) config('firebase.project_id', ''));
        if ($configured !== '') {
            return $configured;
        }

        $creds = $credentials ?? $this->resolveCredentials();

        return trim((string) ($creds['project_id'] ?? ''));
    }

    /**
     * Build and sign a Google Service Account RS256 JWT assertion using native PHP OpenSSL.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function createServiceAccountJwt(array $credentials, ?int $now = null): string
    {
        $clientEmail = trim((string) ($credentials['client_email'] ?? ''));
        $privateKey = (string) ($credentials['private_key'] ?? '');
        $tokenUri = trim((string) ($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token'));

        if ($clientEmail === '' || $privateKey === '') {
            throw new RuntimeException('Firebase Service Account credentials require client_email and private_key.');
        }

        $issuedAt = $now ?? time();
        $header = $this->base64UrlEncode((string) json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));

        $claims = $this->base64UrlEncode((string) json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud' => $tokenUri !== '' ? $tokenUri : 'https://oauth2.googleapis.com/token',
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
        ], JSON_THROW_ON_ERROR));

        $signingInput = "{$header}.{$claims}";
        $signature = '';
        $signed = @openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (! $signed || $signature === '') {
            throw new RuntimeException('Unable to sign Firebase Service Account JWT using openssl_sign.');
        }

        return "{$signingInput}.".$this->base64UrlEncode($signature);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function getAccessToken(array $credentials): ?string
    {
        $projectId = $this->resolveProjectId($credentials);
        $clientEmail = (string) ($credentials['client_email'] ?? '');
        $tokenUri = trim((string) ($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token'));
        $cacheKey = 'firebase.oauth2.token.'.hash('sha256', "{$projectId}|{$clientEmail}|{$tokenUri}");

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $jwt = $this->createServiceAccountJwt($credentials);
            $response = Http::asForm()
                ->timeout((int) config('firebase.timeout_seconds', 10))
                ->post($tokenUri, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            if (! $response->successful()) {
                Log::warning('Firebase OAuth2 token exchange failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $accessToken = (string) $response->json('access_token', '');
            if ($accessToken === '') {
                Log::warning('Firebase OAuth2 response omitted access_token.');

                return null;
            }

            $expiresIn = max(60, (int) $response->json('expires_in', 3600));
            $ttl = max(60, min(3000, $expiresIn - 120));
            Cache::put($cacheKey, $accessToken, $ttl);

            return $accessToken;
        } catch (Throwable $e) {
            Log::warning('Firebase OAuth2 token request error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Convert an associative PHP array into Firestore REST v1 typed `fields` map.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>|stdClass
     */
    public function toFirestoreFields(array $data): array|stdClass
    {
        $sanitized = $this->stripSensitiveKeys($data);
        if ($sanitized === []) {
            return new stdClass;
        }

        $fields = [];
        foreach ($sanitized as $key => $value) {
            $fields[(string) $key] = $this->encodeFirestoreValue($value);
        }

        return $fields;
    }

    /**
     * @return array<string, mixed>
     */
    public function encodeFirestoreValue(mixed $value): array
    {
        if ($value === null) {
            return ['nullValue' => null];
        }

        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }

        if (is_int($value)) {
            return ['integerValue' => (string) $value];
        }

        if (is_float($value)) {
            return ['doubleValue' => $value];
        }

        if ($value instanceof DateTimeInterface) {
            $utc = (new \DateTimeImmutable($value->format(DateTimeInterface::ATOM)))
                ->setTimezone(new DateTimeZone('UTC'));

            return ['timestampValue' => $utc->format('Y-m-d\TH:i:s.u\Z')];
        }

        if ($value instanceof BackedEnum) {
            return ['stringValue' => (string) $value->value];
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                $values = array_map(fn (mixed $item): array => $this->encodeFirestoreValue($item), $value);

                return ['arrayValue' => ['values' => $values]];
            }

            return [
                'mapValue' => [
                    'fields' => $this->toFirestoreFields($value),
                ],
            ];
        }

        return ['stringValue' => (string) $value];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertDocument(string $collection, string $documentId, array $data): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $credentials = $this->resolveCredentials();
        $projectId = $this->resolveProjectId($credentials);

        if (! $credentials || $projectId === '') {
            Log::warning('Firebase sync skipped: missing Firebase credentials or project_id.', [
                'collection' => $collection,
                'document_id' => $documentId,
            ]);

            return false;
        }

        $accessToken = $this->getAccessToken($credentials);
        if (! $accessToken) {
            return false;
        }

        try {
            $url = $this->documentUrl($collection, $documentId, $projectId);
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout((int) config('firebase.timeout_seconds', 10))
                ->patch($url, [
                    'fields' => $this->toFirestoreFields($data),
                ]);

            if (! $response->successful()) {
                Log::warning('Firebase Firestore document upsert failed.', [
                    'collection' => $collection,
                    'document_id' => $documentId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error('Firebase Firestore upsert exception: '.$e->getMessage(), [
                'collection' => $collection,
                'document_id' => $documentId,
            ]);

            return false;
        }
    }

    public function deleteDocument(string $collection, string $documentId): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $credentials = $this->resolveCredentials();
        $projectId = $this->resolveProjectId($credentials);

        if (! $credentials || $projectId === '') {
            return false;
        }

        $accessToken = $this->getAccessToken($credentials);
        if (! $accessToken) {
            return false;
        }

        try {
            $url = $this->documentUrl($collection, $documentId, $projectId);
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout((int) config('firebase.timeout_seconds', 10))
                ->delete($url);

            if (! $response->successful() && $response->status() !== 404) {
                Log::warning('Firebase Firestore document delete failed.', [
                    'collection' => $collection,
                    'document_id' => $documentId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error('Firebase Firestore delete exception: '.$e->getMessage(), [
                'collection' => $collection,
                'document_id' => $documentId,
            ]);

            return false;
        }
    }

    // -------------------------------------------------------------------------
    // Domain Payload Builders (Strictly Non-PII)
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function buildCategoryPayload(Category $category): array
    {
        return [
            'id' => (int) $category->id,
            'name' => (string) $category->name,
            'name_km' => $category->name_km,
            'slug' => (string) $category->slug,
            'description' => $category->description,
            'description_km' => $category->description_km,
            'image_url' => $category->image_url,
            'is_active' => (bool) $category->is_active,
            'sort_order' => (int) $category->sort_order,
            'updated_at' => $category->updated_at ?? now(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildProductPayload(Product $product): array
    {
        $product->loadMissing(['category', 'images', 'variants']);
        $approvedReviewsQuery = $product->reviews();
        $reviewCount = (int) ($product->getAttributes()['approved_reviews_count'] ?? $approvedReviewsQuery->count());
        $averageRating = $reviewCount > 0
            ? round((float) ($product->getAttributes()['approved_reviews_avg'] ?? $product->reviews()->avg('rating') ?? 5.0), 1)
            : 5.0;

        $variants = $product->variants->map(static fn ($variant): array => [
            'id' => (int) $variant->id,
            'sku' => (string) $variant->sku,
            'size' => (string) $variant->size,
            'color' => (string) $variant->color,
            'color_code' => (string) $variant->color_code,
            'price_cents' => (int) $variant->price_cents,
            'stock_quantity' => (int) $variant->stock_quantity,
            'reserved_quantity' => (int) $variant->reserved_quantity,
            'available_stock' => (int) $variant->available_stock,
        ])->values()->all();

        $images = $product->images->map(static fn ($image): array => [
            'id' => (int) $image->id,
            'image_url' => (string) $image->image_url,
            'alt_text' => $image->alt_text,
            'is_primary' => (bool) $image->is_primary,
            'sort_order' => (int) $image->sort_order,
        ])->values()->all();

        $totalStock = array_sum(array_column($variants, 'available_stock'));

        return [
            'id' => (int) $product->id,
            'category_id' => (int) $product->category_id,
            'category_slug' => $product->category?->slug,
            'name' => (string) $product->name,
            'name_km' => $product->name_km,
            'slug' => (string) $product->slug,
            'sku_prefix' => (string) $product->sku_prefix,
            'tagline' => $product->tagline,
            'tagline_km' => $product->tagline_km,
            'description' => (string) $product->description,
            'description_km' => $product->description_km,
            'details' => is_array($product->details) ? $product->details : [],
            'base_price_cents' => (int) $product->base_price_cents,
            'compare_at_price_cents' => $product->compare_at_price_cents !== null ? (int) $product->compare_at_price_cents : null,
            'is_featured' => (bool) $product->is_featured,
            'is_new_arrival' => (bool) $product->is_new_arrival,
            'is_best_seller' => (bool) $product->is_best_seller,
            'is_published' => (bool) $product->is_published,
            'total_stock' => (int) $totalStock,
            'is_in_stock' => $totalStock > 0,
            'average_rating' => $averageRating,
            'review_count' => $reviewCount,
            'images' => $images,
            'variants' => $variants,
            'updated_at' => $product->updated_at ?? now(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildDiscountPayload(Discount $discount): array
    {
        return [
            'id' => (int) $discount->id,
            'code' => (string) $discount->code,
            'type' => $discount->type?->value ?? (string) $discount->type,
            'value' => (int) $discount->value,
            'min_spend_cents' => (int) $discount->min_spend_cents,
            'max_uses' => $discount->max_uses !== null ? (int) $discount->max_uses : null,
            'used_count' => (int) $discount->used_count,
            'starts_at' => $discount->starts_at,
            'expires_at' => $discount->expires_at,
            'is_active' => (bool) $discount->is_active,
            'updated_at' => $discount->updated_at ?? now(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buildSettingPayload(Setting $setting): ?array
    {
        if (! in_array($setting->key, self::SETTINGS_PUBLIC_ALLOWLIST, true)) {
            return null;
        }

        $rawValue = $setting->value;
        $decodedValue = $rawValue;
        if (is_string($rawValue)) {
            $json = json_decode($rawValue, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                $decodedValue = $json;
            } elseif (is_numeric($rawValue) && ! str_starts_with($rawValue, '+')) {
                $decodedValue = str_contains($rawValue, '.') ? (float) $rawValue : (int) $rawValue;
            }
        }

        return [
            'key' => (string) $setting->key,
            'group' => (string) $setting->group,
            'value' => $decodedValue,
            'updated_at' => $setting->updated_at ?? now(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buildReviewPayload(Review $review): ?array
    {
        if (! $review->is_approved) {
            return null;
        }

        $review->loadMissing('user:id,name');

        return [
            'id' => (int) $review->id,
            'product_id' => (int) $review->product_id,
            'author_name' => (string) ($review->user?->name ?? 'RESET Customer'),
            'rating' => (int) $review->rating,
            'title' => $review->title,
            'body' => (string) $review->body,
            'is_verified_purchase' => (bool) $review->is_verified_purchase,
            'is_approved' => true,
            'created_at' => $review->created_at ?? now(),
            'updated_at' => $review->updated_at ?? now(),
        ];
    }

    /**
     * Strictly sanitized Order mirror — excludes email, phone, user_id, street_address, billing_address, and notes.
     *
     * @return array<string, mixed>
     */
    public function buildOrderPayload(Order $order): array
    {
        $itemsCount = array_key_exists('items_count', $order->getAttributes())
            ? (int) $order->getAttribute('items_count')
            : ($order->relationLoaded('items') ? $order->items->count() : $order->items()->count());

        $province = is_array($order->shipping_address)
            ? ($order->shipping_address['province'] ?? null)
            : null;

        return [
            'id' => (int) $order->id,
            'number' => (string) $order->number,
            'status' => $order->status?->value ?? (string) $order->status,
            'currency' => (string) $order->currency,
            'exchange_rate' => (int) $order->exchange_rate,
            'subtotal_cents' => (int) $order->subtotal_cents,
            'discount_cents' => (int) $order->discount_cents,
            'discount_code' => $order->discount_code,
            'shipping_fee_cents' => (int) $order->shipping_fee_cents,
            'shipping_method' => (string) $order->shipping_method,
            'total_cents' => (int) $order->total_cents,
            'total_charged_currency_amount' => (float) $order->total_charged_currency_amount,
            'province' => $province !== null ? (string) $province : null,
            'items_count' => $itemsCount,
            'shipping_carrier' => $order->shipping_carrier,
            'tracking_number' => $order->tracking_number,
            'paid_at' => $order->paid_at,
            'shipped_at' => $order->shipped_at,
            'delivered_at' => $order->delivered_at,
            'cancelled_at' => $order->cancelled_at,
            'updated_at' => $order->updated_at ?? now(),
        ];
    }

    /**
     * Strictly sanitized Payment mirror — excludes qr_string, md5, account_id, bakong_response, and admin notes.
     *
     * @return array<string, mixed>
     */
    public function buildPaymentPayload(Payment $payment): array
    {
        $payment->loadMissing('order:id,number');

        return [
            'id' => (int) $payment->id,
            'order_id' => (int) $payment->order_id,
            'order_number' => $payment->order?->number,
            'provider' => (string) $payment->provider,
            'currency' => (string) $payment->currency,
            'amount' => (float) $payment->amount,
            'status' => $payment->status?->value ?? (string) $payment->status,
            'expires_at' => $payment->expires_at,
            'paid_at' => $payment->paid_at,
            'updated_at' => $payment->updated_at ?? now(),
        ];
    }

    // -------------------------------------------------------------------------
    // Entity Sync Helpers & Bulk Backfill
    // -------------------------------------------------------------------------

    public function syncCategory(Category $category): bool
    {
        return $this->upsertDocument('categories', (string) $category->id, $this->buildCategoryPayload($category));
    }

    public function syncProduct(Product $product): bool
    {
        return $this->upsertDocument('products', (string) $product->id, $this->buildProductPayload($product));
    }

    public function syncDiscount(Discount $discount): bool
    {
        return $this->upsertDocument('discounts', (string) $discount->id, $this->buildDiscountPayload($discount));
    }

    public function syncSetting(Setting $setting): bool
    {
        $payload = $this->buildSettingPayload($setting);
        if ($payload === null) {
            return false;
        }

        return $this->upsertDocument('settings', (string) $setting->key, $payload);
    }

    public function syncReview(Review $review): bool
    {
        $payload = $this->buildReviewPayload($review);
        if ($payload === null) {
            return $this->deleteDocument('reviews', (string) $review->id);
        }

        return $this->upsertDocument('reviews', (string) $review->id, $payload);
    }

    public function syncOrder(Order $order): bool
    {
        return $this->upsertDocument('orders', (string) $order->number, $this->buildOrderPayload($order));
    }

    public function syncPayment(Payment $payment): bool
    {
        return $this->upsertDocument('payments', (string) $payment->id, $this->buildPaymentPayload($payment));
    }

    /**
     * Backfill selected or all supported SQLite collections to Cloud Firestore.
     *
     * @return array<string, array{total: int, synced: int, deleted: int, skipped: int, failed: int, status: string}>
     */
    public function syncAll(?string $collection = null, bool $dryRun = false): array
    {
        $collections = $this->resolveRequestedCollections($collection);
        $summary = [];

        foreach ($collections as $name) {
            $summary[$name] = $this->syncCollection($name, $dryRun);
        }

        if (! $dryRun && $this->isEnabled()) {
            try {
                $gatewayClient = app(FirebaseApiClient::class);
                if ($gatewayClient->isEnabled()) {
                    $gatewayClient->invalidateCache(count($collections) === 1 ? $collections[0] : 'all');
                }
            } catch (Throwable $e) {
                Log::warning('Post-sync Firebase API cache invalidation failed safely: '.$e->getMessage());
            }
        }

        return $summary;
    }

    /**
     * @return array<int, string>
     */
    private function resolveRequestedCollections(?string $collection): array
    {
        $normalized = strtolower(trim((string) ($collection ?? 'all')));
        if ($normalized === '' || $normalized === 'all') {
            return self::SUPPORTED_COLLECTIONS;
        }

        $parts = array_values(array_unique(array_filter(array_map(
            static fn (string $item): string => strtolower(trim($item)),
            explode(',', $normalized)
        ))));

        foreach ($parts as $part) {
            if (! in_array($part, self::SUPPORTED_COLLECTIONS, true)) {
                throw new InvalidArgumentException("Unsupported Firestore collection '{$part}'. Supported: ".implode(', ', self::SUPPORTED_COLLECTIONS));
            }
        }

        return $parts;
    }

    /**
     * @return array{total: int, synced: int, deleted: int, skipped: int, failed: int, status: string}
     */
    private function syncCollection(string $name, bool $dryRun): array
    {
        $stats = [
            'total' => 0,
            'synced' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'failed' => 0,
            'status' => $dryRun ? 'dry_run' : ($this->isEnabled() ? 'synced' : 'disabled'),
        ];

        $process = function (string $collection, string $docId, ?array $payload, bool $deleteIfNull = false) use (&$stats, $dryRun): void {
            $stats['total']++;

            if ($payload === null) {
                if ($deleteIfNull) {
                    if ($dryRun) {
                        $stats['deleted']++;

                        return;
                    }
                    if (! $this->isEnabled()) {
                        $stats['skipped']++;

                        return;
                    }
                    $this->deleteDocument($collection, $docId) ? $stats['deleted']++ : $stats['failed']++;

                    return;
                }

                $stats['skipped']++;

                return;
            }

            if ($dryRun) {
                // Validate encoding & PII sanitization without outbound HTTP
                $this->toFirestoreFields($payload);
                $stats['synced']++;

                return;
            }

            if (! $this->isEnabled()) {
                $stats['skipped']++;

                return;
            }

            $this->upsertDocument($collection, $docId, $payload) ? $stats['synced']++ : $stats['failed']++;
        };

        match ($name) {
            'categories' => Category::query()->orderBy('id')->chunk(100, function ($rows) use ($process): void {
                foreach ($rows as $row) {
                    $process('categories', (string) $row->id, $this->buildCategoryPayload($row));
                }
            }),
            'products' => Product::query()->with(['category', 'images', 'variants'])->withReviewStats()->orderBy('id')->chunk(50, function ($rows) use ($process): void {
                foreach ($rows as $row) {
                    $process('products', (string) $row->id, $this->buildProductPayload($row));
                }
            }),
            'discounts' => Discount::query()->orderBy('id')->chunk(100, function ($rows) use ($process): void {
                foreach ($rows as $row) {
                    $process('discounts', (string) $row->id, $this->buildDiscountPayload($row));
                }
            }),
            'settings' => Setting::query()->orderBy('id')->chunk(100, function ($rows) use ($process): void {
                foreach ($rows as $row) {
                    $process('settings', (string) $row->key, $this->buildSettingPayload($row));
                }
            }),
            'reviews' => Review::query()->with('user:id,name')->orderBy('id')->chunk(100, function ($rows) use ($process): void {
                foreach ($rows as $row) {
                    $process('reviews', (string) $row->id, $this->buildReviewPayload($row), true);
                }
            }),
            'orders' => Order::query()->withCount('items')->orderBy('id')->chunk(100, function ($rows) use ($process): void {
                foreach ($rows as $row) {
                    $process('orders', (string) $row->number, $this->buildOrderPayload($row));
                }
            }),
            'payments' => Payment::query()->with('order:id,number')->orderBy('id')->chunk(100, function ($rows) use ($process): void {
                foreach ($rows as $row) {
                    $process('payments', (string) $row->id, $this->buildPaymentPayload($row));
                }
            }),
        };

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function stripSensitiveKeys(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::FORBIDDEN_SENSITIVE_KEYS, true)) {
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->stripSensitiveKeys($value);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
