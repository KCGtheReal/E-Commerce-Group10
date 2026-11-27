<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('product_images', ['product_id', 'sort_order'], 'product_images_product_id_sort_order_index');
        $this->addIndexIfMissing('order_items', ['order_id', 'product_variant_id'], 'order_items_order_id_product_variant_id_index');
        $this->addIndexIfMissing('categories', ['is_active', 'sort_order'], 'categories_is_active_sort_order_index');
        $this->addIndexIfMissing('products', ['is_published', 'base_price_cents'], 'products_is_published_base_price_cents_index');
        $this->addIndexIfMissing('product_variants', ['product_id', 'size', 'color', 'color_code'], 'product_variants_product_id_size_color_code_index');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('product_images', 'product_images_product_id_sort_order_index');
        $this->dropIndexIfExists('order_items', 'order_items_order_id_product_variant_id_index');
        $this->dropIndexIfExists('categories', 'categories_is_active_sort_order_index');
        $this->dropIndexIfExists('products', 'products_is_published_base_price_cents_index');
        $this->dropIndexIfExists('product_variants', 'product_variants_product_id_size_color_code_index');
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
