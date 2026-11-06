<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index(): View
    {
        $products = Auth::user()->wishlists()->with('product.images', 'product.variants')->get()->pluck('product');

        return view('accounts.wishlist', compact('products'));
    }

    public function toggle(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        if (! Auth::check()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'require_login' => true, 'message' => 'Please sign in to save items to your wishlist.'], 401);
            }

            return redirect()->route('login')->with('info', 'Please sign in to save to your wishlist.');
        }

        $userId = Auth::id();
        $productId = (int) $request->input('product_id');

        $existing = Wishlist::where('user_id', $userId)->where('product_id', $productId)->first();

        if ($existing) {
            $existing->delete();
            $added = false;
            $message = 'Item removed from your wishlist.';
        } else {
            Wishlist::create(['user_id' => $userId, 'product_id' => $productId]);
            $added = true;
            $message = 'Item added to your wishlist.';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'added' => $added,
                'message' => $message,
                'total_wishlists' => Auth::user()->wishlists()->count(),
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}
