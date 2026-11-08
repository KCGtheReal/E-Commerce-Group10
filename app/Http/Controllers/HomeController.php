<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index(): View
    {
        $categories = Cache::remember('home_categories', 3600, function () {
            return Category::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });

        $featuredProducts = Cache::remember('home_featured_products', 600, function () {
            return Product::where('is_published', true)
                ->where('is_featured', true)
                ->with(['category', 'images', 'variants'])
                ->take(8)
                ->get();
        });

        $newArrivals = Cache::remember('home_new_arrivals', 600, function () {
            return Product::where('is_published', true)
                ->where('is_new_arrival', true)
                ->with(['category', 'images', 'variants'])
                ->take(8)
                ->get();
        });

        $bestSellers = Cache::remember('home_best_sellers', 600, function () {
            return Product::where('is_published', true)
                ->where('is_best_seller', true)
                ->with(['category', 'images', 'variants'])
                ->take(8)
                ->get();
        });

        return view('storefront.home', compact('categories', 'featuredProducts', 'newArrivals', 'bestSellers'));
    }
}
