<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\OrderService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService
    ) {}

    public function index(): View|RedirectResponse
    {
        $cart = $this->cartService->getCart();

        if ($cart->items->isEmpty()) {
            return redirect()->route('shop.index')->with('error', __('store.cart_empty'));
        }

        $user = Auth::user();
        $defaultAddress = $user?->defaultShippingAddress;

        $provinces = array_keys((array) Setting::get('province_shipping_fees', []));
        if (empty($provinces)) {
            $provinces = [
                'Phnom Penh', 'Kandal', 'Siem Reap', 'Battambang', 'Sihanoukville',
                'Kampot', 'Kep', 'Takeo', 'Kampong Cham', 'Kampong Chhnang',
                'Kampong Speu', 'Kampong Thom', 'Koh Kong', 'Kratie', 'Mondulkiri',
                'Oddar Meanchey', 'Pailin', 'Preah Vihear', 'Prey Veng', 'Pursat',
                'Ratanakiri', 'Stung Treng', 'Svay Rieng', 'Tboung Khmum', 'Banteay Meanchey',
            ];
        }

        $selectedProvince = $defaultAddress?->province ?? 'Phnom Penh';
        $totals = $this->cartService->calculateTotals($selectedProvince);

        return view('storefront.checkout', compact('cart', 'user', 'defaultAddress', 'provinces', 'selectedProvince', 'totals'));
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $cart = $this->cartService->getCart();

        if ($cart->items->isEmpty()) {
            return redirect()->route('shop.index')->with('error', __('store.cart_empty'));
        }

        // Concurrency Lock: Prevent duplicate order creation from rapid double-submissions
        $lockKey = 'checkout_lock_cart_'.$cart->id;
        $lock = Cache::lock($lockKey, 15);

        if (! $lock->get()) {
            return redirect()->back()->with('error', 'Checkout is already being processed. Please wait...');
        }

        try {
            $result = $this->orderService->createOrderFromCart(
                $cart,
                $request->validated(),
                Auth::user()
            );

            // Store order number in session for guest payment authorization
            session(['active_order_'.$result['order']->number => true]);

            return redirect()->route('payment.show', ['order' => $result['order']->number]);
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } finally {
            $lock->release();
        }
    }
}
