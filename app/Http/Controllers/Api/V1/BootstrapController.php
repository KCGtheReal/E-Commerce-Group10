<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CartResource;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Category;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\CatalogCacheService;
use App\Services\CurrencyService;
use App\Services\FirebaseApiClient;
use App\Services\StoreContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BootstrapController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly StoreContentService $storeContentService,
        private readonly FirebaseApiClient $firebaseApi
    ) {}

    public function index(Request $request): JsonResponse
    {
        $locale = (string) ($request->attributes->get('api_locale') ?? app()->getLocale());
        $currency = CurrencyService::current();
        $user = $request->user();
        $cartToken = $request->header('X-Cart-Token');
        $cart = ($user || (is_string($cartToken) && trim($cartToken) !== ''))
            ? $this->cartService->resolveApiCart($user, is_string($cartToken) ? $cartToken : null, (bool) $user)
            : null;

        if ($this->firebaseApi->isEnabled()) {
            $firebaseBootstrap = $this->firebaseApi->bootstrap($locale, $currency);

            if (
                is_array($firebaseBootstrap)
                && is_array($firebaseBootstrap['settings'] ?? null)
                && is_array($firebaseBootstrap['categories'] ?? null)
            ) {
                $settings = array_merge($firebaseBootstrap['settings'], [
                    'bakong_mock' => (bool) config('bakong.mock', false),
                    'bakong_currency' => strtoupper((string) config('bakong.currency', 'KHR')),
                    'qr_ttl_seconds' => (int) config('bakong.qr_ttl_seconds', 300),
                ]);

                return response()->json([
                    'locale' => (string) ($firebaseBootstrap['locale'] ?? $locale),
                    'currency' => (string) ($firebaseBootstrap['currency'] ?? $currency),
                    'settings' => $settings,
                    'categories' => $firebaseBootstrap['categories'],
                    'size_guide' => is_array($firebaseBootstrap['size_guide'] ?? null)
                        ? $firebaseBootstrap['size_guide']
                        : $this->storeContentService->sizeGuide($locale),
                    'pages' => is_array($firebaseBootstrap['pages'] ?? null)
                        ? $firebaseBootstrap['pages']
                        : $this->storeContentService->pageSummaries($locale),
                    'authenticated' => $user !== null,
                    'user' => $user ? (new UserResource($user))->resolve($request) : null,
                    'cart' => $cart ? (new CartResource($cart))->resolve($request) : null,
                ]);
            }
        }

        $exchangeRate = CurrencyService::exchangeRate();

        $allSettings = Setting::allValues();
        $freeShippingThreshold = (int) ($allSettings['free_shipping_threshold'] ?? 200000);

        $rawFees = $allSettings['province_shipping_fees'] ?? [];
        $provinceFees = is_array($rawFees) && $rawFees !== []
            ? array_map(static fn (mixed $fee): int => (int) $fee, $rawFees)
            : array_fill_keys(CartService::CAMBODIA_PROVINCES, 0);

        $bannerEn = (string) ($allSettings['announcement_banner_en'] ?? 'Free delivery on orders over 200,000 ៛ | Instant QR checkout with Bakong KHQR');
        $bannerKm = (string) ($allSettings['announcement_banner_km'] ?? 'ដឹកជញ្ជូនឥតគិតថ្លៃសម្រាប់ការបញ្ជាទិញចាប់ពី 200,000 ៛ ឡើងទៅ | ទូទាត់រហ័សជាមួយបាគង KHQR');

        $categories = CatalogCacheService::remember("bootstrap:categories:{$locale}", 300, static function () use ($request): array {
            $models = Category::where('is_active', true)
                ->withCount(['products' => static fn ($q) => $q->where('is_published', true)])
                ->orderBy('sort_order')
                ->get();

            return CategoryResource::collection($models)->resolve($request);
        });

        return response()->json([
            'locale' => $locale,
            'currency' => $currency,
            'settings' => [
                'store_name' => (string) ($allSettings['store_name'] ?? 'RESET Store'),
                'store_tagline' => (string) ($allSettings['store_tagline'] ?? 'Start fresh.'),
                'store_tagline_km' => (string) ($allSettings['store_tagline_km'] ?? 'ចាប់ផ្តើមថ្មី។'),
                'store_email' => (string) ($allSettings['store_email'] ?? 'support@reset.store'),
                'store_phone' => (string) ($allSettings['store_phone'] ?? '+855 23 999 123'),
                'store_address' => (string) ($allSettings['store_address'] ?? 'Street 240, Daun Penh, Phnom Penh, Cambodia'),
                'announcement_banner_en' => $bannerEn,
                'announcement_banner_km' => $bannerKm,
                'announcement_banner' => $locale === 'km' ? $bannerKm : $bannerEn,
                'exchange_rate' => $exchangeRate,
                'free_shipping_threshold' => $freeShippingThreshold,
                'formatted_free_shipping_threshold' => CurrencyService::formatCents($freeShippingThreshold, $currency),
                'express_shipping_fee_khr' => CartService::EXPRESS_SHIPPING_FEE_KHR,
                'express_shipping_province' => CartService::EXPRESS_SHIPPING_PROVINCE,
                'province_shipping_fees' => $provinceFees,
                'provinces' => array_keys($provinceFees),
                'bakong_mock' => (bool) config('bakong.mock', false),
                'bakong_currency' => strtoupper((string) config('bakong.currency', 'KHR')),
                'qr_ttl_seconds' => (int) config('bakong.qr_ttl_seconds', 300),
            ],
            'categories' => $categories,
            'size_guide' => $this->storeContentService->sizeGuide($locale),
            'pages' => $this->storeContentService->pageSummaries($locale),
            'authenticated' => $user !== null,
            'user' => $user ? (new UserResource($user))->resolve($request) : null,
            'cart' => $cart ? (new CartResource($cart))->resolve($request) : null,
        ]);
    }
}
