<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductCardResource;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $products = $user->wishlists()
            ->with(['product' => static fn ($q) => $q->with(['category', 'images', 'variants'])->withReviewStats()])
            ->latest()
            ->get()
            ->pluck('product')
            ->filter(static fn (?Product $product): bool => $product !== null && (bool) $product->is_published)
            ->values();

        return response()->json([
            'items' => ProductCardResource::collection($products)->resolve($request),
            'total' => $products->count(),
        ]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $user = $request->user();
        $productId = (int) $validated['product_id'];

        $existing = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            $added = false;
            $message = 'Item removed from your wishlist.';
        } else {
            Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);
            $added = true;
            $message = 'Item added to your wishlist.';
        }

        return response()->json([
            'added' => $added,
            'product_id' => $productId,
            'message' => $message,
            'total_wishlists' => $user->wishlists()->count(),
        ]);
    }
}
