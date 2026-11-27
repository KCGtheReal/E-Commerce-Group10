<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $totals = $this->computeTotals($request, $cart);

        return response()->json([
            'cart' => (new CartResource($cart, $totals))->resolve($request),
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.CartService::MAX_ITEM_QUANTITY],
            'province' => ['nullable', 'string'],
            'shipping_method' => ['nullable', 'string', 'in:standard,express'],
        ]);

        $cart = $this->resolveCart($request);

        try {
            $this->cartService->addItemToCart(
                $cart,
                (int) $validated['product_variant_id'],
                (int) $validated['quantity']
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'INSUFFICIENT_STOCK',
                'errors' => ['quantity' => [$e->getMessage()]],
            ], 422);
        }

        $cart->load(['items.variant.product.images', 'discount']);
        $totals = $this->computeTotals($request, $cart);

        return response()->json([
            'message' => __('store.cart_item_added'),
            'cart' => (new CartResource($cart, $totals))->resolve($request),
        ]);
    }

    public function updateItem(Request $request, int $item): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:'.CartService::MAX_ITEM_QUANTITY],
            'province' => ['nullable', 'string'],
            'shipping_method' => ['nullable', 'string', 'in:standard,express'],
        ]);

        $cart = $this->resolveCart($request);

        try {
            $this->cartService->updateItemInCart($cart, $item, (int) $validated['quantity']);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found.',
                'code' => 'NOT_FOUND',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'INSUFFICIENT_STOCK',
                'errors' => ['quantity' => [$e->getMessage()]],
            ], 422);
        }

        $cart->load(['items.variant.product.images', 'discount']);
        $totals = $this->computeTotals($request, $cart);

        return response()->json([
            'message' => __('store.cart_item_updated'),
            'cart' => (new CartResource($cart, $totals))->resolve($request),
        ]);
    }

    public function removeItem(Request $request, int $item): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $removed = $this->cartService->removeItemFromCart($cart, $item);

        if (! $removed) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found.',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $cart->load(['items.variant.product.images', 'discount']);
        $totals = $this->computeTotals($request, $cart);

        return response()->json([
            'message' => __('store.cart_item_removed'),
            'cart' => (new CartResource($cart, $totals))->resolve($request),
        ]);
    }

    public function applyDiscount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'province' => ['nullable', 'string'],
            'shipping_method' => ['nullable', 'string', 'in:standard,express'],
        ]);

        $cart = $this->resolveCart($request);

        try {
            $discount = $this->cartService->applyDiscountToCart($cart, (string) $validated['code']);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'INVALID_DISCOUNT',
                'errors' => ['code' => [$e->getMessage()]],
            ], 422);
        }

        $cart->load(['items.variant.product.images', 'discount']);
        $totals = $this->computeTotals($request, $cart);

        return response()->json([
            'message' => "Discount code {$discount->code} applied!",
            'cart' => (new CartResource($cart, $totals))->resolve($request),
        ]);
    }

    public function removeDiscount(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $this->cartService->removeDiscountFromCart($cart);

        $cart->load(['items.variant.product.images', 'discount']);
        $totals = $this->computeTotals($request, $cart);

        return response()->json([
            'message' => __('store.promo_code_removed'),
            'cart' => (new CartResource($cart, $totals))->resolve($request),
        ]);
    }

    private function resolveCart(Request $request): Cart
    {
        $token = $request->header('X-Cart-Token');

        /** @var Cart $cart */
        $cart = $this->cartService->resolveApiCart(
            $request->user(),
            is_string($token) ? $token : null,
            true
        );

        return $cart;
    }

    /**
     * @return array<string, mixed>
     */
    private function computeTotals(Request $request, Cart $cart): array
    {
        $province = $request->input('province') ?? $request->query('province');
        $shippingMethod = (string) ($request->input('shipping_method') ?? $request->query('shipping_method', 'standard'));

        return $this->cartService->calculateTotals(
            is_string($province) ? $province : null,
            $shippingMethod,
            $cart
        );
    }
}
