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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CartService
{
    public const EXPRESS_SHIPPING_FEE_KHR = 12000;

    public const EXPRESS_SHIPPING_PROVINCE = 'Phnom Penh';

    public const MAX_ITEM_QUANTITY = 20;

    /**
     * @var array<int, string>
     */
    public const CAMBODIA_PROVINCES = [
        'Phnom Penh',
        'Kandal',
        'Siem Reap',
        'Battambang',
        'Sihanoukville',
        'Kampot',
        'Kep',
        'Takeo',
        'Kampong Cham',
        'Kampong Chhnang',
        'Kampong Speu',
        'Kampong Thom',
        'Koh Kong',
        'Kratie',
        'Mondulkiri',
        'Oddar Meanchey',
        'Pailin',
        'Preah Vihear',
        'Prey Veng',
        'Pursat',
        'Ratanakiri',
        'Stung Treng',
        'Svay Rieng',
        'Tboung Khmum',
        'Banteay Meanchey',
    ];

    /**
     * Get or create the active cart for the current Blade web session/user.
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

            $this->restoreCartFromSession($cart);

            return $cart;
        }

        if (Session::has('cart_id')) {
            $cart = Cart::with(['items.variant.product.images', 'discount'])
                ->where('id', Session::get('cart_id'))
                ->whereNull('user_id')
                ->first();

            if ($cart) {
                $this->restoreCartFromSession($cart);

                return $cart;
            }
        }

        $sessionId = Session::getId();
        $cart = Cart::with(['items.variant.product.images', 'discount'])->firstOrCreate(
            ['session_id' => $sessionId, 'user_id' => null],
            ['id' => (string) Str::uuid()]
        );

        Session::put('cart_id', $cart->id);
        $this->restoreCartFromSession($cart);

        return $cart;
    }

    /**
     * Resolve or create a stateless API cart using Bearer user and/or X-Cart-Token (without PHP Session).
     */
    public function resolveApiCart(?User $user, ?string $cartToken = null, bool $createIfMissing = true): ?Cart
    {
        $relations = ['items.variant.product.images', 'discount'];
        $cleanToken = trim((string) $cartToken);

        if ($user) {
            $userCart = Cart::with($relations)->firstOrCreate(
                ['user_id' => $user->id],
                ['id' => (string) Str::uuid()]
            );

            if ($cleanToken !== '') {
                $guestCart = Cart::with('items.variant')
                    ->where('session_id', $cleanToken)
                    ->whereNull('user_id')
                    ->first();

                if ($guestCart && $guestCart->id !== $userCart->id) {
                    DB::transaction(function () use ($guestCart, $userCart): void {
                        $this->mergeCarts($guestCart, $userCart);
                    });
                    $userCart->load($relations);
                }
            }

            return $userCart;
        }

        if ($cleanToken !== '') {
            $existing = Cart::with($relations)
                ->where('session_id', $cleanToken)
                ->whereNull('user_id')
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        if (! $createIfMissing) {
            return null;
        }

        $newToken = $cleanToken !== '' ? $cleanToken : (string) Str::uuid();
        $cart = Cart::create([
            'id' => (string) Str::uuid(),
            'user_id' => null,
            'session_id' => $newToken,
        ]);

        return $cart->load($relations);
    }

    /**
     * Atomically merge a guest X-Cart-Token cart into the user's persistent cart on API login/register.
     */
    public function mergeGuestCartByToken(User $user, ?string $cartToken): Cart
    {
        return DB::transaction(function () use ($user, $cartToken): Cart {
            $userCart = Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['id' => (string) Str::uuid()]
            );

            $cleanToken = trim((string) $cartToken);
            if ($cleanToken !== '') {
                $guestCart = Cart::with('items.variant')
                    ->where('session_id', $cleanToken)
                    ->whereNull('user_id')
                    ->first();

                if ($guestCart && $guestCart->id !== $userCart->id) {
                    $this->mergeCarts($guestCart, $userCart);
                }
            }

            return $userCart->fresh(['items.variant.product.images', 'discount']) ?? $userCart;
        });
    }

    /**
     * Add a product variant to the Blade session cart.
     *
     * @throws Exception
     */
    public function addItem(int $variantId, int $quantity = 1): CartItem
    {
        $cart = $this->getCart();
        $item = $this->addItemToCart($cart, $variantId, $quantity);
        $this->syncCartToSession($cart);

        return $item;
    }

    /**
     * Stateless API helper: Add a product variant to the specified Cart without touching PHP Session.
     *
     * @throws Exception
     */
    public function addItemToCart(Cart $cart, int $variantId, int $quantity = 1): CartItem
    {
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

        if ($newTotalQty > self::MAX_ITEM_QUANTITY) {
            throw new Exception('Maximum '.self::MAX_ITEM_QUANTITY.' units allowed per item.');
        }

        if ($existingItem) {
            $existingItem->update(['quantity' => $newTotalQty]);
            $cart->unsetRelation('items');

            return $existingItem;
        }

        $newItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
        ]);
        $cart->unsetRelation('items');

        return $newItem;
    }

    /**
     * Update the quantity of an item in the Blade session cart.
     *
     * @throws Exception
     */
    public function updateItem(int $itemId, int $quantity): ?CartItem
    {
        $cart = $this->getCart();
        $item = $this->updateItemInCart($cart, $itemId, $quantity);
        $this->syncCartToSession($cart);

        return $item;
    }

    /**
     * Stateless API helper: Update an item quantity inside the given Cart without touching PHP Session.
     *
     * @throws Exception
     */
    public function updateItemInCart(Cart $cart, int $itemId, int $quantity): ?CartItem
    {
        $item = CartItem::where('cart_id', $cart->id)->where('id', $itemId)->firstOrFail();

        if ($quantity <= 0) {
            $item->delete();
            $cart->unsetRelation('items');

            return null;
        }

        if ($quantity > $item->variant->available_stock) {
            throw new Exception("Only {$item->variant->available_stock} units available in stock.");
        }

        if ($quantity > self::MAX_ITEM_QUANTITY) {
            throw new Exception('Maximum '.self::MAX_ITEM_QUANTITY.' units allowed per item.');
        }

        $item->update(['quantity' => $quantity]);
        $cart->unsetRelation('items');

        return $item;
    }

    /**
     * Remove an item from the Blade session cart.
     */
    public function removeItem(int $itemId): bool
    {
        $cart = $this->getCart();
        $deleted = $this->removeItemFromCart($cart, $itemId);
        $this->syncCartToSession($cart);

        return $deleted;
    }

    /**
     * Stateless API helper: Remove an item from the specified Cart without touching PHP Session.
     */
    public function removeItemFromCart(Cart $cart, int $itemId): bool
    {
        $item = CartItem::where('cart_id', $cart->id)->where('id', $itemId)->first();
        if (! $item) {
            return false;
        }

        $deleted = (bool) $item->delete();
        $cart->unsetRelation('items');

        return $deleted;
    }

    /**
     * Clear all items and reset active discount.
     */
    public function clearCart(?Cart $cart = null): void
    {
        $activeCart = $cart ?? $this->getCart();
        $activeCart->items()->delete();
        $activeCart->update(['discount_id' => null]);

        if (request()?->hasSession()) {
            Session::forget('cart_items_backup');
            Session::forget('cart_discount_id_backup');
        }
    }

    /**
     * Apply a discount code to the Blade session cart.
     *
     * @throws Exception
     */
    public function applyDiscount(string $code): Discount
    {
        $cart = $this->getCart();
        $discount = $this->applyDiscountToCart($cart, $code);
        $this->syncCartToSession($cart);

        return $discount;
    }

    /**
     * Stateless API helper: Apply a discount code to the specified Cart.
     *
     * @throws Exception
     */
    public function applyDiscountToCart(Cart $cart, string $code): Discount
    {
        $cart->loadMissing(['items.variant', 'discount']);
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
        $cart->setRelation('discount', $discount);

        return $discount;
    }

    /**
     * Remove the active discount code from the Blade session cart.
     */
    public function removeDiscount(): void
    {
        $cart = $this->getCart();
        $this->removeDiscountFromCart($cart);
        $this->syncCartToSession($cart);
    }

    /**
     * Stateless API helper: Remove active discount from the specified Cart.
     */
    public function removeDiscountFromCart(Cart $cart): void
    {
        $cart->update(['discount_id' => null]);
        $cart->setRelation('discount', null);
    }

    /**
     * Calculate comprehensive totals for the cart with province & Express delivery parity.
     *
     * @return array<string, mixed>
     */
    public function calculateTotals(
        ?string $province = null,
        string $shippingMethod = 'standard',
        ?Cart $cart = null
    ): array {
        $activeCart = $cart ?? $this->getCart();
        $activeCart->loadMissing(['items.variant', 'discount']);
        $subtotal = $activeCart->subtotal_cents;

        $shippingFees = (array) Setting::get('province_shipping_fees', []);
        $freeShippingThreshold = (int) Setting::get('free_shipping_threshold', 200000);
        $currency = CurrencyService::current();
        $exchangeRate = CurrencyService::exchangeRate();

        $provinceKey = trim((string) ($province ?? self::EXPRESS_SHIPPING_PROVINCE));
        if ($provinceKey === '') {
            $provinceKey = self::EXPRESS_SHIPPING_PROVINCE;
        }

        $requestedMethod = strtolower(trim($shippingMethod)) === 'express' ? 'express' : 'standard';
        $isExpressEligible = $requestedMethod === 'express' && $provinceKey === self::EXPRESS_SHIPPING_PROVINCE;
        $resolvedMethod = $isExpressEligible ? 'express' : 'standard';

        $standardShippingFee = (int) ($shippingFees[$provinceKey] ?? 0);
        $freeShippingQualified = $subtotal >= $freeShippingThreshold;
        $freeShippingRemaining = max(0, $freeShippingThreshold - $subtotal);
        $freeShippingProgressPercent = $freeShippingThreshold > 0
            ? min(100.0, round(($subtotal / $freeShippingThreshold) * 100, 1))
            : 100.0;

        $discountAmount = 0;
        if ($activeCart->discount && $activeCart->discount->isValidFor($subtotal)) {
            $discountAmount = $activeCart->discount->calculateDiscount($subtotal, $standardShippingFee);
        }

        if ($isExpressEligible) {
            $effectiveShippingFee = self::EXPRESS_SHIPPING_FEE_KHR;
        } else {
            $effectiveShippingFee = $freeShippingQualified ? 0 : $standardShippingFee;
            if ($activeCart->discount && $activeCart->discount->type?->value === 'free_shipping') {
                $effectiveShippingFee = 0;
            }
        }

        $totalCents = max(0, $subtotal - $discountAmount + $effectiveShippingFee);
        $formattedDiscount = $discountAmount > 0
            ? '-'.CurrencyService::formatCents($discountAmount, $currency)
            : CurrencyService::formatCents(0, $currency);

        return [
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'province' => $provinceKey,
            'shipping_method' => $resolvedMethod,
            'subtotal_cents' => $subtotal,
            'formatted_subtotal' => CurrencyService::formatCents($subtotal, $currency),
            'discount_cents' => $discountAmount,
            'formatted_discount' => $formattedDiscount,
            'shipping_fee_cents' => $effectiveShippingFee,
            'formatted_shipping' => $effectiveShippingFee === 0
                ? ($currency === 'USD' ? 'FREE' : '0 ៛')
                : CurrencyService::formatCents($effectiveShippingFee, $currency),
            'total_cents' => $totalCents,
            'formatted_total' => CurrencyService::formatCents($totalCents, $currency),
            'converted_total' => CurrencyService::convertCents($totalCents, $currency),
            'free_shipping_threshold_cents' => $freeShippingThreshold,
            'formatted_free_shipping_threshold' => CurrencyService::formatCents($freeShippingThreshold, $currency),
            'free_shipping_qualified' => $freeShippingQualified,
            'free_shipping_remaining_cents' => $freeShippingRemaining,
            'free_shipping_remaining_formatted' => CurrencyService::formatCents($freeShippingRemaining, $currency),
            'free_shipping_progress_percent' => $freeShippingProgressPercent,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getProvinces(): array
    {
        $configured = array_keys((array) Setting::get('province_shipping_fees', []));

        return $configured !== [] ? $configured : self::CAMBODIA_PROVINCES;
    }

    /**
     * Merge guest cart into user cart.
     */
    public function mergeCarts(Cart $guestCart, Cart $userCart): void
    {
        $guestCart->loadMissing('items.variant');

        foreach ($guestCart->items as $guestItem) {
            if (! $guestItem->variant) {
                continue;
            }

            $existing = CartItem::where('cart_id', $userCart->id)
                ->where('product_variant_id', $guestItem->product_variant_id)
                ->first();

            $maxAllowed = min(self::MAX_ITEM_QUANTITY, max(0, $guestItem->variant->available_stock));

            if ($existing) {
                $newQty = min($existing->quantity + $guestItem->quantity, $maxAllowed);
                if ($newQty > 0) {
                    $existing->update(['quantity' => $newQty]);
                }
            } else {
                $qty = min($guestItem->quantity, $maxAllowed);
                if ($qty > 0) {
                    CartItem::create([
                        'cart_id' => $userCart->id,
                        'product_variant_id' => $guestItem->product_variant_id,
                        'quantity' => $qty,
                    ]);
                }
            }
        }

        if (! $userCart->discount_id && $guestCart->discount_id) {
            $userCart->update(['discount_id' => $guestCart->discount_id]);
        }

        $guestCart->items()->delete();
        $guestCart->delete();
    }

    /**
     * Helper to merge guest cart into user cart by User model (Blade login/register).
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

        $userCart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['id' => (string) Str::uuid()]
        );
        $this->mergeCarts($guestCart, $userCart);
        Session::forget('cart_id');
    }

    /**
     * Backup active cart items into the session cookie (Blade storefront only).
     */
    private function syncCartToSession(Cart $cart): void
    {
        $cart->unsetRelation('items')->load('items');
        $backup = [];
        foreach ($cart->items as $item) {
            $backup[] = [
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
            ];
        }

        Session::put('cart_items_backup', $backup);
        if ($cart->discount_id) {
            Session::put('cart_discount_id_backup', $cart->discount_id);
        } else {
            Session::forget('cart_discount_id_backup');
        }
    }

    /**
     * Restore cart items from the session cookie if the database cart has no items.
     */
    private function restoreCartFromSession(Cart $cart): void
    {
        if (! $cart->items->isEmpty()) {
            return;
        }

        $backup = Session::get('cart_items_backup', []);
        if (empty($backup)) {
            return;
        }

        $restoredAny = false;
        foreach ($backup as $entry) {
            $variantId = (int) ($entry['product_variant_id'] ?? 0);
            $qty = (int) ($entry['quantity'] ?? 0);
            if ($variantId > 0 && $qty > 0) {
                $variant = ProductVariant::find($variantId);
                if ($variant && $variant->available_stock > 0) {
                    CartItem::create([
                        'cart_id' => $cart->id,
                        'product_variant_id' => $variantId,
                        'quantity' => min($qty, $variant->available_stock, self::MAX_ITEM_QUANTITY),
                    ]);
                    $restoredAny = true;
                }
            }
        }

        if ($restoredAny) {
            $discountId = Session::get('cart_discount_id_backup');
            if ($discountId && ! $cart->discount_id) {
                $cart->update(['discount_id' => $discountId]);
            }
            $cart->load(['items.variant.product.images', 'discount']);
        }
    }
}
