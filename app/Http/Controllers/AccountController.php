<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Models\Address;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function profile(): View
    {
        $user = Auth::user();

        return view('accounts.profile', compact('user'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function orders(): View
    {
        $orders = Auth::user()->orders()->with(['items', 'latestPayment'])->paginate(10);

        return view('accounts.orders', compact('orders'));
    }

    public function orderDetail(string $orderNumber): View
    {
        $order = Auth::user()->orders()
            ->where('number', $orderNumber)
            ->with(['items.variant.product', 'payments'])
            ->firstOrFail();

        return view('accounts.order-detail', compact('order'));
    }

    public function invoice(string $orderNumber): View
    {
        $order = Order::where('number', $orderNumber)->with(['items', 'latestPayment'])->firstOrFail();

        // Allow owner or admin
        if (! Auth::check() || (! Auth::user()->isAdmin() && Auth::id() !== $order->user_id)) {
            if (session('active_order_'.$order->number) !== true) {
                abort(403);
            }
        }

        $storeInfo = [
            'name' => Setting::get('store_name', 'RESET Store'),
            'address' => Setting::get('store_address', 'Phnom Penh, Cambodia'),
            'phone' => Setting::get('store_phone', '+855 23 999 123'),
            'email' => Setting::get('store_email', 'support@reset.store'),
        ];

        return view('storefront.invoice', compact('order', 'storeInfo'));
    }

    public function addresses(): View
    {
        $addresses = Auth::user()->addresses()->latest()->get();
        $provinces = array_keys((array) Setting::get('province_shipping_fees', []));

        return view('accounts.addresses', compact('addresses', 'provinces'));
    }

    public function storeAddress(AddressRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! empty($data['name']) && empty($data['first_name'])) {
            $parts = explode(' ', trim((string) $data['name']), 2);
            $data['first_name'] = $parts[0];
            $data['last_name'] = $parts[1] ?? '';
        }
        unset($data['name']);

        $user = Auth::user();

        if (! empty($data['is_default'])) {
            $user->addresses()->update(['is_default' => false]);
        }

        $user->addresses()->create($data);

        return redirect()->back()->with('success', 'Address saved successfully.');
    }

    public function updateAddress(AddressRequest $request, Address $address): RedirectResponse
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        $data = $request->validated();
        if (! empty($data['name']) && empty($data['first_name'])) {
            $parts = explode(' ', trim((string) $data['name']), 2);
            $data['first_name'] = $parts[0];
            $data['last_name'] = $parts[1] ?? '';
        }
        unset($data['name']);

        if (! empty($data['is_default'])) {
            Auth::user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return redirect()->back()->with('success', 'Address updated successfully.');
    }

    public function destroyAddress(Address $address): RedirectResponse
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        $address->delete();

        return redirect()->back()->with('success', 'Address deleted.');
    }
}
