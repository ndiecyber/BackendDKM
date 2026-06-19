<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shohibul_id')->constrained('shohibuls')->cascadeOnDelete();
            $table->string('order_id')->unique();             // ID unik ke PaKasir
            $table->decimal('amount', 15, 2);                 // Nominal setoran
            $table->enum('status', ['pending', 'success', 'failed', 'expired', 'cancelled'])->default('pending');
            $table->string('payment_method');                  // qris, bni_va, tunai, etc.
            $table->text('payment_number')->nullable();        // QR string / nomor VA
            $table->decimal('total_payment', 15, 2)->nullable(); // amount + fee dari PaKasir
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['shohibul_id', 'status']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_transactions');
    }
};
