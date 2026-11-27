<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $product = Product::where('slug', $slug)
            ->where('is_published', true)
            ->with('variants:id,product_id')
            ->firstOrFail();

        $user = $request->user();
        $variantIds = $product->variants->pluck('id');

        $verifiedOrder = $variantIds->isEmpty()
            ? null
            : $user->orders()
                ->whereIn('status', ['paid', 'packed', 'shipped', 'delivered'])
                ->whereHas('items', static function ($q) use ($variantIds): void {
                    $q->whereIn('product_variant_id', $variantIds);
                })
                ->latest()
                ->first();

        $review = Review::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $user->id],
            [
                'order_id' => $verifiedOrder?->id,
                'rating' => (int) $validated['rating'],
                'title' => isset($validated['title']) ? trim((string) $validated['title']) : null,
                'body' => trim((string) $validated['body']),
                'is_verified_purchase' => (bool) $verifiedOrder,
                'is_approved' => true,
            ]
        );

        $review->load('user:id,name');
        $product->refresh();

        return response()->json([
            'message' => 'Thank you! Your review has been submitted.',
            'review' => (new ReviewResource($review))->resolve($request),
            'average_rating' => round((float) $product->average_rating, 1),
            'review_count' => (int) $product->review_count,
        ], 201);
    }
}
