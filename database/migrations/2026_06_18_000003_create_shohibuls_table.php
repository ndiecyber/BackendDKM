<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shohibuls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('qurban_periods')->cascadeOnDelete();
            $table->foreignId('animal_group_id')->nullable()->constrained('animal_groups')->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->text('address');
            $table->enum('target_type', ['sapi', 'kambing']);
            $table->decimal('target_amount', 15, 2);         // Total target harga hewan
            $table->decimal('collected_amount', 15, 2)->default(0);
            $table->string('last_payment_month')->nullable(); // "2026-06"
            $table->timestamps();
            $table->softDeletes();

            $table->index(['period_id', 'target_type']);
            $table->index(['name', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shohibuls');
    }
};
