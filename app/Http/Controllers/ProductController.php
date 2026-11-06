<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function show(string $slug): View
    {
        $product = Product::where('slug', $slug)
            ->where('is_published', true)
            ->with(['category', 'images', 'variants', 'reviews.user'])
            ->firstOrFail();

        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_published', true)
            ->with(['images', 'variants'])
            ->take(4)
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
