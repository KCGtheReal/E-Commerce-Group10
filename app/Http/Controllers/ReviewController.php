<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, string $slug): RedirectResponse
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        // Check if verified buyer
        $verifiedOrder = $user->orders()
            ->whereIn('status', ['paid', 'packed', 'shipped', 'delivered'])
            ->whereHas('items', function ($q) use ($product) {
                $q->whereIn('product_variant_id', $product->variants->pluck('id'));
            })
            ->latest()
            ->first();

        Review::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $user->id],
            [
                'order_id' => $verifiedOrder?->id,
                'rating' => (int) $request->input('rating'),
                'title' => $request->input('title'),
                'body' => $request->input('body'),
                'is_verified_purchase' => (bool) $verifiedOrder,
                'is_approved' => true,
            ]
        );

        return redirect()->back()->with('success', 'Thank you! Your review has been submitted.');
    }
}
