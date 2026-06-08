<?php

namespace Database\Seeders;

use App\Models\BankKas;
use Illuminate\Database\Seeder;

class BankKasSeeder extends Seeder
{
    /**
     * Seed default bank/kas account.
     */
    public function run(): void
    {
        BankKas::firstOrCreate(
            ['nama' => 'Kas Tunai'],
            [
                'nama' => 'Kas Tunai',
                'tipe' => 'tunai',
                'deskripsi' => 'Kas tunai utama masjid',
                'saldo_awal' => 0,
                'saldo_terkini' => 0,
                'status' => 'aktif',
                'visibilitas_publik' => false,
            ]
        );
    }
}
