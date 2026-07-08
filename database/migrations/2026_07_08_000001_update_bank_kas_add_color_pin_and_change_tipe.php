<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bank_kas', function (Blueprint $table) {
            $table->string('color', 20)->nullable()->after('status');
            $table->boolean('is_pinned')->default(false)->after('color');
        });

        // Change tipe from enum to string for flexibility (supports: tunai, rekening, dompet_digital, etc.)
        // Step 1: Add a temporary string column
        Schema::table('bank_kas', function (Blueprint $table) {
            $table->string('tipe_new', 50)->default('tunai')->after('tipe');
        });

        // Step 2: Copy data
        DB::table('bank_kas')->update(['tipe_new' => DB::raw('tipe::text')]);

        // Step 3: Drop old enum column and rename new column
        Schema::table('bank_kas', function (Blueprint $table) {
            $table->dropColumn('tipe');
        });

        Schema::table('bank_kas', function (Blueprint $table) {
            $table->renameColumn('tipe_new', 'tipe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert tipe back to enum (only values that existed before)
        Schema::table('bank_kas', function (Blueprint $table) {
            $table->string('tipe_old', 50)->default('tunai')->after('nama');
        });

        DB::table('bank_kas')->update(['tipe_old' => DB::raw('tipe')]);

        Schema::table('bank_kas', function (Blueprint $table) {
            $table->dropColumn('tipe');
        });

        Schema::table('bank_kas', function (Blueprint $table) {
            $table->renameColumn('tipe_old', 'tipe');
        });

        Schema::table('bank_kas', function (Blueprint $table) {
            $table->dropColumn(['color', 'is_pinned']);
        });
    }
};
