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
        Schema::create('bank_kas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('tipe', ['tunai', 'rekening']);
            $table->string('nomor_rekening')->nullable();
            $table->string('atas_nama')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('qr_image_path')->nullable();
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->decimal('saldo_terkini', 15, 2)->default(0);
            $table->enum('status', ['aktif', 'non_aktif'])->default('aktif');
            $table->boolean('visibilitas_publik')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_kas');
    }
};
