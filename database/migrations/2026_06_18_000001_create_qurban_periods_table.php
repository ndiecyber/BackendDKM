<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // "Qurban 1448 H / 2027 M"
            $table->decimal('sapi_price_per_slot', 15, 2);   // Harga per orang (1/7 sapi)
            $table->decimal('kambing_price', 15, 2);         // Harga per ekor kambing
            $table->date('deadline_date');                    // Batas akhir periode
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_periods');
    }
};
