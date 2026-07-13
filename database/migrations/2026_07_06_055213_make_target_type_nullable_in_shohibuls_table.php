<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shohibuls', function (Blueprint $table) {
            $table->string('target_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shohibuls', function (Blueprint $table) {
            // Kita tidak bisa mudah mengembalikan ke enum tanpa data loss, jadi biarkan string namun required
            $table->string('target_type')->nullable(false)->change();
        });
    }
};
