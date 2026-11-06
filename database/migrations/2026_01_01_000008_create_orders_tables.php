<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('phone');
            $table->json('shipping_address');
            $table->json('billing_address')->nullable();

            // Amounts in base currency (USD cents)
            $table->integer('subtotal_cents');
            $table->integer('discount_cents')->default(0);
            $table->string('discount_code')->nullable();
            $table->integer('shipping_fee_cents')->default(0);
            $table->string('shipping_method')->default('standard');
            $table->integer('total_cents');

            // Charged currency details
            $table->string('currency')->default('USD'); // USD or KHR
            $table->integer('exchange_rate')->default(4100);
            $table->decimal('total_charged_currency_amount', 14, 2);

            // Fulfillment & Order Status
            $table->string('status')->default('pending_payment')->index();
            $table->string('tracking_number')->nullable();
            $table->string('shipping_carrier')->nullable();
            $table->text('customer_notes')->nullable();

            // Manual refund details
            $table->integer('refund_amount_cents')->nullable();
            $table->text('refund_note')->nullable();

            // Status Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->string('product_name_km')->nullable();
            $table->string('variant_label'); // e.g. "Size: L / Color: Onyx Black"
            $table->string('sku');
            $table->integer('price_cents');
            $table->integer('quantity');
            $table->integer('subtotal_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
