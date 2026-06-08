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
        Schema::table('jamaah', function (Blueprint $table) {
            $table->enum('kategori_entitas', ['individu', 'organisasi'])->default('individu')->after('nama_lengkap');
            $table->string('tempat_lahir')->nullable()->after('jenis_kelamin');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            $table->text('alamat')->nullable()->after('email');
            $table->enum('tipe_jamaah', ['internal_dkm', 'warga_sekitar', 'eksternal', 'mitra_organisasi'])->default('warga_sekitar')->after('alamat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jamaah', function (Blueprint $table) {
            $table->dropColumn([
                'kategori_entitas',
                'tempat_lahir',
                'tanggal_lahir',
                'alamat',
                'tipe_jamaah',
            ]);
        });
    }
};
