<?php

declare(strict_types=1);

use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BootstrapController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\WishlistController;
use Illuminate\Support\Facades\Route;

// OpenAPI 3.1.0 Spec & Interactive Swagger UI
Route::get('/openapi.json', [OpenApiController::class, 'spec'])->name('api.openapi');
Route::get('/docs', [OpenApiController::class, 'docs'])->name('api.docs');

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    // Store Bootstrap & Catalog
    Route::get('/bootstrap', [BootstrapController::class, 'index'])->name('bootstrap');
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/search/suggest', [ProductController::class, 'suggest'])->name('search.suggest');
    Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

    // Reviews & Wishlist (Auth Bearer)
    Route::middleware('auth.api')->group(function (): void {
        Route::post('/products/{slug}/reviews', [ReviewController::class, 'store'])->name('products.reviews.store');
        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
        Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    });

    // Stateless Cart (Bearer or X-Cart-Token)
    Route::prefix('cart')->name('cart.')->group(function (): void {
        Route::get('/', [CartController::class, 'show'])->name('show');
        Route::post('/items', [CartController::class, 'addItem'])->name('items.store');
        Route::patch('/items/{item}', [CartController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{item}', [CartController::class, 'removeItem'])->name('items.destroy');
        Route::post('/discount', [CartController::class, 'applyDiscount'])->name('discount.apply');
        Route::delete('/discount', [CartController::class, 'removeDiscount'])->name('discount.remove');
    });

    // Checkout, Bakong KHQR Payment & Invoice (Bearer Owner/Admin or X-Order-Token)
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout');
    Route::get('/orders/{orderNumber}/payment', [PaymentController::class, 'orderPayment'])->name('orders.payment');
    Route::get('/payments/{payment}/status', [PaymentController::class, 'status'])
        ->middleware('throttle:60,1')
        ->name('payments.status');
    Route::post('/orders/{orderNumber}/regenerate-qr', [PaymentController::class, 'regenerateQr'])->name('orders.regenerate-qr');
    Route::post('/payments/{payment}/simulate-pay', [PaymentController::class, 'simulatePay'])->name('payments.simulate-pay');
    Route::get('/orders/{orderNumber}/invoice', [PaymentController::class, 'invoice'])->name('orders.invoice');

    // Authentication
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');

        Route::middleware('auth.api')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::put('/password', [AuthController::class, 'updatePassword'])->name('password.update');
        });
    });

    // Customer Account Portal (Auth Bearer)
    Route::middleware('auth.api')->prefix('account')->name('account.')->group(function (): void {
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile.show');
        Route::patch('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::get('/orders', [AccountController::class, 'orders'])->name('orders.index');
        Route::get('/orders/{orderNumber}', [AccountController::class, 'orderDetail'])->name('orders.show');
        Route::get('/addresses', [AccountController::class, 'addresses'])->name('addresses.index');
        Route::post('/addresses', [AccountController::class, 'storeAddress'])->name('addresses.store');
        Route::put('/addresses/{address}', [AccountController::class, 'updateAddress'])->name('addresses.update');
        Route::delete('/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('addresses.destroy');
    });

    // Info Pages & Support
    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');
    Route::post('/contact', [PageController::class, 'contact'])->name('contact');
    Route::post('/newsletter', [PageController::class, 'newsletter'])->name('newsletter');
});
