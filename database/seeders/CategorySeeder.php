<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed default transaction categories.
     */
    public function run(): void
    {
        $categories = [
            // Pemasukan
            ['nama' => 'Infaq Kotak Amal', 'tipe' => 'pemasukan', 'deskripsi' => 'Pemasukan dari kotak amal masjid'],
            ['nama' => 'Infaq Jumat', 'tipe' => 'pemasukan', 'deskripsi' => 'Pemasukan dari infaq sholat Jumat'],
            ['nama' => 'Zakat', 'tipe' => 'pemasukan', 'deskripsi' => 'Pemasukan dari zakat (fitrah/maal)'],
            ['nama' => 'Donasi Pembangunan', 'tipe' => 'pemasukan', 'deskripsi' => 'Donasi khusus untuk pembangunan masjid'],
            ['nama' => 'Lain-lain Pemasukan', 'tipe' => 'pemasukan', 'deskripsi' => 'Pemasukan lain yang tidak termasuk kategori di atas'],

            // Pengeluaran
            ['nama' => 'Operasional', 'tipe' => 'pengeluaran', 'deskripsi' => 'Biaya operasional harian masjid (listrik, air, kebersihan, dll)'],
            ['nama' => 'Pembangunan', 'tipe' => 'pengeluaran', 'deskripsi' => 'Biaya pembangunan dan renovasi masjid'],
            ['nama' => 'Sosial / Santunan', 'tipe' => 'pengeluaran', 'deskripsi' => 'Santunan anak yatim, dhuafa, dan kegiatan sosial'],
            ['nama' => 'Honor / Gaji', 'tipe' => 'pengeluaran', 'deskripsi' => 'Honor marbot, imam, khotib, dan tenaga lainnya'],
            ['nama' => 'Lain-lain Pengeluaran', 'tipe' => 'pengeluaran', 'deskripsi' => 'Pengeluaran lain yang tidak termasuk kategori di atas'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['nama' => $category['nama'], 'tipe' => $category['tipe']],
                $category
            );
        }
    }
}
