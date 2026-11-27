<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Setting;
use App\Services\FirebaseApiClient;
use App\Services\FirebaseFirestoreService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebaseSyncObserver
{
    public function saved(Model $model): void
    {
        if (! $this->shouldSyncFirestore() && ! $this->shouldInvalidateGateway()) {
            return;
        }

        $collections = $this->collectionsForModel($model);

        DB::afterCommit(function () use ($model, $collections): void {
            if ($this->shouldSyncFirestore()) {
                try {
                    $service = app(FirebaseFirestoreService::class);

                    match (true) {
                        $model instanceof Category => $service->syncCategory($model),
                        $model instanceof Product => $service->syncProduct($model->fresh(['category', 'images', 'variants']) ?? $model),
                        $model instanceof ProductVariant, $model instanceof ProductImage => $this->syncParentProduct($service, (int) $model->product_id),
                        $model instanceof Discount => $service->syncDiscount($model),
                        $model instanceof Setting => $service->syncSetting($model),
                        $model instanceof Review => $this->syncReviewAndParentProduct($service, $model),
                        $model instanceof Order => $service->syncOrder($model),
                        $model instanceof Payment => $service->syncPayment($model),
                        default => null,
                    };
                } catch (Throwable $e) {
                    Log::warning('FirebaseSyncObserver::saved failed safely: '.$e->getMessage(), [
                        'model' => $model::class,
                        'key' => $model->getKey(),
                    ]);
                }
            }

            $this->invalidateGatewayCollections($collections);
        });
    }

    public function deleted(Model $model): void
    {
        if (! $this->shouldSyncFirestore() && ! $this->shouldInvalidateGateway()) {
            return;
        }

        $modelClass = $model::class;
        $key = (string) $model->getKey();
        $productId = property_exists($model, 'product_id') || isset($model->product_id) ? (int) $model->product_id : null;
        $orderNumber = $model instanceof Order ? (string) $model->number : null;
        $settingKey = $model instanceof Setting ? (string) $model->key : null;
        $collections = $this->collectionsForModel($model);

        DB::afterCommit(function () use ($modelClass, $key, $productId, $orderNumber, $settingKey, $collections): void {
            if ($this->shouldSyncFirestore()) {
                try {
                    $service = app(FirebaseFirestoreService::class);

                    match ($modelClass) {
                        Category::class => $service->deleteDocument('categories', $key),
                        Product::class => $service->deleteDocument('products', $key),
                        ProductVariant::class, ProductImage::class => $productId ? $this->syncParentProduct($service, $productId) : null,
                        Discount::class => $service->deleteDocument('discounts', $key),
                        Setting::class => ($settingKey && in_array($settingKey, FirebaseFirestoreService::SETTINGS_PUBLIC_ALLOWLIST, true))
                            ? $service->deleteDocument('settings', $settingKey)
                            : null,
                        Review::class => $this->deleteReviewAndSyncProduct($service, $key, $productId),
                        Order::class => $orderNumber ? $service->deleteDocument('orders', $orderNumber) : null,
                        Payment::class => $service->deleteDocument('payments', $key),
                        default => null,
                    };
                } catch (Throwable $e) {
                    Log::warning('FirebaseSyncObserver::deleted failed safely: '.$e->getMessage(), [
                        'model' => $modelClass,
                        'key' => $key,
                    ]);
                }
            }

            $this->invalidateGatewayCollections($collections);
        });
    }

    private function shouldSyncFirestore(): bool
    {
        return (bool) config('firebase.enabled', false)
            && (bool) config('firebase.sync_on_write', true);
    }

    private function shouldInvalidateGateway(): bool
    {
        return (bool) config('firebase_api.enabled', false)
            && trim((string) config('firebase_api.base_url', '')) !== '';
    }

    /**
     * @return array<int, string>
     */
    private function collectionsForModel(Model $model): array
    {
        return match (true) {
            $model instanceof Category => ['categories'],
            $model instanceof Product,
            $model instanceof ProductVariant,
            $model instanceof ProductImage => ['products'],
            $model instanceof Discount => ['discounts'],
            $model instanceof Setting => in_array((string) $model->key, FirebaseFirestoreService::SETTINGS_PUBLIC_ALLOWLIST, true)
                ? ['settings']
                : [],
            $model instanceof Review => ['reviews', 'products'],
            $model instanceof Order => ['orders'],
            $model instanceof Payment => ['payments'],
            default => [],
        };
    }

    /**
     * @param  array<int, string>  $collections
     */
    private function invalidateGatewayCollections(array $collections): void
    {
        if ($collections === [] || ! $this->shouldInvalidateGateway()) {
            return;
        }

        try {
            $client = app(FirebaseApiClient::class);
            foreach ($collections as $collection) {
                $client->invalidateCache($collection);
            }
        } catch (Throwable $e) {
            Log::warning('FirebaseSyncObserver gateway cache invalidation failed safely: '.$e->getMessage(), [
                'collections' => $collections,
            ]);
        }
    }

    private function syncParentProduct(FirebaseFirestoreService $service, int $productId): void
    {
        if ($productId <= 0) {
            return;
        }

        $product = Product::with(['category', 'images', 'variants'])->find($productId);
        if ($product) {
            $service->syncProduct($product);
        }
    }

    private function syncReviewAndParentProduct(FirebaseFirestoreService $service, Review $review): void
    {
        $service->syncReview($review);
        $this->syncParentProduct($service, (int) $review->product_id);
    }

    private function deleteReviewAndSyncProduct(FirebaseFirestoreService $service, string $reviewId, ?int $productId): void
    {
        $service->deleteDocument('reviews', $reviewId);
        if ($productId) {
            $this->syncParentProduct($service, $productId);
        }
    }
}
