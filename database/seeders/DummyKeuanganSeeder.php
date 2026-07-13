<?php

namespace Database\Seeders;

use App\Models\BankKas;
use App\Models\Category;
use App\Models\Program;
use App\Models\User;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DummyKeuanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transactionService = app(TransactionService::class);

        // Dapatkan user pertama untuk created_by (Super Admin)
        $user = User::first() ?? User::factory()->create();

        // 1. Setup Bank & Kas
        $kasUtama = BankKas::firstOrCreate(
            ['nama' => 'Kas Masjid', 'tipe' => 'tunai'],
            ['nomor_rekening' => '-', 'status' => 'aktif', 'saldo_awal' => 0, 'saldo_terkini' => 0]
        );
        $bankBSI = BankKas::firstOrCreate(
            ['nama' => 'BSI Operasional', 'tipe' => 'rekening'],
            ['nomor_rekening' => '7123456789', 'status' => 'aktif', 'saldo_awal' => 0, 'saldo_terkini' => 0]
        );

        // 2. Setup Kategori Keuangan
        $katInfaq = Category::firstOrCreate(['nama' => 'Infaq & Sedekah', 'tipe' => 'pemasukan']);
        $katJumat = Category::firstOrCreate(['nama' => 'Kotak Amal Jumat', 'tipe' => 'pemasukan']);
        $katDonasi = Category::firstOrCreate(['nama' => 'Donasi Program', 'tipe' => 'pemasukan']);

        $katListrik = Category::firstOrCreate(['nama' => 'Bayar Listrik & Air', 'tipe' => 'pengeluaran']);
        $katKajian = Category::firstOrCreate(['nama' => 'Honor Pemateri Kajian', 'tipe' => 'pengeluaran']);
        $katKebersihan = Category::firstOrCreate(['nama' => 'Alat Kebersihan', 'tipe' => 'pengeluaran']);
        $katPerbaikan = Category::firstOrCreate(['nama' => 'Perbaikan Fisik', 'tipe' => 'pengeluaran']);

        // 3. Setup Program (Beberapa transaksi punya program, beberapa tidak)
        $progRamadhan = Program::firstOrCreate(
            ['nama' => 'Ramadhan 1447 H'],
            ['deskripsi' => 'Rangkaian kegiatan bulan suci Ramadhan', 'status' => 'aktif']
        );
        $progYatim = Program::firstOrCreate(
            ['nama' => 'Santunan Yatim Bulanan'],
            ['deskripsi' => 'Pemberian santunan rutin tiap bulan', 'status' => 'aktif']
        );

        $categoriesIn = [$katInfaq->id, $katJumat->id, $katDonasi->id];
        $categoriesOut = [$katListrik->id, $katKajian->id, $katKebersihan->id, $katPerbaikan->id];

        // Memberi probabilitas transaksi tanpa program lebih besar
        $programs = [$progRamadhan->id, $progYatim->id, null, null, null];
        $banks = [$kasUtama->id, $bankBSI->id];

        $now = Carbon::now();

        // 4. Generate Transactions (Generate ke belakang selama 3 bulan)
        $this->command->info('Mulai generate data dummy transaksi keuangan selama 90 hari ke belakang...');

        for ($i = 90; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);

            // Randomly 2-5 transactions per day agar datanya cukup padat
            $dailyCount = rand(2, 5);

            for ($j = 0; $j < $dailyCount; $j++) {
                $type = rand(1, 100);

                // 60% Pemasukan, 30% Pengeluaran, 10% Mutasi (Transfer)
                if ($type <= 60) {
                    $transactionService->createIncome([
                        'nama' => 'Pemasukan Harian '.$date->format('d M'),
                        'deskripsi' => 'Penerimaan infaq/sedekah jamaah',
                        'nominal' => rand(50, 1500) * 1000,
                        'tanggal' => $date->format('Y-m-d'),
                        'category_id' => $categoriesIn[array_rand($categoriesIn)],
                        'program_id' => $programs[array_rand($programs)],
                        'bank_kas_tujuan_id' => $banks[array_rand($banks)],
                        'status' => 'approved',
                        'created_by' => $user->id,
                    ]);
                } elseif ($type <= 90) {
                    $transactionService->createExpense([
                        'nama' => 'Pengeluaran Operasional '.$date->format('d M'),
                        'deskripsi' => 'Pembayaran rutin masjid',
                        'nominal' => rand(20, 800) * 1000,
                        'tanggal' => $date->format('Y-m-d'),
                        'category_id' => $categoriesOut[array_rand($categoriesOut)],
                        'program_id' => $programs[array_rand($programs)],
                        'bank_kas_asal_id' => $banks[array_rand($banks)],
                        'status' => 'approved',
                        'created_by' => $user->id,
                    ]);
                } else {
                    $asal = $banks[array_rand($banks)];
                    // Tujuan mutasi tidak boleh sama dengan asal
                    $tujuan = $asal === $kasUtama->id ? $bankBSI->id : $kasUtama->id;

                    $transactionService->createTransfer([
                        'nama' => 'Setor/Tarik Dana '.$date->format('d M'),
                        'deskripsi' => 'Pemindahan saldo antar bank dan kas',
                        'nominal' => rand(200, 2000) * 1000,
                        'tanggal' => $date->format('Y-m-d'),
                        'bank_kas_asal_id' => $asal,
                        'bank_kas_tujuan_id' => $tujuan,
                        'biaya_admin' => rand(0, 1) ? 2500 : 0,
                        'status' => 'approved',
                        'created_by' => $user->id,
                    ]);
                }
            }
        }

        $this->command->info('Sukses! Ratusan data transaksi telah berhasil digenerate.');
    }
}
