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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_transaksi')->unique();
            $table->enum('tipe', ['pemasukan', 'pengeluaran', 'transfer']);
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->decimal('nominal', 15, 2);
            $table->date('tanggal');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('bank_kas_asal_id')->nullable()->constrained('bank_kas')->nullOnDelete();
            $table->foreignId('bank_kas_tujuan_id')->nullable()->constrained('bank_kas')->nullOnDelete();
            $table->foreignId('jamaah_id')->nullable()->constrained('jamaah')->nullOnDelete();
            $table->decimal('biaya_admin', 15, 2)->nullable();
            $table->enum('status', ['draft', 'pending', 'approved'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index(['tipe', 'status']);
            $table->index('tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
