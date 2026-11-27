<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductCardResource;
use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Wishlist;
use App\Services\CatalogCacheService;
use App\Services\CurrencyService;
use App\Services\FirebaseApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly FirebaseApiClient $firebaseApi
    ) {}

    private const SIZE_WEIGHTS = [
        'XXS' => 1,
        'XS' => 2,
        'S' => 3,
        'M' => 4,
        'L' => 5,
        'XL' => 6,
        'XXL' => 7,
        '2XL' => 7,
        '3XL' => 8,
        'ONE SIZE' => 9,
        'ONESIZE' => 9,
        'FREE' => 9,
    ];

    public function index(Request $request): JsonResponse
    {
        $locale = (string) ($request->attributes->get('api_locale') ?? app()->getLocale());
        $currency = CurrencyService::current();

        if ($this->firebaseApi->isEnabled()) {
            $firebaseProducts = $this->firebaseApi->products($request->query(), $locale, $currency);
            if ($firebaseProducts !== null) {
                return response()->json($firebaseProducts);
            }
        }

        $query = Product::where('is_published', true)
            ->with(['category', 'images', 'variants'])
            ->withReviewStats();

        if ($search = trim((string) $request->input('q', ''))) {
            $terms = array_filter(preg_split('/\s+/', $search) ?: []);

            $query->where(static function ($q) use ($terms): void {
                foreach ($terms as $term) {
                    $q->where(static function ($sub) use ($term): void {
                        $sub->where('name', 'like', "%{$term}%")
                            ->orWhere('name_km', 'like', "%{$term}%")
                            ->orWhere('tagline', 'like', "%{$term}%")
                            ->orWhere('tagline_km', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%")
                            ->orWhere('sku_prefix', 'like', "%{$term}%")
                            ->orWhereHas('category', static function ($cq) use ($term): void {
                                $cq->where('name', 'like', "%{$term}%")
                                    ->orWhere('name_km', 'like', "%{$term}%")
                                    ->orWhere('slug', 'like', "%{$term}%");
                            })
                            ->orWhereHas('variants', static function ($vq) use ($term): void {
                                $vq->where('color', 'like', "%{$term}%")
                                    ->orWhere('sku', 'like', "%{$term}%")
                                    ->orWhere('size', 'like', "%{$term}%");
                            });
                    });
                }
            });

            $query->orderByRaw('CASE
                WHEN name LIKE ? THEN 1
                WHEN name LIKE ? THEN 2
                ELSE 3
            END', ["{$search}%", "%{$search}%"]);
        }

        if ($categorySlug = trim((string) $request->input('category', ''))) {
            $query->whereHas('category', static function ($q) use ($categorySlug): void {
                $q->where('slug', $categorySlug);
            });
        }

        if ($size = $request->input('size')) {
            $sizes = array_values(array_filter(is_array($size) ? $size : [$size], static fn ($s): bool => is_string($s) && trim($s) !== ''));
            if ($sizes !== []) {
                $query->whereHas('variants', static function ($q) use ($sizes): void {
                    $q->whereIn('size', $sizes);
                });
            }
        }

        if ($color = trim((string) $request->input('color', ''))) {
            $query->whereHas('variants', static function ($q) use ($color): void {
                $q->where('color', $color);
            });
        }

        $isKhr = $currency === 'KHR';
        $exchangeRate = CurrencyService::exchangeRate();

        if ($request->filled('min_price')) {
            $minPrice = (float) $request->input('min_price');
            $minCents = $isKhr ? (int) round($minPrice) : (int) round($minPrice * $exchangeRate);
            $query->where('base_price_cents', '>=', $minCents);
        }

        if ($request->filled('max_price')) {
            $maxPrice = (float) $request->input('max_price');
            $maxCents = $isKhr ? (int) round($maxPrice) : (int) round($maxPrice * $exchangeRate);
            $query->where('base_price_cents', '<=', $maxCents);
        }

        if ($request->boolean('in_stock')) {
            $query->whereHas('variants', static function ($q): void {
                $q->whereRaw('stock_quantity > reserved_quantity');
            });
        }

        match ($request->input('sort')) {
            'price_low' => $query->orderBy('base_price_cents', 'asc'),
            'price_high' => $query->orderBy('base_price_cents', 'desc'),
            'newest' => $query->latest('id'),
            default => $query->orderByDesc('is_featured')->latest('id'),
        };

        $perPage = max(1, min(50, (int) $request->input('per_page', 12)));
        $paginator = $query->paginate($perPage)->withQueryString();

        $facetsBase = CatalogCacheService::remember('shop:facets', 300, static function (): array {
            $categories = Category::where('is_active', true)
                ->withCount(['products' => static fn ($q) => $q->where('is_published', true)])
                ->orderBy('sort_order')
                ->get()
                ->map(static fn (Category $c): array => [
                    'id' => (int) $c->id,
                    'name' => (string) $c->name,
                    'name_km' => $c->name_km,
                    'slug' => (string) $c->slug,
                    'products_count' => (int) $c->products_count,
                ])
                ->values()
                ->all();

            $allPublishedCount = Product::where('is_published', true)->count();

            $rawSizes = ProductVariant::query()
                ->whereHas('product', static fn ($q) => $q->where('is_published', true))
                ->distinct()
                ->pluck('size')
                ->filter()
                ->values()
                ->all();

            $availableSizes = collect($rawSizes)->sortBy(static function ($s): int {
                $upper = strtoupper(trim((string) $s));

                return self::SIZE_WEIGHTS[$upper] ?? 50;
            })->values()->all();

            $availableColors = ProductVariant::query()
                ->select('color', 'color_code')
                ->whereHas('product', static fn ($q) => $q->where('is_published', true))
                ->whereNotNull('color')
                ->where('color', '!=', '')
                ->get()
                ->unique('color')
                ->map(static fn (ProductVariant $v): array => [
                    'color' => (string) $v->color,
                    'color_code' => (string) ($v->color_code ?: '#111111'),
                ])
                ->values()
                ->all();

            return [
                'all_products_count' => $allPublishedCount,
                'categories' => $categories,
                'available_sizes' => $availableSizes,
                'available_colors' => $availableColors,
            ];
        });

        $activeFilterCount = collect([
            $request->filled('q'),
            $request->filled('category'),
            $request->filled('size'),
            $request->filled('color'),
            $request->filled('min_price') || $request->filled('max_price'),
            $request->boolean('in_stock'),
            $request->filled('sort') && $request->input('sort') !== 'featured',
        ])->filter()->count();

        $categoryFacets = array_map(static fn (array $c): array => [
            'id' => (int) $c['id'],
            'name' => ($locale === 'km' && ! empty($c['name_km'])) ? (string) $c['name_km'] : (string) $c['name'],
            'slug' => (string) $c['slug'],
            'products_count' => (int) $c['products_count'],
        ], $facetsBase['categories']);

        return response()->json([
            'data' => ProductCardResource::collection($paginator->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
            'facets' => [
                'all_products_count' => $facetsBase['all_products_count'],
                'categories' => $categoryFacets,
                'available_sizes' => $facetsBase['available_sizes'],
                'available_colors' => $facetsBase['available_colors'],
                'active_filter_count' => $activeFilterCount,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
            ],
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));
        $locale = (string) ($request->attributes->get('api_locale') ?? app()->getLocale());
        $currency = CurrencyService::current();

        if ($this->firebaseApi->isEnabled()) {
            $firebaseSuggest = $this->firebaseApi->searchSuggest($search, $locale, $currency);
            if ($firebaseSuggest !== null) {
                return response()->json($firebaseSuggest);
            }
        }

        if ($search === '') {
            $popularCategories = Category::where('is_active', true)
                ->orderBy('sort_order')
                ->take(4)
                ->get()
                ->map(static fn (Category $c): array => [
                    'name' => $c->localized_name,
                    'slug' => (string) $c->slug,
                ])
                ->values()
                ->all();

            return response()->json([
                'query' => '',
                'popular_searches' => [
                    'Heavyweight Boxy Tee',
                    'Hoodies & Outerwear',
                    'Raw Edge Tee',
                    'Tactical Cargo',
                    'Oversized',
                ],
                'categories' => $popularCategories,
                'products' => [],
                'total' => 0,
            ]);
        }

        $terms = array_filter(preg_split('/\s+/', $search) ?: []);

        $productQuery = Product::where('is_published', true)
            ->with(['category', 'images', 'variants']);

        $productQuery->where(static function ($q) use ($terms): void {
            foreach ($terms as $term) {
                $q->where(static function ($sub) use ($term): void {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('name_km', 'like', "%{$term}%")
                        ->orWhere('tagline', 'like', "%{$term}%")
                        ->orWhere('tagline_km', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('sku_prefix', 'like', "%{$term}%")
                        ->orWhereHas('category', static function ($cq) use ($term): void {
                            $cq->where('name', 'like', "%{$term}%")
                                ->orWhere('name_km', 'like', "%{$term}%")
                                ->orWhere('slug', 'like', "%{$term}%");
                        })
                        ->orWhereHas('variants', static function ($vq) use ($term): void {
                            $vq->where('color', 'like', "%{$term}%")
                                ->orWhere('sku', 'like', "%{$term}%")
                                ->orWhere('size', 'like', "%{$term}%");
                        });
                });
            }
        });

        $productQuery->orderByRaw('CASE
            WHEN name LIKE ? THEN 1
            WHEN name LIKE ? THEN 2
            ELSE 3
        END', ["{$search}%", "%{$search}%"]);

        $totalMatches = (clone $productQuery)->count();
        $products = $productQuery->take(6)->get();

        $matchingCategories = Category::where('is_active', true)
            ->where(static function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_km', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })
            ->take(3)
            ->get()
            ->map(static fn (Category $c): array => [
                'name' => $c->localized_name,
                'slug' => (string) $c->slug,
            ])
            ->values()
            ->all();

        $formattedProducts = $products->map(static fn (Product $p): array => [
            'id' => (int) $p->id,
            'name' => $p->localized_name,
            'slug' => (string) $p->slug,
            'image_url' => $p->primary_image_url,
            'price' => CurrencyService::formatCents((int) $p->base_price_cents, $currency),
            'compare_at_price' => $p->compare_at_price_cents
                ? CurrencyService::formatCents((int) $p->compare_at_price_cents, $currency)
                : null,
            'price_cents' => (int) $p->base_price_cents,
            'compare_at_price_cents' => $p->compare_at_price_cents !== null ? (int) $p->compare_at_price_cents : null,
            'category' => $p->category?->localized_name,
            'is_in_stock' => $p->is_in_stock,
        ])->values()->all();

        return response()->json([
            'query' => $search,
            'popular_searches' => [],
            'categories' => $matchingCategories,
            'products' => $formattedProducts,
            'total' => $totalMatches,
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $locale = (string) ($request->attributes->get('api_locale') ?? app()->getLocale());
        $currency = CurrencyService::current();

        if (! $request->user() && $this->firebaseApi->isEnabled()) {
            $firebaseDetail = $this->firebaseApi->productDetail($slug, $locale, $currency);
            if ($firebaseDetail !== null) {
                return response()->json($firebaseDetail);
            }

            if ($this->firebaseApi->lastWasNotFound()) {
                abort(404, 'Resource not found.');
            }
        }

        $product = Product::where('slug', $slug)
            ->where('is_published', true)
            ->with(['category', 'images', 'variants', 'reviews.user'])
            ->withReviewStats()
            ->firstOrFail();

        $relatedProducts = CatalogCacheService::remember(
            "product:{$product->id}:related:{$locale}:{$currency}",
            600,
            static function () use ($product, $request): array {
                $models = Product::where('category_id', $product->category_id)
                    ->where('id', '!=', $product->id)
                    ->where('is_published', true)
                    ->with(['category', 'images', 'variants'])
                    ->withReviewStats()
                    ->take(4)
                    ->get();

                return ProductCardResource::collection($models)->resolve($request);
            }
        );

        $user = $request->user();
        $hasPurchased = false;
        $inWishlist = false;

        if ($user) {
            $variantIds = $product->variants->pluck('id');
            if ($variantIds->isNotEmpty()) {
                $hasPurchased = $user->orders()
                    ->whereIn('status', ['paid', 'packed', 'shipped', 'delivered'])
                    ->whereHas('items', static function ($q) use ($variantIds): void {
                        $q->whereIn('product_variant_id', $variantIds);
                    })
                    ->exists();
            }

            $inWishlist = Wishlist::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->exists();
        }

        return response()->json([
            'product' => (new ProductDetailResource($product))->resolve($request),
            'has_purchased' => $hasPurchased,
            'in_wishlist' => $inWishlist,
            'related_products' => $relatedProducts,
        ]);
    }
}
