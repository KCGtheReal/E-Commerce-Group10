<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $featuredProducts = Product::where('is_published', true)
            ->where('is_featured', true)
            ->with(['category', 'images', 'variants'])
            ->take(8)
            ->get();

        $newArrivals = Product::where('is_published', true)
            ->where('is_new_arrival', true)
            ->with(['category', 'images', 'variants'])
            ->take(8)
            ->get();

        $bestSellers = Product::where('is_published', true)
            ->where('is_best_seller', true)
            ->with(['category', 'images', 'variants'])
            ->take(8)
            ->get();

        return view('storefront.home', compact('categories', 'featuredProducts', 'newArrivals', 'bestSellers'));
    }
}
