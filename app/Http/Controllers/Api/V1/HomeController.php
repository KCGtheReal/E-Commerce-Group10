<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ProductCardResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\CatalogCacheService;
use App\Services\CurrencyService;
use App\Services\FirebaseApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        private readonly FirebaseApiClient $firebaseApi
    ) {}

    public function index(Request $request): JsonResponse
    {
        $locale = (string) ($request->attributes->get('api_locale') ?? app()->getLocale());
        $currency = CurrencyService::current();

        if ($this->firebaseApi->isEnabled()) {
            $firebaseHome = $this->firebaseApi->home($locale, $currency);
            if ($firebaseHome !== null) {
                return response()->json($firebaseHome);
            }
        }

        $homeData = CatalogCacheService::remember("home:{$locale}:{$currency}", 300, static function () use ($request): array {
            $categories = Category::where('is_active', true)
                ->withCount(['products' => static fn ($q) => $q->where('is_published', true)])
                ->orderBy('sort_order')
                ->get();

            $baseQuery = static fn () => Product::where('is_published', true)
                ->with(['category', 'images', 'variants'])
                ->withReviewStats();

            return [
                'categories' => CategoryResource::collection($categories)->resolve($request),
                'featured_products' => ProductCardResource::collection($baseQuery()->where('is_featured', true)->take(8)->get())->resolve($request),
                'new_arrivals' => ProductCardResource::collection($baseQuery()->where('is_new_arrival', true)->take(8)->get())->resolve($request),
                'best_sellers' => ProductCardResource::collection($baseQuery()->where('is_best_seller', true)->take(8)->get())->resolve($request),
            ];
        });

        return response()->json($homeData);
    }
}
