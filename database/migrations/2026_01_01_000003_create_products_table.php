<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_km')->nullable();
            $table->string('slug')->unique();
            $table->string('sku_prefix');
            $table->string('tagline')->nullable();
            $table->string('tagline_km')->nullable();
            $table->text('description');
            $table->text('description_km')->nullable();
            $table->json('details')->nullable(); // fabric, care, fit
            $table->integer('base_price_cents'); // USD cents e.g. 4500 = $45.00
            $table->integer('compare_at_price_cents')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_new_arrival')->default(false)->index();
            $table->boolean('is_best_seller')->default(false)->index();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
