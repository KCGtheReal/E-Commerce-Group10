<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function show(string $slug): View
    {
        $product = Product::where('slug', $slug)
            ->where('is_published', true)
            ->with(['category', 'images', 'variants', 'reviews.user'])
            ->firstOrFail();

        $relatedIds = Cache::remember("cat_{$product->category_id}_related_prod_{$product->id}_ids", 600, static function () use ($product): array {
            return Product::where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->where('is_published', true)
                ->limit(4)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
        });

        $relatedProducts = $relatedIds === []
            ? collect()
            : Product::whereIn('id', $relatedIds)
                ->where('is_published', true)
                ->with(['images', 'variants'])
                ->get();

        // Check if authenticated user has already purchased this item (for review eligibility)
        $hasPurchased = false;
        if (auth()->check()) {
            $hasPurchased = auth()->user()->orders()
                ->whereIn('status', ['paid', 'packed', 'shipped', 'delivered'])
                ->whereHas('items', function ($q) use ($product) {
                    $q->whereIn('product_variant_id', $product->variants->pluck('id'));
                })
                ->exists();
        }

        return view('storefront.product', compact('product', 'relatedProducts', 'hasPurchased'));
    }
}
