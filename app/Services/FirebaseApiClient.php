<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebaseApiClient
{
    /**
     * @var array<int, string>
     */
    private const VALID_CACHE_SCOPES = [
        'all',
        'categories',
        'products',
        'settings',
        'reviews',
        'orders',
        'payments',
        'discounts',
        'bootstrap',
        'home',
    ];

    /**
     * @var array<int, string>
     */
    private const ALLOWED_PRODUCT_QUERY_KEYS = [
        'q',
        'category',
        'size',
        'color',
        'min_price',
        'max_price',
        'in_stock',
        'featured',
        'new_arrival',
        'best_seller',
        'sort',
        'page',
        'per_page',
        'fresh',
    ];

    /**
     * @var array<int, string>
     */
    private const ALLOWED_REVIEW_QUERY_KEYS = [
        'product_id',
        'rating',
        'verified_only',
        'page',
        'per_page',
        'fresh',
    ];

    private ?int $lastStatus = null;

    public function isEnabled(): bool
    {
        return (bool) config('firebase_api.enabled', false)
            && $this->baseUrl() !== '';
    }

    public function lastStatus(): ?int
    {
        return $this->lastStatus;
    }

    public function lastWasNotFound(): bool
    {
        return $this->lastStatus === 404;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function health(bool $ping = false): ?array
    {
        return $this->getJson('/health', $ping ? ['ping' => '1'] : []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function bootstrap(string $locale, string $currency): ?array
    {
        return $this->getJson('/api/firebase/bootstrap', [], $this->contextHeaders($locale, $currency));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function home(string $locale, string $currency): ?array
    {
        return $this->getJson('/api/firebase/home', [], $this->contextHeaders($locale, $currency));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function categories(string $locale, string $currency): ?array
    {
        return $this->getJson('/api/firebase/categories', [], $this->contextHeaders($locale, $currency));
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    public function products(array $query, string $locale, string $currency): ?array
    {
        $filtered = array_intersect_key($query, array_flip(self::ALLOWED_PRODUCT_QUERY_KEYS));

        return $this->getJson('/api/firebase/products', $filtered, $this->contextHeaders($locale, $currency));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function productDetail(string $slugOrId, string $locale, string $currency): ?array
    {
        $trimmed = trim($slugOrId);
        if ($trimmed === '') {
            $this->lastStatus = null;

            return null;
        }

        return $this->getJson(
            '/api/firebase/products/'.rawurlencode($trimmed),
            [],
            $this->contextHeaders($locale, $currency)
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function searchSuggest(string $query, string $locale, string $currency): ?array
    {
        $trimmed = trim($query);

        return $this->getJson(
            '/api/firebase/search/suggest',
            $trimmed !== '' ? ['q' => $trimmed] : [],
            $this->contextHeaders($locale, $currency)
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function settings(): ?array
    {
        return $this->getJson('/api/firebase/settings');
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    public function reviews(array $query = []): ?array
    {
        $filtered = array_intersect_key($query, array_flip(self::ALLOWED_REVIEW_QUERY_KEYS));

        return $this->getJson('/api/firebase/reviews', $filtered);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function invalidateCache(?string $collection = null): ?array
    {
        $this->lastStatus = null;

        if (! $this->isEnabled()) {
            return null;
        }

        $apiKey = trim((string) config('firebase_api.api_key', ''));
        if ($apiKey === '') {
            return null;
        }

        $normalizedScope = strtolower(trim((string) ($collection ?? '')));
        $scopes = ($normalizedScope !== '' && in_array($normalizedScope, self::VALID_CACHE_SCOPES, true))
            ? [$normalizedScope]
            : ['all'];

        try {
            $response = $this->baseRequest([
                'X-API-Key' => $apiKey,
            ])->post($this->baseUrl().'/api/firebase/cache/invalidate', [
                'scopes' => $scopes,
            ]);

            $this->lastStatus = $response->status();

            if (! $response->successful()) {
                Log::warning('Firebase API Gateway cache invalidation returned non-success status.', [
                    'scopes' => $scopes,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : null;
        } catch (Throwable $e) {
            $this->lastStatus = null;
            Log::warning('Firebase API Gateway cache invalidation failed safely.', [
                'scopes' => $scopes,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, string>  $headers
     * @return array<string, mixed>|null
     */
    private function getJson(string $path, array $query = [], array $headers = []): ?array
    {
        $this->lastStatus = null;

        if (! $this->isEnabled()) {
            return null;
        }

        $queryString = $this->buildQueryString($query);
        $url = $this->baseUrl().$path.($queryString !== '' ? '?'.$queryString : '');

        try {
            $response = $this->baseRequest($headers)->get($url);
            $this->lastStatus = $response->status();

            if (! $response->successful()) {
                if ($response->status() !== 404) {
                    Log::warning('Firebase API Gateway request returned non-success status.', [
                        'path' => $path,
                        'status' => $response->status(),
                    ]);
                }

                return null;
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : null;
        } catch (Throwable $e) {
            $this->lastStatus = null;
            Log::warning('Firebase API Gateway request failed; falling back to SQLite.', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function baseRequest(array $headers = []): PendingRequest
    {
        $timeout = max(0.1, (float) config('firebase_api.timeout_seconds', 2.0));
        $connectTimeout = max(0.1, (float) config('firebase_api.connect_timeout_seconds', 0.5));

        $request = Http::acceptJson()
            ->connectTimeout($connectTimeout)
            ->timeout($timeout);

        if ($headers !== []) {
            $request = $request->withHeaders($headers);
        }

        return $request;
    }

    /**
     * @return array<string, string>
     */
    private function contextHeaders(string $locale, string $currency): array
    {
        return [
            'X-Locale' => $this->normalizeLocale($locale),
            'X-Currency' => $this->normalizeCurrency($currency),
        ];
    }

    private function normalizeLocale(string $locale): string
    {
        return strtolower(trim($locale)) === 'km' ? 'km' : 'en';
    }

    private function normalizeCurrency(string $currency): string
    {
        return strtoupper(trim($currency)) === 'USD' ? 'USD' : 'KHR';
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('firebase_api.base_url', 'http://127.0.0.1:4000'), '/');
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function buildQueryString(array $query): string
    {
        $pairs = [];

        foreach ($query as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $paramKey = (string) $key;

            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item === null || $item === '') {
                        continue;
                    }
                    $scalar = is_bool($item) ? ($item ? '1' : '0') : trim((string) $item);
                    if ($scalar === '') {
                        continue;
                    }
                    $pairs[] = rawurlencode($paramKey.'[]').'='.rawurlencode($scalar);
                }

                continue;
            }

            $scalar = is_bool($value) ? ($value ? '1' : '0') : trim((string) $value);
            if ($scalar === '') {
                continue;
            }

            $pairs[] = rawurlencode($paramKey).'='.rawurlencode($scalar);
        }

        return implode('&', $pairs);
    }
}
