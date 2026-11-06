<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\BakongService;
use App\Services\OrderService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use KHQR\BakongKHQR;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PaymentController extends Controller
{
    public function __construct(
        private readonly BakongService $bakongService,
        private readonly OrderService $orderService
    ) {}

    public function show(Request $request, string $orderNumber): View|RedirectResponse
    {
        $order = Order::where('number', $orderNumber)->with(['items', 'latestPayment'])->firstOrFail();

        // Authorize: logged in user who owns the order, OR active order session for guests, OR admin
        $this->authorizeOrderAccess($order);

        // If order already paid, redirect to success
        if ($order->isPaid()) {
            return redirect()->route('payment.success', ['order' => $order->number]);
        }

        $payment = $order->latestPayment;

        // Auto-heal: If no payment exists or if the existing QR string is malformed/missing expiration, generate fresh payment
        $isQrValid = $payment
            && ! empty($payment->qr_string)
            && str_contains($payment->qr_string, '99340013')
            && ! str_contains($payment->qr_string, '#')
            && ! $payment->isExpired()
            && BakongKHQR::verify($payment->qr_string)->isValid;

        if (! $payment || ! $isQrValid) {
            $result = $this->bakongService->generatePayment($order);
            $payment = $result['payment'];
            $qrSvg = $result['qr_svg'];
        } else {
            // Re-render SVG with high contrast medium error correction for camera readability
            $qrSvg = (string) QrCode::format('svg')
                ->size(280)
                ->errorCorrection('M')
                ->margin(2)
                ->generate($payment->qr_string);
        }

        // Deep link for mobile banking apps
        $deeplinkUrl = 'bakong://qr?data='.urlencode($payment->qr_string);

        return view('storefront.payment', compact('order', 'payment', 'qrSvg', 'deeplinkUrl'));
    }

    /**
     * Polling endpoint to check transaction status (GET /payments/{payment}/status).
     */
    public function status(Request $request, Payment $payment): JsonResponse
    {
        $order = $payment->order;
        $this->authorizeOrderAccess($order);

        // 1. If already paid, return instantly
        if ($payment->status === PaymentStatus::Paid || $order->isPaid()) {
            return response()->json([
                'status' => 'paid',
                'redirect_url' => route('payment.success', ['order' => $order->number]),
            ]);
        }

        // 2. Perform authoritative gateway check before evaluating expiration (prevents premature expiration of late-settling payments)
        $secondsSinceLastCheck = $payment->last_checked_at
            ? $payment->last_checked_at->diffInSeconds(now())
            : 99;

        if ($secondsSinceLastCheck >= 2 || $payment->isExpired()) {
            $payment->update(['last_checked_at' => now()]);
            $checkResult = $this->bakongService->checkTransaction($payment);

            if ($checkResult['paid']) {
                $this->orderService->finalizePaidOrder($payment, $checkResult['data'] ?? []);

                return response()->json([
                    'status' => 'paid',
                    'redirect_url' => route('payment.success', ['order' => $order->number]),
                ]);
            }
        }

        // 3. Only if gateway confirmed NOT paid and time has elapsed, expire the payment
        if ($payment->isExpired()) {
            $this->orderService->expirePayment($payment);

            return response()->json([
                'status' => 'expired',
                'message' => __('store.payment_status_expired'),
            ]);
        }

        return response()->json([
            'status' => $payment->status->value,
            'time_remaining_seconds' => $payment->time_remaining_seconds,
        ]);
    }

    /**
     * Regenerate a new KHQR for an expired payment.
     */
    public function regenerate(Request $request, string $orderNumber): RedirectResponse
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $this->authorizeOrderAccess($order);

        try {
            $this->orderService->regeneratePayment($order);

            return redirect()->route('payment.show', ['order' => $order->number])
                ->with('success', 'New Bakong KHQR generated.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Simulate successful payment in test / mock mode.
     */
    public function simulatePay(Request $request, Payment $payment): JsonResponse|RedirectResponse
    {
        if (! config('bakong.mock')) {
            abort(403, 'Mock payment is disabled.');
        }

        $this->authorizeOrderAccess($payment->order);
        $this->bakongService->forceMockPaid($payment);

        $checkResult = $this->bakongService->checkTransaction($payment);
        if ($checkResult['paid']) {
            $this->orderService->finalizePaidOrder($payment, $checkResult['data'] ?? []);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('payment.success', ['order' => $payment->order->number]),
            ]);
        }

        return redirect()->route('payment.success', ['order' => $payment->order->number])
            ->with('success', 'Mock payment completed successfully!');
    }

    /**
     * Download QR code as SVG file.
     */
    public function downloadQr(Payment $payment): Response
    {
        $this->authorizeOrderAccess($payment->order);

        $svg = (string) QrCode::format('svg')
            ->size(500)
            ->errorCorrection('M')
            ->margin(2)
            ->generate($payment->qr_string);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="RESET-KHQR-'.$payment->order->number.'.svg"',
        ]);
    }

    /**
     * Order success celebration page.
     */
    public function success(Request $request, string $orderNumber): View
    {
        $order = Order::where('number', $orderNumber)->with(['items', 'latestPayment'])->firstOrFail();
        $this->authorizeOrderAccess($order);

        return view('storefront.order-success', compact('order'));
    }

    private function authorizeOrderAccess(Order $order): void
    {
        if (Auth::check() && (Auth::user()->isAdmin() || Auth::id() === $order->user_id)) {
            return;
        }

        if (session('active_order_'.$order->number) === true) {
            return;
        }

        // Guests with matching email in session
        if (session('checkout_email') === $order->email) {
            return;
        }

        abort(403, 'Unauthorized access to this order.');
    }
}
