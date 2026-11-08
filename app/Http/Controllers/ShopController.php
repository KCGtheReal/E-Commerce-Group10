<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CurrencyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::where('is_published', true)
            ->with(['category', 'images', 'variants']);

        // 1. Smart Multi-Term Search Query
        if ($search = trim((string) $request->input('q'))) {
            $terms = array_filter(preg_split('/\s+/', $search) ?: []);

            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($sub) use ($term) {
                        $sub->where('name', 'like', "%{$term}%")
                            ->orWhere('name_km', 'like', "%{$term}%")
                            ->orWhere('tagline', 'like', "%{$term}%")
                            ->orWhere('tagline_km', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%")
                            ->orWhere('sku_prefix', 'like', "%{$term}%")
                            ->orWhereHas('category', function ($cq) use ($term) {
                                $cq->where('name', 'like', "%{$term}%")
                                    ->orWhere('name_km', 'like', "%{$term}%")
                                    ->orWhere('slug', 'like', "%{$term}%");
                            })
                            ->orWhereHas('variants', function ($vq) use ($term) {
                                $vq->where('color', 'like', "%{$term}%")
                                    ->orWhere('sku', 'like', "%{$term}%")
                                    ->orWhere('size', 'like', "%{$term}%");
                            });
                    });
                }
            });

            // Prioritize matches that start with or contain query
            $query->orderByRaw('CASE 
                WHEN name LIKE ? THEN 1 
                WHEN name LIKE ? THEN 2 
                ELSE 3 
            END', ["{$search}%", "%{$search}%"]);
        }

        // 2. Category Filter
        if ($categorySlug = $request->input('category')) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        // 3. Size Filter
        if ($size = $request->input('size')) {
            $sizes = is_array($size) ? $size : [$size];
            $query->whereHas('variants', function ($q) use ($sizes) {
                $q->whereIn('size', $sizes);
            });
        }

        // 4. Color Filter
        if ($color = $request->input('color')) {
            $query->whereHas('variants', function ($q) use ($color) {
                $q->where('color', $color);
            });
        }

        // 5. Price Range Filter (Respects active currency)
        $isKhr = CurrencyService::current() === 'KHR';
        $exchangeRate = CurrencyService::exchangeRate();

        if ($minPrice = $request->input('min_price')) {
            $minCents = $isKhr ? (int) $minPrice : (int) round((float) $minPrice * $exchangeRate);
            $query->where('base_price_cents', '>=', $minCents);
        }
        if ($maxPrice = $request->input('max_price')) {
            $maxCents = $isKhr ? (int) $maxPrice : (int) round((float) $maxPrice * $exchangeRate);
            $query->where('base_price_cents', '<=', $maxCents);
        }

        // 6. In Stock Filter
        if ($request->boolean('in_stock')) {
            $query->whereHas('variants', function ($q) {
                $q->whereRaw('stock_quantity > reserved_quantity');
            });
        }

        // 7. Sorting
        match ($request->input('sort')) {
            'price_low' => $query->orderBy('base_price_cents', 'asc'),
            'price_high' => $query->orderBy('base_price_cents', 'desc'),
            'newest' => $query->latest('id'),
            default => $query->orderByDesc('is_featured')->latest('id'),
        };

        $products = $query->paginate(12)->withQueryString();

        // Data for sidebar filters
        $categories = Category::where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_published', true)])
            ->orderBy('sort_order')
            ->get();

        $allPublishedProductsCount = Product::where('is_published', true)->count();

        $rawSizes = ProductVariant::distinct()->pluck('size')->filter()->values()->all();
        $sizeWeights = [
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
        $availableSizes = collect($rawSizes)->sortBy(function ($s) use ($sizeWeights) {
            $upper = strtoupper(trim((string) $s));

            return $sizeWeights[$upper] ?? 50;
        })->values()->all();

        $availableColors = ProductVariant::select('color', 'color_code')
            ->whereNotNull('color')
            ->where('color', '!=', '')
            ->get()
            ->unique('color')
            ->values();

        $activeFilterCount = collect([
            $request->filled('category'),
            $request->filled('size'),
            $request->filled('color'),
            $request->filled('min_price') || $request->filled('max_price'),
            $request->boolean('in_stock'),
            $request->filled('sort') && $request->input('sort') !== 'featured',
        ])->filter()->count();

        return view('storefront.shop', compact(
            'products',
            'categories',
            'availableSizes',
            'availableColors',
            'allPublishedProductsCount',
            'activeFilterCount'
        ));
    }

    /**
     * Predictive instant search autocomplete suggestions.
     */
    public function suggest(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));

        if ($search === '') {
            $popularCategories = Category::where('is_active', true)
                ->orderBy('sort_order')
                ->take(4)
                ->get()
                ->map(fn (Category $c) => [
                    'name' => $c->localized_name,
                    'slug' => $c->slug,
                    'url' => route('shop.index', ['category' => $c->slug]),
                ]);

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
                'view_all_url' => route('shop.index'),
            ]);
        }

        $terms = array_filter(preg_split('/\s+/', $search) ?: []);

        $productQuery = Product::where('is_published', true)
            ->with(['category', 'images', 'variants']);

        $productQuery->where(function ($q) use ($terms) {
            foreach ($terms as $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('name_km', 'like', "%{$term}%")
                        ->orWhere('tagline', 'like', "%{$term}%")
                        ->orWhere('tagline_km', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('sku_prefix', 'like', "%{$term}%")
                        ->orWhereHas('category', function ($cq) use ($term) {
                            $cq->where('name', 'like', "%{$term}%")
                                ->orWhere('name_km', 'like', "%{$term}%")
                                ->orWhere('slug', 'like', "%{$term}%");
                        })
                        ->orWhereHas('variants', function ($vq) use ($term) {
                            $vq->where('color', 'like', "%{$term}%")
                                ->orWhere('sku', 'like', "%{$term}%")
                                ->orWhere('size', 'like', "%{$term}%");
                        });
                });
            }
        });

        // Relevance ordering
        $productQuery->orderByRaw('CASE 
            WHEN name LIKE ? THEN 1 
            WHEN name LIKE ? THEN 2 
            ELSE 3 
        END', ["{$search}%", "%{$search}%"]);

        $totalMatches = (clone $productQuery)->count();
        $products = $productQuery->take(6)->get();

        $matchingCategories = Category::where('is_active', true)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_km', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })
            ->take(3)
            ->get()
            ->map(fn (Category $c) => [
                'name' => $c->localized_name,
                'slug' => $c->slug,
                'url' => route('shop.index', ['category' => $c->slug]),
            ]);

        $formattedProducts = $products->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->localized_name,
            'slug' => $p->slug,
            'url' => route('product.show', $p->slug),
            'image_url' => $p->primary_image_url,
            'price' => $p->formatted_price,
            'compare_at_price' => $p->formatted_compare_at_price,
            'category' => $p->category?->localized_name,
            'is_in_stock' => $p->is_in_stock,
        ]);

        return response()->json([
            'query' => $search,
            'products' => $formattedProducts,
            'categories' => $matchingCategories,
            'total' => $totalMatches,
            'view_all_url' => route('shop.index', ['q' => $search]),
        ]);
    }
}
