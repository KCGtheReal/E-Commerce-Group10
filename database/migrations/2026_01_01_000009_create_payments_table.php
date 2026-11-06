<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('provider')->default('bakong_khqr');
            $table->string('md5')->unique(); // Unique indexed MD5 hash of the KHQR string
            $table->text('qr_string'); // EMV-QR string
            $table->string('account_id');
            $table->decimal('amount', 14, 2);
            $table->string('currency'); // USD or KHR
            $table->string('status')->default('pending'); // pending, paid, expired, failed
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->json('bakong_response')->nullable();
            $table->text('error_message')->nullable();
            $table->text('manual_paid_note')->nullable(); // Required note when marked paid by admin
            $table->foreignId('manual_paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
