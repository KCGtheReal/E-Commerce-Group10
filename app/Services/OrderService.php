<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderPlacedMail;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class OrderService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly BakongService $bakongService,
        private readonly TelegramNotificationService $telegramService
    ) {}

    /**
     * Create an Order from active cart with server-side recalculation and stock reservation.
     *
     * @param  array<string, mixed>  $data
     * @return array{order: Order, payment: Payment, qr_svg: string, qr_string: string, md5: string}
     *
     * @throws Exception
     */
    public function createOrderFromCart(Cart $cart, array $data, ?User $user = null): array
    {
        return DB::transaction(function () use ($cart, $data, $user) {
            /** @var Cart $lockedCart */
            $lockedCart = Cart::with('discount')->where('id', $cart->id)->lockForUpdate()->firstOrFail();

            $cartItems = $lockedCart->items()->with('variant.product')->get();

            if ($cartItems->isEmpty()) {
                throw new Exception('Your shopping cart is empty.');
            }

            $shippingMethod = strtolower(trim((string) ($data['shipping_method'] ?? 'standard')));
            if (! in_array($shippingMethod, ['standard', 'express'], true)) {
                $shippingMethod = 'standard';
            }

            if ($shippingMethod === 'express' && $data['province'] !== CartService::EXPRESS_SHIPPING_PROVINCE) {
                throw new Exception('Express delivery is only available in Phnom Penh.');
            }

            // 1. Verify live stock for each item before reservation
            $touchedProductIds = [];
            foreach ($cartItems as $item) {
                /** @var ProductVariant $variant */
                $variant = ProductVariant::where('id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($item->quantity > $variant->available_stock) {
                    throw new Exception("Product {$variant->product->name} ({$variant->label}) only has {$variant->available_stock} units left.");
                }

                $touchedProductIds[] = (int) $variant->product_id;
            }

            // 2. Recompute all figures server-side (NEVER trust client amounts)
            $totals = $this->cartService->calculateTotals($data['province'], $shippingMethod, $lockedCart);
            $subtotalCents = (int) $totals['subtotal_cents'];
            $discountCents = (int) $totals['discount_cents'];
            $shippingFeeCents = (int) $totals['shipping_fee_cents'];
            $totalCents = (int) $totals['total_cents'];

            // 3. Currency and Exchange Rate Calculation
            $currency = CurrencyService::current();
            $exchangeRate = CurrencyService::exchangeRate();
            $totalChargedAmount = CurrencyService::convertCents($totalCents, $currency);

            // 4. Generate unique order number: RST-YYYY-XXXXX
            $year = date('Y');
            $uniqueSuffix = strtoupper(Str::random(5));
            $orderNumber = "RST-{$year}-{$uniqueSuffix}";

            // 5. Create Order record (status: pending_payment)
            $fullName = trim((string) ($data['name'] ?? trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''))));
            $parts = explode(' ', $fullName, 2);
            $firstName = ! empty($data['first_name']) ? $data['first_name'] : ($parts[0] ?? $fullName);
            $lastName = ! empty($data['last_name']) ? $data['last_name'] : ($parts[1] ?? '');

            $shippingAddress = [
                'name' => $fullName,
                'full_name' => $fullName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $data['phone'],
                'province' => $data['province'],
                'district' => $data['district'] ?? null,
                'commune' => $data['commune'] ?? null,
                'street_address' => $data['street_address'],
            ];

            $order = Order::create([
                'number' => $orderNumber,
                'user_id' => $user?->id,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'shipping_address' => $shippingAddress,
                'billing_address' => $shippingAddress,
                'subtotal_cents' => $subtotalCents,
                'discount_cents' => $discountCents,
                'discount_code' => $lockedCart->discount?->code,
                'shipping_fee_cents' => $shippingFeeCents,
                'shipping_method' => $shippingMethod,
                'total_cents' => $totalCents,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'total_charged_currency_amount' => $totalChargedAmount,
                'status' => OrderStatus::PendingPayment,
                'customer_notes' => $data['notes'] ?? null,
            ]);

            // 6. Create Order Items and Reserve Inventory
            foreach ($cartItems as $item) {
                $variant = $item->variant;
                $product = $variant->product;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'product_name_km' => $product->name_km,
                    'variant_label' => $variant->label,
                    'sku' => $variant->sku,
                    'price_cents' => $variant->price_cents,
                    'quantity' => $item->quantity,
                    'subtotal_cents' => $item->subtotal_cents,
                ]);

                // Reserve inventory immediately (increment does not fire Eloquent saved events)
                $variant->increment('reserved_quantity', $item->quantity);
            }

            // 7. Increment discount usage count
            $discount = $lockedCart->discount;
            if ($discount) {
                $discount->increment('used_count');
            }

            // 8. Generate dynamic Bakong KHQR Payment
            $paymentResult = $this->bakongService->generatePayment($order);

            // 9. Clear cart items and session backup
            $this->cartService->clearCart($lockedCart);

            $this->schedulePostCommitCacheAndSync($touchedProductIds, $order->id, $paymentResult['payment']->id, $discount?->id);

            return [
                'order' => $order,
                'payment' => $paymentResult['payment'],
                'qr_svg' => $paymentResult['qr_svg'],
                'qr_string' => $paymentResult['qr_string'],
                'md5' => $paymentResult['md5'],
            ];
        });
    }

    /**
     * Finalize payment as PAID (Idempotent with row locking).
     *
     * @param  array<string, mixed>  $bakongResponse
     */
    public function finalizePaidOrder(Payment $payment, array $bakongResponse): bool
    {
        return DB::transaction(function () use ($payment, $bakongResponse) {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status === PaymentStatus::Paid) {
                return true;
            }

            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $lockedPayment->order_id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->isPaid()) {
                $lockedPayment->update(['status' => PaymentStatus::Paid, 'paid_at' => now(), 'bakong_response' => $bakongResponse]);

                return true;
            }

            $wasPending = $lockedPayment->status === PaymentStatus::Pending;

            $lockedPayment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'last_checked_at' => now(),
                'bakong_response' => $bakongResponse,
            ]);

            $lockedOrder->update([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
            ]);

            $touchedProductIds = [];
            foreach ($lockedOrder->items as $orderItem) {
                if ($orderItem->product_variant_id) {
                    $variant = ProductVariant::where('id', $orderItem->product_variant_id)
                        ->lockForUpdate()
                        ->first();

                    if ($variant) {
                        $variant->decrement('stock_quantity', $orderItem->quantity);

                        if ($wasPending && $variant->reserved_quantity > 0) {
                            $variant->decrement('reserved_quantity', min($variant->reserved_quantity, $orderItem->quantity));
                        }

                        $touchedProductIds[] = (int) $variant->product_id;
                    }
                }
            }

            try {
                Mail::to($lockedOrder->email)->send(new OrderPlacedMail($lockedOrder));
            } catch (Exception $e) {
                Log::error('Failed to send order confirmation email: '.$e->getMessage(), [
                    'order_id' => $lockedOrder->id,
                ]);
            }

            $this->telegramService->notifyOrderPaid($lockedOrder);
            $this->schedulePostCommitCacheAndSync($touchedProductIds, $lockedOrder->id, $lockedPayment->id);

            return true;
        });
    }

    /**
     * Mark a payment as EXPIRED and release reserved inventory.
     */
    public function expirePayment(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status !== PaymentStatus::Pending) {
                return false;
            }

            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $lockedPayment->order_id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->isPaid()) {
                return false;
            }

            $lockedPayment->update([
                'status' => PaymentStatus::Expired,
                'last_checked_at' => now(),
            ]);

            $touchedProductIds = [];
            foreach ($lockedOrder->items as $orderItem) {
                if ($orderItem->product_variant_id) {
                    $variant = ProductVariant::where('id', $orderItem->product_variant_id)
                        ->lockForUpdate()
                        ->first();

                    if ($variant) {
                        $variant->decrement('reserved_quantity', min($variant->reserved_quantity, $orderItem->quantity));
                        $touchedProductIds[] = (int) $variant->product_id;
                    }
                }
            }

            if ($lockedOrder->status === OrderStatus::PendingPayment) {
                $lockedOrder->update(['status' => OrderStatus::Expired]);
            }

            $this->schedulePostCommitCacheAndSync($touchedProductIds, $lockedOrder->id, $lockedPayment->id);

            return true;
        });
    }

    /**
     * Regenerate a new KHQR for an expired/pending order.
     *
     * @return array{payment: Payment, qr_svg: string, qr_string: string, md5: string}
     *
     * @throws Exception
     */
    public function regeneratePayment(Order $order): array
    {
        return DB::transaction(function () use ($order) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->isPaid()) {
                throw new Exception('Order has already been paid.');
            }

            $touchedProductIds = [];
            foreach ($lockedOrder->items as $orderItem) {
                if ($orderItem->product_variant_id) {
                    $variant = ProductVariant::where('id', $orderItem->product_variant_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($variant->available_stock < $orderItem->quantity) {
                        throw new Exception("Product {$orderItem->product_name} ({$orderItem->variant_label}) is no longer in stock.");
                    }

                    $variant->increment('reserved_quantity', $orderItem->quantity);
                    $touchedProductIds[] = (int) $variant->product_id;
                }
            }

            $lockedOrder->update(['status' => OrderStatus::PendingPayment]);

            $expiredPaymentIds = $lockedOrder->payments()
                ->where('status', PaymentStatus::Pending)
                ->pluck('id')
                ->all();

            $lockedOrder->payments()->where('status', PaymentStatus::Pending)->update([
                'status' => PaymentStatus::Expired,
            ]);

            $result = $this->bakongService->generatePayment($lockedOrder);

            $this->schedulePostCommitCacheAndSync($touchedProductIds, $lockedOrder->id, $result['payment']->id, null, $expiredPaymentIds);

            return $result;
        });
    }

    /**
     * Manually mark an order as PAID by an administrator with audit note.
     */
    public function manualMarkPaid(Order $order, string $note, User $admin): bool
    {
        return DB::transaction(function () use ($order, $note, $admin) {
            $payment = $order->latestPayment;

            if (! $payment) {
                $result = $this->bakongService->generatePayment($order);
                $payment = $result['payment'];
            }

            $payment->update([
                'manual_paid_note' => $note,
                'manual_paid_by' => $admin->id,
            ]);

            $mockResponse = [
                'responseCode' => 0,
                'responseMessage' => 'Manually marked as paid by admin',
                'data' => [
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'note' => $note,
                    'timestamp' => now()->toISOString(),
                ],
            ];

            return $this->finalizePaidOrder($payment, $mockResponse);
        });
    }

    /**
     * Ensure catalog cache epoch bumps and atomic increment/mass-update rows sync after SQLite commits.
     *
     * @param  array<int, int>  $productIds
     * @param  array<int, int>  $extraPaymentIds
     */
    private function schedulePostCommitCacheAndSync(
        array $productIds,
        int $orderId,
        int $paymentId,
        ?int $discountId = null,
        array $extraPaymentIds = []
    ): void {
        $uniqueProductIds = array_values(array_unique(array_filter($productIds)));
        $allPaymentIds = array_values(array_unique(array_merge([$paymentId], $extraPaymentIds)));

        DB::afterCommit(static function () use ($uniqueProductIds, $orderId, $allPaymentIds, $discountId): void {
            CatalogCacheService::bumpCatalogEpoch();

            if (config('firebase.enabled', false) && config('firebase.sync_on_write', true)) {
                try {
                    $firestore = app(FirebaseFirestoreService::class);

                    if ($uniqueProductIds !== []) {
                        Product::with(['category', 'images', 'variants'])
                            ->whereIn('id', $uniqueProductIds)
                            ->get()
                            ->each(static fn (Product $product): bool => $firestore->syncProduct($product));
                    }

                    if ($discountId) {
                        $discount = Discount::find($discountId);
                        if ($discount) {
                            $firestore->syncDiscount($discount);
                        }
                    }

                    $order = Order::withCount('items')->find($orderId);
                    if ($order) {
                        $firestore->syncOrder($order);
                    }

                    Payment::with('order:id,number')
                        ->whereIn('id', $allPaymentIds)
                        ->get()
                        ->each(static fn (Payment $payment): bool => $firestore->syncPayment($payment));
                } catch (Throwable $e) {
                    Log::warning('Post-commit Firestore sync failed safely: '.$e->getMessage(), [
                        'order_id' => $orderId,
                    ]);
                }
            }

            if (config('firebase_api.enabled', false)) {
                try {
                    $gatewayClient = app(FirebaseApiClient::class);
                    if ($uniqueProductIds !== []) {
                        $gatewayClient->invalidateCache('products');
                    }
                    if ($discountId) {
                        $gatewayClient->invalidateCache('discounts');
                    }
                } catch (Throwable $e) {
                    Log::warning('Post-commit Firebase API cache invalidation failed safely: '.$e->getMessage(), [
                        'order_id' => $orderId,
                    ]);
                }
            }
        });
    }
}
