<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\WishlistController;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Localization & Currency Switchers
Route::get('/locale/{locale}', [LocaleController::class, 'setLocale'])->name('locale.set');
Route::get('/currency/{currency}', [LocaleController::class, 'setCurrency'])->name('currency.set');

// Storefront Core
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/search/suggest', [ShopController::class, 'suggest'])->name('search.suggest');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');

// Reviews
Route::post('/product/{slug}/reviews', [ReviewController::class, 'store'])->middleware('auth')->name('product.review.store');

// Wishlist
Route::get('/wishlist', [WishlistController::class, 'index'])->middleware('auth')->name('wishlist.index');
Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

// Cart Routes
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::patch('/items/{item}', [CartController::class, 'update'])->name('update');
    Route::delete('/items/{item}', [CartController::class, 'remove'])->name('remove');
    Route::post('/discount', [CartController::class, 'applyDiscount'])->name('discount.apply');
    Route::delete('/discount', [CartController::class, 'removeDiscount'])->name('discount.remove');
    Route::get('/data', [CartController::class, 'data'])->name('data');
});

// Checkout Flow
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::post('/', [CheckoutController::class, 'store'])->name('store');
});

// Bakong KHQR Payment Flow
Route::get('/checkout/{order:number}/pay', [PaymentController::class, 'show'])->name('payment.show');
Route::get('/payments/{payment}/status', [PaymentController::class, 'status'])
    ->middleware('throttle:60,1')
    ->name('payment.status');
Route::post('/checkout/{order:number}/regenerate-qr', [PaymentController::class, 'regenerate'])->name('payment.regenerate');
Route::post('/payments/{payment}/simulate-pay', [PaymentController::class, 'simulatePay'])->name('payment.simulate');
Route::get('/payments/{payment}/download-qr', [PaymentController::class, 'downloadQr'])->name('payment.download');
Route::get('/orders/{order:number}/success', [PaymentController::class, 'success'])->name('payment.success');

// Printable Order Invoice
Route::get('/orders/{order:number}/invoice', [AccountController::class, 'invoice'])->name('order.invoice');

// Customer Account Portal
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::post('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order:number}', [AccountController::class, 'orderDetail'])->name('orders.show');
    Route::get('/addresses', [AccountController::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [AccountController::class, 'storeAddress'])->name('addresses.store');
    Route::put('/addresses/{address}', [AccountController::class, 'updateAddress'])->name('addresses.update');
    Route::delete('/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('addresses.destroy');
});

// Static Brand Pages
Route::get('/about', [PageController::class, 'about'])->name('page.about');
Route::get('/faq', [PageController::class, 'faq'])->name('page.faq');
Route::get('/shipping', [PageController::class, 'shipping'])->name('page.shipping');
Route::get('/privacy', [PageController::class, 'privacy'])->name('page.privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('page.terms');
Route::get('/size-guide', [PageController::class, 'sizeGuide'])->name('page.size-guide');
Route::get('/contact', [PageController::class, 'contact'])->name('page.contact');
Route::post('/contact', [PageController::class, 'contactSubmit'])->name('page.contact.submit');
Route::post('/newsletter', [PageController::class, 'newsletterSubmit'])->name('newsletter.subscribe');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');
Route::get('/dashboard', fn () => redirect()->route('account.profile'))->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Login Fallback Handler (defensive route for native browser POST submissions)
Route::post('admin/login', function (Request $request) {
    $email = strtolower(trim((string) ($request->input('data.email') ?? $request->input('email'))));
    $password = (string) ($request->input('data.password') ?? $request->input('password'));
    $remember = (bool) ($request->input('data.remember') ?? $request->input('remember', false));

    if (! $email || ! $password) {
        return back()->withErrors(['email' => trans('auth.failed')])->withInput();
    }

    if (! auth()->guard('admin')->attempt(['email' => $email, 'password' => $password], $remember)) {
        return back()->withErrors(['email' => trans('auth.failed')])->withInput();
    }

    /** @var User $user */
    $user = auth()->guard('admin')->user();
    if (! $user->canAccessPanel(Filament::getPanel('admin'))) {
        auth()->guard('admin')->logout();

        return back()->withErrors(['email' => trans('auth.failed')])->withInput();
    }

    $request->session()->regenerate();

    return redirect()->intended('/admin');
})->middleware(['web']);

// Authentication Routes (Laravel Breeze)
require __DIR__.'/auth.php';
