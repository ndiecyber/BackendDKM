<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('qurban_periods')->cascadeOnDelete();
            $table->string('name');                          // "Sapi 1", "Kambing Mandiri"
            $table->enum('target_type', ['sapi', 'kambing']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_groups');
    }
};
