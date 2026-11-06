<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Discount;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CartService
{
    /**
     * Get or create the active cart for the current session/user.
     */
    public function getCart(): Cart
    {
        if (Auth::check()) {
            $user = Auth::user();
            $cart = Cart::with(['items.variant.product.images', 'discount'])->firstOrCreate(
                ['user_id' => $user->id],
                ['id' => (string) Str::uuid()]
            );

            // If there's an existing session cart, merge it into this user's cart
            $cartId = Session::get('cart_id');
            $sessionCart = $cartId
                ? Cart::where('id', $cartId)->whereNull('user_id')->first()
                : Cart::where('session_id', Session::getId())->whereNull('user_id')->first();

            if ($sessionCart && $sessionCart->id !== $cart->id) {
                $this->mergeCarts($sessionCart, $cart);
                Session::forget('cart_id');
            }

            return $cart;
        }

        if (Session::has('cart_id')) {
            $cart = Cart::with(['items.variant.product.images', 'discount'])
                ->where('id', Session::get('cart_id'))
                ->whereNull('user_id')
                ->first();

            if ($cart) {
                return $cart;
            }
        }

        $sessionId = Session::getId();
        $cart = Cart::with(['items.variant.product.images', 'discount'])->firstOrCreate(
            ['session_id' => $sessionId, 'user_id' => null],
            ['id' => (string) Str::uuid()]
        );

        Session::put('cart_id', $cart->id);

        return $cart;
    }

    /**
     * Add a product variant to the cart.
     *
     * @throws Exception
     */
    public function addItem(int $variantId, int $quantity = 1): CartItem
    {
        $cart = $this->getCart();
        $variant = ProductVariant::findOrFail($variantId);

        if ($quantity <= 0) {
            throw new Exception('Quantity must be at least 1.');
        }

        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variantId)
            ->first();

        $currentQty = $existingItem ? $existingItem->quantity : 0;
        $newTotalQty = $currentQty + $quantity;

        if ($newTotalQty > $variant->available_stock) {
            throw new Exception("Only {$variant->available_stock} units available in stock.");
        }

        if ($existingItem) {
            $existingItem->update(['quantity' => $newTotalQty]);

            return $existingItem;
        }

        return CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Update the quantity of an item in the cart.
     *
     * @throws Exception
     */
    public function updateItem(int $itemId, int $quantity): ?CartItem
    {
        $cart = $this->getCart();
        $item = CartItem::where('cart_id', $cart->id)->where('id', $itemId)->firstOrFail();

        if ($quantity <= 0) {
            $item->delete();

            return null;
        }

        if ($quantity > $item->variant->available_stock) {
            throw new Exception("Only {$item->variant->available_stock} units available in stock.");
        }

        $item->update(['quantity' => $quantity]);

        return $item;
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(int $itemId): bool
    {
        $cart = $this->getCart();

        return (bool) CartItem::where('cart_id', $cart->id)->where('id', $itemId)->delete();
    }

    /**
     * Apply a discount code to the cart.
     *
     * @throws Exception
     */
    public function applyDiscount(string $code): Discount
    {
        $cart = $this->getCart();
        $discount = Discount::where('code', strtoupper(trim($code)))->first();

        if (! $discount) {
            throw new Exception('Invalid discount code.');
        }

        if (! $discount->isValidFor($cart->subtotal_cents)) {
            if ($discount->min_spend_cents > $cart->subtotal_cents) {
                $minSpend = CurrencyService::formatCents($discount->min_spend_cents);
                throw new Exception("Minimum spend of {$minSpend} required for this promo code.");
            }
            throw new Exception('This discount code is expired or no longer valid.');
        }

        $cart->update(['discount_id' => $discount->id]);

        return $discount;
    }

    /**
     * Remove the active discount code from the cart.
     */
    public function removeDiscount(): void
    {
        $cart = $this->getCart();
        $cart->update(['discount_id' => null]);
    }

    /**
     * Calculate comprehensive totals for the cart with optional shipping province.
     *
     * @return array{subtotal_cents: int, formatted_subtotal: string, discount_cents: int, formatted_discount: string, shipping_fee_cents: int, formatted_shipping: string, total_cents: int, formatted_total: string, free_shipping_qualified: bool, free_shipping_remaining_cents: int, free_shipping_remaining_formatted: string}
     */
    public function calculateTotals(?string $province = null): array
    {
        $cart = $this->getCart();
        $subtotal = $cart->subtotal_cents;

        // Shipping fee calculation
        $shippingFees = (array) Setting::get('province_shipping_fees', ['Phnom Penh' => 150]);
        $freeShippingThreshold = (int) Setting::get('free_shipping_threshold', 5000); // $50.00

        $provinceKey = $province ?? 'Phnom Penh';
        $baseShippingFee = (int) ($shippingFees[$provinceKey] ?? 250);

        $freeShippingQualified = $subtotal >= $freeShippingThreshold;
        $freeShippingRemaining = max(0, $freeShippingThreshold - $subtotal);

        // Discount calculation
        $discountAmount = 0;
        if ($cart->discount && $cart->discount->isValidFor($subtotal)) {
            $discountAmount = $cart->discount->calculateDiscount($subtotal, $baseShippingFee);
        }

        $effectiveShippingFee = $freeShippingQualified ? 0 : $baseShippingFee;
        if ($cart->discount && $cart->discount->type?->value === 'free_shipping') {
            $effectiveShippingFee = 0;
        }

        $totalCents = max(0, $subtotal - $discountAmount + $effectiveShippingFee);

        return [
            'subtotal_cents' => $subtotal,
            'formatted_subtotal' => CurrencyService::formatCents($subtotal),
            'discount_cents' => $discountAmount,
            'formatted_discount' => '-'.CurrencyService::formatCents($discountAmount),
            'shipping_fee_cents' => $effectiveShippingFee,
            'formatted_shipping' => $effectiveShippingFee === 0 ? (CurrencyService::current() === 'USD' ? 'FREE' : '0 ៛') : CurrencyService::formatCents($effectiveShippingFee),
            'total_cents' => $totalCents,
            'formatted_total' => CurrencyService::formatCents($totalCents),
            'free_shipping_qualified' => $freeShippingQualified,
            'free_shipping_remaining_cents' => $freeShippingRemaining,
            'free_shipping_remaining_formatted' => CurrencyService::formatCents($freeShippingRemaining),
            'currency' => CurrencyService::current(),
        ];
    }

    /**
     * Merge guest cart into user cart.
     */
    public function mergeCarts(Cart $guestCart, Cart $userCart): void
    {
        foreach ($guestCart->items as $guestItem) {
            $existing = CartItem::where('cart_id', $userCart->id)
                ->where('product_variant_id', $guestItem->product_variant_id)
                ->first();

            if ($existing) {
                $newQty = min(
                    $existing->quantity + $guestItem->quantity,
                    $guestItem->variant->available_stock
                );
                $existing->update(['quantity' => $newQty]);
            } else {
                $qty = min($guestItem->quantity, $guestItem->variant->available_stock);
                if ($qty > 0) {
                    CartItem::create([
                        'cart_id' => $userCart->id,
                        'product_variant_id' => $guestItem->product_variant_id,
                        'quantity' => $qty,
                    ]);
                }
            }
        }

        // Copy discount if user cart didn't have one
        if (! $userCart->discount_id && $guestCart->discount_id) {
            $userCart->update(['discount_id' => $guestCart->discount_id]);
        }

        $guestCart->items()->delete();
        $guestCart->delete();
    }

    /**
     * Helper to merge guest cart into user cart by User model.
     */
    public function mergeGuestCartToUser(User $user): void
    {
        $cartId = Session::get('cart_id');
        $guestCart = $cartId
            ? Cart::where('id', $cartId)->whereNull('user_id')->first()
            : Cart::where('session_id', Session::getId())->whereNull('user_id')->first();

        if (! $guestCart) {
            return;
        }

        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);
        $this->mergeCarts($guestCart, $userCart);
        Session::forget('cart_id');
    }
}
