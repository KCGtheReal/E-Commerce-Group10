<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Setting;
use App\Observers\FirebaseSyncObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            Category::class,
            Product::class,
            ProductVariant::class,
            ProductImage::class,
            Discount::class,
            Setting::class,
            Review::class,
            Order::class,
            Payment::class,
        ] as $modelClass) {
            $modelClass::observe(FirebaseSyncObserver::class);
        }
    }
}
