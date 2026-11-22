<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Services\CartService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function index(Request $request): View
    {
        $cart = $this->cartService->getCart();
        $totals = $this->cartService->calculateTotals();

        return view('storefront.cart', compact('cart', 'totals'));
    }

    public function add(AddToCartRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $this->cartService->addItem(
                (int) $request->input('product_variant_id'),
                (int) $request->input('quantity', 1)
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('store.cart_item_added'),
                    'cart' => $this->getCartJsonData(),
                ]);
            }

            return redirect()->back()->with('success', __('store.cart_item_added'));
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, int|string $item): JsonResponse|RedirectResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        try {
            $this->cartService->updateItem((int) $item, (int) $request->input('quantity'));

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('store.cart_item_updated'),
                    'cart' => $this->getCartJsonData(),
                ]);
            }

            return redirect()->back()->with('success', __('store.cart_item_updated'));
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function remove(Request $request, int|string $item): JsonResponse|RedirectResponse
    {
        $this->cartService->removeItem((int) $item);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('store.cart_item_removed'),
                'cart' => $this->getCartJsonData(),
            ]);
        }

        return redirect()->back()->with('success', __('store.cart_item_removed'));
    }

    public function applyDiscount(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $discount = $this->cartService->applyDiscount((string) $request->input('code'));

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Discount code {$discount->code} applied!",
                    'cart' => $this->getCartJsonData(),
                ]);
            }

            return redirect()->back()->with('success', "Discount code {$discount->code} applied!");
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function removeDiscount(Request $request): JsonResponse|RedirectResponse
    {
        $this->cartService->removeDiscount();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Discount removed.',
                'cart' => $this->getCartJsonData(),
            ]);
        }

        return redirect()->back()->with('success', 'Discount removed.');
    }

    public function data(): JsonResponse
    {
        return response()->json($this->getCartJsonData());
    }

    private function getCartJsonData(): array
    {
        $cart = $this->cartService->getCart();
        $totals = $this->cartService->calculateTotals();

        $items = $cart->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_name' => $item->variant->product->localized_name,
                'variant_label' => $item->variant->label,
                'image_url' => $item->variant->product->primary_image_url,
                'price_cents' => $item->variant->price_cents,
                'formatted_price' => $item->variant->formatted_price,
                'quantity' => $item->quantity,
                'max_stock' => $item->variant->available_stock,
                'subtotal_cents' => $item->subtotal_cents,
                'formatted_subtotal' => $item->formatted_subtotal,
            ];
        });

        return [
            'item_count' => $cart->item_count,
            'items' => $items,
            'currency' => $totals['currency'] ?? 'USD',
            'subtotal_cents' => $totals['subtotal_cents'],
            'formatted_subtotal' => $totals['formatted_subtotal'],
            'discount_cents' => $totals['discount_cents'],
            'formatted_discount' => $totals['formatted_discount'],
            'discount_code' => $cart->discount?->code,
            'shipping_fee_cents' => $totals['shipping_fee_cents'],
            'formatted_shipping' => $totals['formatted_shipping'],
            'total_cents' => $totals['total_cents'],
            'formatted_total' => $totals['formatted_total'],
            'free_shipping_qualified' => $totals['free_shipping_qualified'],
            'free_shipping_remaining_formatted' => $totals['free_shipping_remaining_formatted'],
        ];
    }
}
