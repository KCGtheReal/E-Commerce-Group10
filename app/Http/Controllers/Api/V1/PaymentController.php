<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderDetailResource;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\BakongService;
use App\Services\OrderAccessTokenService;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly BakongService $bakongService,
        private readonly OrderService $orderService,
        private readonly OrderAccessTokenService $orderAccessTokenService
    ) {}

    public function orderPayment(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('number', $orderNumber)
            ->with(['items.variant.product.images', 'latestPayment.order'])
            ->firstOrFail();

        if (! $this->orderAccessTokenService->canAccessOrder($request, $order)) {
            return $this->forbiddenResponse();
        }

        $payment = $order->latestPayment;
        if (! $payment) {
            $generated = $this->bakongService->generatePayment($order);
            $payment = $generated['payment'];
            $order->load('latestPayment.order');
        }

        $payment->loadMissing('order');

        return response()->json([
            'order' => (new OrderDetailResource($order))->resolve($request),
            'payment' => (new PaymentResource($payment))->resolve($request),
        ]);
    }

    public function status(Request $request, Payment $payment): JsonResponse
    {
        $payment->loadMissing('order');
        $order = $payment->order;

        if (! $this->orderAccessTokenService->canAccessOrder($request, $order)) {
            return $this->forbiddenResponse();
        }

        if ($payment->status === PaymentStatus::Paid || $order->isPaid()) {
            return response()->json([
                'payment_id' => (int) $payment->id,
                'order_number' => (string) $order->number,
                'order_status' => $order->status?->value ?? (string) $order->status,
                'status' => 'paid',
                'time_remaining_seconds' => 0,
                'paid_at' => ($payment->paid_at ?? $order->paid_at)?->toIso8601String(),
                'message' => __('store.payment_status_paid'),
            ]);
        }

        $secondsSinceLastCheck = $payment->last_checked_at
            ? $payment->last_checked_at->diffInSeconds(now())
            : 99;

        if ($secondsSinceLastCheck >= 2 || $payment->isExpired()) {
            $payment->update(['last_checked_at' => now()]);
            $checkResult = $this->bakongService->checkTransaction($payment);

            if ($checkResult['paid']) {
                $this->orderService->finalizePaidOrder($payment, $checkResult['data'] ?? []);
                $payment->refresh();
                $order->refresh();

                return response()->json([
                    'payment_id' => (int) $payment->id,
                    'order_number' => (string) $order->number,
                    'order_status' => $order->status?->value ?? (string) $order->status,
                    'status' => 'paid',
                    'time_remaining_seconds' => 0,
                    'paid_at' => ($payment->paid_at ?? $order->paid_at)?->toIso8601String(),
                    'message' => __('store.payment_status_paid'),
                ]);
            }
        }

        if ($payment->isExpired()) {
            $this->orderService->expirePayment($payment);
            $payment->refresh();
            $order->refresh();

            return response()->json([
                'payment_id' => (int) $payment->id,
                'order_number' => (string) $order->number,
                'order_status' => $order->status?->value ?? (string) $order->status,
                'status' => 'expired',
                'time_remaining_seconds' => 0,
                'paid_at' => null,
                'message' => __('store.payment_status_expired'),
            ]);
        }

        return response()->json([
            'payment_id' => (int) $payment->id,
            'order_number' => (string) $order->number,
            'order_status' => $order->status?->value ?? (string) $order->status,
            'status' => $payment->status?->value ?? (string) $payment->status,
            'time_remaining_seconds' => (int) $payment->time_remaining_seconds,
            'paid_at' => null,
            'message' => null,
        ]);
    }

    public function regenerateQr(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        if (! $this->orderAccessTokenService->canAccessOrder($request, $order)) {
            return $this->forbiddenResponse();
        }

        if ($order->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Order has already been paid.',
                'code' => 'PAYMENT_ALREADY_PAID',
            ], 422);
        }

        try {
            $result = $this->orderService->regeneratePayment($order);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'INSUFFICIENT_STOCK',
                'errors' => ['order' => [$e->getMessage()]],
            ], 422);
        }

        $order->refresh()->load(['items.variant.product.images', 'latestPayment.order']);
        $payment = $result['payment']->load('order');

        return response()->json([
            'message' => 'New Bakong KHQR generated.',
            'order' => (new OrderDetailResource($order))->resolve($request),
            'payment' => (new PaymentResource($payment))->resolve($request),
        ]);
    }

    public function simulatePay(Request $request, Payment $payment): JsonResponse
    {
        if (! config('bakong.mock')) {
            return response()->json([
                'success' => false,
                'message' => 'Mock payment is disabled.',
                'code' => 'MOCK_PAYMENT_DISABLED',
            ], 403);
        }

        $payment->loadMissing('order');
        $order = $payment->order;

        if (! $this->orderAccessTokenService->canAccessOrder($request, $order)) {
            return $this->forbiddenResponse();
        }

        if (! $payment->isPaid() && ! $order->isPaid()) {
            $this->bakongService->forceMockPaid($payment);
            $checkResult = $this->bakongService->checkTransaction($payment);

            if ($checkResult['paid']) {
                $this->orderService->finalizePaidOrder($payment, $checkResult['data'] ?? []);
            }
        }

        $order->refresh()->load(['items.variant.product.images', 'latestPayment.order']);
        $payment->refresh()->load('order');

        return response()->json([
            'success' => true,
            'message' => 'Mock payment completed successfully!',
            'order' => (new OrderDetailResource($order))->resolve($request),
            'payment' => (new PaymentResource($payment))->resolve($request),
        ]);
    }

    public function invoice(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('number', $orderNumber)
            ->with(['items.variant.product.images', 'latestPayment.order'])
            ->firstOrFail();

        if (! $this->orderAccessTokenService->canAccessOrder($request, $order)) {
            return $this->forbiddenResponse();
        }

        $payment = $order->latestPayment;

        return response()->json([
            'invoice' => [
                'invoice_number' => 'INV-'.$order->number,
                'issued_date' => $order->created_at?->format('F d, Y') ?? now()->format('F d, Y'),
                'payment_method_label' => 'Bakong KHQR',
                'store_info' => [
                    'name' => (string) Setting::get('store_name', 'RESET Store'),
                    'tagline' => (string) Setting::get('store_tagline', 'Start fresh.'),
                    'address' => (string) Setting::get('store_address', 'Phnom Penh, Cambodia'),
                    'phone' => (string) Setting::get('store_phone', '+855 23 999 123'),
                    'email' => (string) Setting::get('store_email', 'support@reset.store'),
                ],
                'order' => (new OrderDetailResource($order))->resolve($request),
                'payment' => $payment ? (new PaymentResource($payment))->resolve($request) : null,
            ],
        ]);
    }

    private function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized access to this order.',
            'code' => 'FORBIDDEN',
        ], 403);
    }
}
