<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderDetailResource;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Services\CartService;
use App\Services\OrderAccessTokenService;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
        private readonly OrderAccessTokenService $orderAccessTokenService
    ) {}

    public function store(Request $request): JsonResponse
    {
        if ($request->has('email')) {
            $request->merge([
                'email' => strtolower(trim((string) $request->input('email'))),
            ]);
        }

        $validated = $request->validate([
            'name' => ['required_without:first_name', 'nullable', 'string', 'max:200'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:20'],
            'province' => ['required', 'string'],
            'district' => ['nullable', 'string', 'max:150'],
            'commune' => ['nullable', 'string', 'max:150'],
            'street_address' => ['required', 'string', 'max:255'],
            'shipping_method' => ['required', 'string', 'in:standard,express'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (! empty($validated['name']) && empty($validated['first_name'])) {
            $parts = explode(' ', trim((string) $validated['name']), 2);
            $validated['first_name'] = $parts[0];
            $validated['last_name'] = $parts[1] ?? '';
        }

        if (
            $validated['shipping_method'] === 'express'
            && $validated['province'] !== CartService::EXPRESS_SHIPPING_PROVINCE
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Express delivery is only available in Phnom Penh.',
                'code' => 'VALIDATION_ERROR',
                'errors' => [
                    'shipping_method' => ['Express delivery is only available in Phnom Penh.'],
                ],
            ], 422);
        }

        $user = $request->user();
        $cartToken = $request->header('X-Cart-Token');
        $cart = $this->cartService->resolveApiCart(
            $user,
            is_string($cartToken) ? $cartToken : null,
            false
        );

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('store.cart_empty'),
                'code' => 'VALIDATION_ERROR',
                'errors' => [
                    'cart' => [__('store.cart_empty')],
                ],
            ], 422);
        }

        $lock = Cache::lock('checkout_lock_cart_'.$cart->id, 15);
        if (! $lock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout is already being processed. Please wait.',
                'code' => 'CHECKOUT_LOCKED',
            ], 422);
        }

        try {
            $result = $this->orderService->createOrderFromCart($cart, $validated, $user);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'INSUFFICIENT_STOCK',
                'errors' => [
                    'cart' => [$e->getMessage()],
                ],
            ], 422);
        } finally {
            $lock->release();
        }

        $order = $result['order']->load(['items.variant.product.images', 'latestPayment.order']);
        $payment = $result['payment']->load('order');
        $orderAccessToken = $this->orderAccessTokenService->issue($order);

        return response()->json([
            'message' => 'Order placed. Please scan the Bakong KHQR code to pay.',
            'order' => (new OrderDetailResource($order))->resolve($request),
            'payment' => (new PaymentResource($payment))->resolve($request),
            'order_access_token' => $orderAccessToken,
        ], 201);
    }
}
