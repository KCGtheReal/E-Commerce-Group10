<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('carts', ['session_id'], 'carts_session_id_index');
        $this->addIndexIfMissing('carts', ['user_id'], 'carts_user_id_index');

        $this->addIndexIfMissing('products', ['is_published', 'is_featured'], 'products_is_published_is_featured_index');
        $this->addIndexIfMissing('products', ['is_published', 'is_new_arrival'], 'products_is_published_is_new_arrival_index');
        $this->addIndexIfMissing('products', ['is_published', 'is_best_seller'], 'products_is_published_is_best_seller_index');
        $this->addIndexIfMissing('products', ['category_id', 'is_published'], 'products_category_id_is_published_index');

        $this->addIndexIfMissing('product_variants', ['product_id', 'size'], 'product_variants_product_id_size_index');
        $this->addIndexIfMissing('product_variants', ['product_id', 'color'], 'product_variants_product_id_color_index');

        $this->addIndexIfMissing('reviews', ['product_id', 'is_approved', 'created_at'], 'reviews_product_id_is_approved_created_at_index');

        $this->addIndexIfMissing('orders', ['user_id', 'created_at'], 'orders_user_id_created_at_index');
        $this->addIndexIfMissing('orders', ['status'], 'orders_status_index');

        $this->addIndexIfMissing('payments', ['order_id', 'status'], 'payments_order_id_status_index');
        $this->addIndexIfMissing('payments', ['md5'], 'payments_md5_index');

        $this->addIndexIfMissing('wishlists', ['user_id', 'product_id'], 'wishlists_user_id_product_id_index');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('carts', 'carts_user_id_index');
        $this->dropIndexIfExists('products', 'products_is_published_is_featured_index');
        $this->dropIndexIfExists('products', 'products_is_published_is_new_arrival_index');
        $this->dropIndexIfExists('products', 'products_is_published_is_best_seller_index');
        $this->dropIndexIfExists('products', 'products_category_id_is_published_index');
        $this->dropIndexIfExists('product_variants', 'product_variants_product_id_size_index');
        $this->dropIndexIfExists('product_variants', 'product_variants_product_id_color_index');
        $this->dropIndexIfExists('reviews', 'reviews_product_id_is_approved_created_at_index');
        $this->dropIndexIfExists('orders', 'orders_user_id_created_at_index');
        $this->dropIndexIfExists('payments', 'payments_order_id_status_index');
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function addIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasIndex($table, $columns) || Schema::hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName): void {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
            $blueprint->dropIndex($indexName);
        });
    }
};
