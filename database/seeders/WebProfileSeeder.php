<?php

namespace Database\Seeders;

use App\Models\WebProfile\CommitteeDivision;
use App\Models\WebProfile\CommitteeMember;
use App\Models\WebProfile\CtaProgram;
use App\Models\WebProfile\CtaSetting;
use App\Models\WebProfile\Event;
use App\Models\WebProfile\Gallery;
use App\Models\WebProfile\MasterCategory;
use App\Models\WebProfile\Service;
use App\Models\WebProfile\Setting;
use App\Models\WebProfile\WhatsappContact;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class WebProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        MasterCategory::truncate();
        Setting::truncate();
        Event::truncate();
        Gallery::truncate();
        Service::truncate();
        CommitteeDivision::truncate();
        CommitteeMember::truncate();
        WhatsappContact::truncate();
        CtaSetting::truncate();
        CtaProgram::truncate();

        Schema::enableForeignKeyConstraints();

        $this->seedMasterCategories();
        $this->seedSettings();
        // $this->seedEvents(); // Di-skip sesuai request tidak memasukkan data events
        // $this->seedGalleries(); // Di-skip sesuai request tidak memasukkan data gambar
        $this->seedServices();
        $this->seedCommittee();
        $this->seedCtaSettings();
    }

    private function seedMasterCategories()
    {
        $categories = [
            ['type' => 'kategori', 'name' => 'Kajian', 'description' => 'Kegiatan belajar agama bersama ustadz', 'icon_name' => 'BookOpen', 'color' => null, 'sort_order' => 1],
            ['type' => 'kategori', 'name' => 'Pendidikan', 'description' => 'Kegiatan edukasi dan pembelajaran', 'icon_name' => 'GraduationCap', 'color' => null, 'sort_order' => 2],
            ['type' => 'kategori', 'name' => 'Sosial', 'description' => 'Kegiatan sosial kemasyarakatan', 'icon_name' => 'Users', 'color' => null, 'sort_order' => 3],
            ['type' => 'kategori', 'name' => 'Ibadah', 'description' => 'Kegiatan peribadahan jamaah', 'icon_name' => 'Heart', 'color' => null, 'sort_order' => 4],
            ['type' => 'kategori', 'name' => 'Umum', 'description' => 'Kegiatan umum lainnya', 'icon_name' => 'Info', 'color' => null, 'sort_order' => 5],
        ];

        $types = [
            ['type' => 'tipe_berita', 'name' => 'Berita', 'description' => 'Informasi atau berita terbaru', 'icon_name' => null, 'color' => 'green', 'sort_order' => 1],
            ['type' => 'tipe_berita', 'name' => 'Artikel', 'description' => 'Artikel pembahasan mendalam', 'icon_name' => null, 'color' => 'blue', 'sort_order' => 2],
        ];

        $labels = [
            ['type' => 'label', 'name' => 'Segera', 'description' => 'Akan segera dilaksanakan', 'icon_name' => null, 'color' => 'red', 'sort_order' => 1],
            ['type' => 'label', 'name' => 'Terbatas', 'description' => 'Kuota terbatas', 'icon_name' => null, 'color' => 'yellow', 'sort_order' => 2],
            ['type' => 'label', 'name' => 'Tersedia', 'description' => 'Fasilitas / Layanan tersedia', 'icon_name' => null, 'color' => 'green', 'sort_order' => 3],
            ['type' => 'label', 'name' => 'Baru', 'description' => 'Informasi atau foto terbaru', 'icon_name' => null, 'color' => 'blue', 'sort_order' => 4],
        ];

        $statuses = [
            ['type' => 'status', 'name' => 'Aktif', 'description' => 'Ditampilkan secara publik', 'icon_name' => null, 'color' => 'green', 'sort_order' => 1],
            ['type' => 'status', 'name' => 'Nonaktif', 'description' => 'Disembunyikan dari publik', 'icon_name' => null, 'color' => 'gray', 'sort_order' => 2],
        ];

        MasterCategory::insert(array_merge($categories, $types, $labels, $statuses));
    }

    private function seedSettings()
    {
        Setting::create([
            'nama_masjid' => 'Perumahan Arjamukti Kencana Raya',
            'slogan' => "Membangun *Iman*,\nIlmu, dan *Ukhuwah*",
            'deskripsi_sambutan' => 'Selamat datang di Masjid Jami Kassiti Perum Arjamukti Kencana Raya Arjasari, Leuwisari, Kab. Tasikmalaya. Bergabunglah bersama kami dalam ibadah, pembelajaran, dakwah, dan pelayanan umat.',
            'sejarah_singkat' => "Masjid Jami Kassiti yang berlokasi di Perum Arjamukti Kencana Raya, Arjasari, Leuwisari, Kab. Tasikmalaya, adalah pusat ibadah dan kegiatan keislaman yang melayani umat dengan penuh dedikasi. Kami berkomitmen untuk menjadi rumah Allah yang menyejukkan, tempat berkumpulnya jamaah dalam menuntut ilmu, beribadah, dan mempererat ukhuwah islamiah.\n\nDengan berbagai program kegiatan rutin seperti kajian, TPA/TPQ, dan kegiatan sosial, kami berusaha membangun generasi muslim yang beriman, berilmu, dan bermanfaat bagi masyarakat sekitar.",
            'link_instagram' => 'https://instagram.com/masjidjamikassiti',
            'link_facebook' => 'https://facebook.com/masjidjamikassiti',
            'link_youtube' => 'https://youtube.com/@masjidjamikassiti',
            'link_twitter' => '',
            'link_tiktok' => '',
            'no_whatsapp' => '6285320132014',
            'email' => 'dkmjami.kassiti@gmail.com',
            'telepon_kantor' => '',
            'link_maps' => 'https://maps.app.goo.gl/HMDmpx7zZFn8GRUaA',
            'maps_iframe' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126748.56347862248!2d107.97120309999998!3d-7.3621539!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6f56e0766e01a1%3A0x673413cb1fb6f2bd!2sTasikmalaya%2C%20Tasikmalaya%20Regency%2C%20West%20Java!5e0!3m2!1sen!2sid!4v1718000000000!5m2!1sen!2sid" width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
            'alamat_lengkap' => 'Masjid Kassiti Arjamukti Kencana Raya, Arjasari, Kec. Leuwisari, Kabupaten Tasikmalaya, Jawa Barat',
            'kota' => 'Kab. Tasikmalaya',
            'kodepos' => '46464',
            'floating_card_title' => 'Masjid Kassiti',
            'floating_card_desc' => 'Pusat kegiatan ibadah dan sosial kemasyarakatan di Perumahan Arjamukti',
            'tahun_berdiri' => 2015,
            'jamaah_aktif' => 200,
            'hero_images' => [],
            'history_image' => null,
            'committee_description' => 'Mengenal lebih dekat para pelayan jamaah Masjid Jami Kassiti periode 2023-2026.',
        ]);

        WhatsappContact::insert([
            ['name' => 'Bpk. Randi Rizal', 'number' => '6285320132014', 'sort_order' => 1],
        ]);
    }

    private function seedCtaSettings()
    {
        $cta = CtaSetting::create([
            'title' => 'Investasi Terbaik Untuk Akhirat',
            'subtitle' => 'Setiap rupiah yang Anda sedekahkan tidak hanya memakmurkan masjid, tapi juga mengalirkan pahala yang tak terputus bagi Anda dan keluarga.',
            'quote' => '"Barang siapa yang membangun masjid karena Allah, maka Allah akan membangunkan baginya rumah di surga."',
            'quote_source' => 'HR. Bukhari & Muslim',
            'total_donors' => 128,
            'slider_images' => [],
        ]);

        CtaProgram::insert([
            ['cta_setting_id' => $cta->id, 'name' => 'Renovasi Aula Utama', 'progress' => 75, 'sort_order' => 1],
            ['cta_setting_id' => $cta->id, 'name' => 'Sarana Pendidikan TPQ', 'progress' => 60, 'sort_order' => 2],
        ]);
    }

    private function seedServices()
    {
        Service::create([
            'title' => 'Sholat Berjamaah',
            'category' => 'Ibadah',
            'icon' => 'Sholat',
            'badge' => 'Tersedia',
            'bg_image' => null,
            'description' => 'Sholat lima waktu dan sholat Jumat berjamaah dengan imam yang berpengalaman.',
            'details' => [
                'fullDescription' => 'Masjid Jami Kassiti menyelenggarakan sholat berjamaah lima waktu secara rutin, dilengkapi dengan fasilitas tempat wudhu yang bersih, karpet yang nyaman, dan pendingin ruangan. Kami juga menyelenggarakan Sholat Jumat dengan khatib-khatib pilihan yang membawakan materi khutbah inspiratif dan aktual.',
                'schedule' => 'Setiap Waktu Sholat & Jumat 11.30 WIB',
                'location' => 'Ruang Utama & Lantai 2 Masjid Jami Kassiti',
                'supervisor' => 'DKM Masjid (Bpk. H. Irvan Ruchiat)',
                'supervisorImage' => null,
                'requirements' => ['Pakaian sopan dan menutup aurat', 'Menjaga ketertiban dan kebersihan'],
            ],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Service::create([
            'title' => 'Kajian Rutin',
            'category' => 'Pendidikan',
            'icon' => 'BookOpen',
            'badge' => 'Terjadwal',
            'bg_image' => null,
            'description' => 'Kajian ilmu agama setiap pekan meliputi tafsir, hadits, fiqih, dan akhlak.',
            'details' => [
                'fullDescription' => 'Program kajian rutin terbuka untuk umum (Ikhwan & Akhwat) yang diisi oleh asatidzah berkompeten. Materi kajian disusun secara terstruktur mulai dari dasar hingga lanjutan, mencakup pembahasan Tafsir Al-Quran, Hadits Arbain, Fiqih Ibadah, dan Sirah Nabawiyah.',
                'schedule' => "Rabu (Ba'da Maghrib) & Ahad (Ba'da Subuh)",
                'location' => 'Ruang Utama Masjid',
                'supervisor' => 'Divisi Dakwah (Bpk. H. Irvan Ruchiat)',
                'supervisorImage' => null,
                'requirements' => ['Membawa alat tulis (opsional)', 'Terbuka untuk umum'],
            ],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Service::create([
            'title' => "TPQ (Taman Pendidikan Al-Qur'an)",
            'category' => 'Pendidikan',
            'icon' => 'GraduationCap',
            'badge' => 'Pendaftaran Buka',
            'bg_image' => null,
            'description' => 'Program pendidikan Al-Quran untuk anak-anak dengan metode pembelajaran modern.',
            'details' => [
                'fullDescription' => 'Taman Pendidikan Al-Quran (TPQ) Masjid Jami Kassiti mendidik generasi muda agar cinta Al-Quran. Kurikulum mencakup baca tulis Al-Quran (Metode Iqro/Tilawati), hafalan surat pendek, doa sehari-hari, praktik ibadah, dan pembentukan akhlakul karimah.',
                'schedule' => 'Senin - Kamis, 15.30 - 17.00 WIB',
                'location' => 'Ruang Kelas TPA (Lantai 2)',
                'supervisor' => 'Kepala TPA (Usth. Ai Jamaliah)',
                'supervisorImage' => null,
                'requirements' => ['Usia 5 - 12 Tahun', 'Mengisi formulir pendaftaran', 'Fotokopi Akta Kelahiran'],
                'staff' => [
                    ['name' => 'Usth. Ai Jamaliah', 'role' => 'Kepala Sekolah', 'image' => null],
                    ['name' => 'Usth. Rini Dewi Anggiani', 'role' => 'Guru', 'image' => null],
                    ['name' => 'Usth. Dede Asiah', 'role' => 'Guru', 'image' => null],
                    ['name' => 'Usth. Rani Rahmayati', 'role' => 'Guru', 'image' => null],
                ],
            ],
            'is_active' => true,
            'sort_order' => 3,
        ]);

        Service::create([
            'title' => 'DTA (Diniyah Takmiliyah Awaliyah)',
            'category' => 'Pendidikan',
            'icon' => 'GraduationCap',
            'badge' => 'Pendaftaran Buka',
            'bg_image' => null,
            'description' => 'Program pendidikan keagamaan Islam non-formal sebagai pelengkap pendidikan formal anak.',
            'details' => [
                'fullDescription' => 'Diniyah Takmiliyah Awaliyah (DTA) Masjid Jami Kassiti menyelenggarakan pendidikan keagamaan Islam bagi anak-anak usia sekolah dasar. Program ini dirancang untuk memperkuat pemahaman agama anak di luar jam sekolah formal dengan kurikulum terpadu meliputi Aqidah, Akhlak, Fiqih Ibadah, Tarikh (Sejarah Islam), Hadits, serta praktik ibadah sehari-hari.',
                'schedule' => 'Senin - Sabtu, 15.30 - 17.00 WIB',
                'location' => 'Ruang Kelas Lantai 2 Masjid Jami Kassiti',
                'supervisor' => 'Kepala DTA (Usth. Neneng Aam S.M)',
                'supervisorImage' => null,
                'requirements' => ['Mengisi formulir pendaftaran', 'Fotokopi Akta Kelahiran & Kartu Keluarga', 'Membayar biaya administrasi pendaftaran'],
                'staff' => [
                    ['name' => 'Usth. Neneng Aam Siti Marhamah', 'role' => 'Kepala DTA', 'image' => null],
                    ['name' => 'Usth. Raya', 'role' => 'Guru', 'image' => null],
                    ['name' => 'Usth. Sani', 'role' => 'Guru', 'image' => null],
                    ['name' => 'Bpk. Dani R', 'role' => 'Guru', 'image' => null],
                ],
            ],
            'is_active' => true,
            'sort_order' => 4,
        ]);

        Service::create([
            'title' => 'Zakat & Infaq',
            'category' => 'Ibadah',
            'icon' => 'HandCoins',
            'badge' => 'Aktif',
            'bg_image' => null,
            'description' => 'Pengelolaan dan penyaluran zakat, infaq, dan sedekah secara transparan.',
            'details' => [
                'fullDescription' => 'Unit Pengumpul Zakat (UPZ) Masjid Jami Kassiti memfasilitasi jamaah dalam menunaikan Zakat Fitrah, Zakat Maal, Infaq, dan Sedekah. Dana yang terkumpul disalurkan kepada asnaf yang berhak dan untuk operasional kemakmuran masjid dengan laporan keuangan yang dipublikasikan rutin.',
                'schedule' => 'Layanan 24 Jam (Transfer) / 08.00-17.00 (Offline)',
                'location' => 'Kantor Sekretariat Masjid',
                'supervisor' => 'Divisi ZISWAF (Bpk. ALI M Abduh)',
                'supervisorImage' => null,
                'requirements' => ['Menerima konsultasi hitung Zakat Maal', 'Menerima jemput zakat khusus area terdekat'],
            ],
            'is_active' => true,
            'sort_order' => 5,
        ]);

        Service::create([
            'title' => 'Remaja Masjid',
            'category' => 'Sosial',
            'icon' => 'Users',
            'badge' => 'Aktif',
            'bg_image' => null,
            'description' => 'Wadah kegiatan dan kreativitas pemuda-pemudi muslim Masjid Jami Kassiti.',
            'details' => [
                'fullDescription' => 'Remaja Masjid Jami Kassiti (IRMAS) merupakan wadah pembinaan, kreativitas, dan kolaborasi pemuda-pemudi muslim dalam memakmurkan masjid. Kegiatan meliputi kajian kepemudaan, pelatihan skill, olahraga, bakti sosial, dan pengembangan seni budaya Islami.',
                'schedule' => 'Setiap Akhir Pekan (Sabtu & Ahad)',
                'location' => 'Masjid Jami Kassiti & Lingkungan Sekitar',
                'supervisor' => 'Koordinator IRMAS (Sdr. Gojali)',
                'supervisorImage' => null,
                'requirements' => ['Pemuda/pemudi usia 13 - 25 tahun', 'Memiliki semangat belajar dan berorganisasi', 'Mengisi form keanggotaan'],
                'staff' => [
                    ['name' => "Bpk. Gojali Abdul Syafi'i", 'role' => 'Koordinator', 'image' => null],
                    ['name' => 'Usth. Rani Rahmayati', 'role' => 'Anggota', 'image' => null],
                    ['name' => 'Usth. Rayanthi', 'role' => 'Anggota', 'image' => null],
                ],
            ],
            'is_active' => true,
            'sort_order' => 6,
        ]);
    }

    private function seedCommittee()
    {
        // Dewan Penasihat
        $membersPenasihat = [
            ['group' => 'dewan_penasihat', 'name' => 'Ust. H. Iwa Kurniawan', 'role' => 'Dewan Penasihat', 'image' => null, 'is_leader' => false, 'sort_order' => 1],
            ['group' => 'dewan_penasihat', 'name' => 'Ust. H. Ade Karom', 'role' => 'Dewan Penasihat', 'image' => null, 'is_leader' => false, 'sort_order' => 2],
            ['group' => 'dewan_penasihat', 'name' => 'Bpk. Sudiana Maska', 'role' => 'Dewan Penasihat', 'image' => null, 'is_leader' => false, 'sort_order' => 3],
            ['group' => 'dewan_penasihat', 'name' => 'Bpk. H. Usman', 'role' => 'Dewan Penasihat', 'image' => null, 'is_leader' => false, 'sort_order' => 4],
            ['group' => 'dewan_penasihat', 'name' => 'Bpk. Ayi Sunarwan', 'role' => 'Ketua RW 07', 'image' => null, 'is_leader' => true, 'sort_order' => 5],
        ];

        // Pengurus Harian
        $membersHarian = [
            ['group' => 'pengurus_harian', 'name' => "Ust. H. Ahmad Nasa'i", 'role' => 'Ketua DKMJ', 'image' => null, 'is_leader' => true, 'sort_order' => 1],
            ['group' => 'pengurus_harian', 'name' => 'Ust. H. M. Ainur Rofik', 'role' => 'Sekretaris', 'image' => null, 'is_leader' => false, 'sort_order' => 2],
            ['group' => 'pengurus_harian', 'name' => 'Ust. Randi Rizal', 'role' => 'Bendahara', 'image' => null, 'is_leader' => false, 'sort_order' => 3],
        ];

        CommitteeMember::insert(array_merge($membersPenasihat, $membersHarian));

        // Divisi
        $divisiDakwah = CommitteeDivision::create(['slug' => 'seksi-pendidikan-dakwah', 'name' => 'Seksi Pendidikan & Dakwah', 'sort_order' => 1]);
        $divisiEkonomi = CommitteeDivision::create(['slug' => 'seksi-ekonomi-wakaf', 'name' => 'Seksi Ekonomi & Wakaf', 'sort_order' => 2]);
        $divisiLogistik = CommitteeDivision::create(['slug' => 'seksi-peralatan-logistik', 'name' => 'Seksi Peralatan & Logistik', 'sort_order' => 3]);
        $divisiRemaja = CommitteeDivision::create(['slug' => 'remaja-masjid', 'name' => 'Remaja Masjid', 'sort_order' => 4]);

        $membersDivisi = [
            // Dakwah
            ['group' => 'divisi', 'division_id' => $divisiDakwah->id, 'name' => 'Ust. H. Irvan Ruchiat', 'role' => 'Koordinator', 'image' => null, 'is_leader' => true, 'sort_order' => 1],
            ['group' => 'divisi', 'division_id' => $divisiDakwah->id, 'name' => 'Ust. H. Dani Ramdhani', 'role' => 'Anggota', 'image' => null, 'is_leader' => false, 'sort_order' => 2],
            ['group' => 'divisi', 'division_id' => $divisiDakwah->id, 'name' => 'Usth. Neneng Aam Siti Marhamah', 'role' => 'Anggota', 'image' => null, 'is_leader' => false, 'sort_order' => 3],
            ['group' => 'divisi', 'division_id' => $divisiDakwah->id, 'name' => 'Usth. Ai Jamaliah', 'role' => 'Anggota', 'image' => null, 'is_leader' => false, 'sort_order' => 4],
            ['group' => 'divisi', 'division_id' => $divisiDakwah->id, 'name' => 'Usth. Rini Dewi Anggiani', 'role' => 'Anggota', 'image' => null, 'is_leader' => false, 'sort_order' => 5],
            ['group' => 'divisi', 'division_id' => $divisiDakwah->id, 'name' => 'Usth. Dede Asiah', 'role' => 'Anggota', 'image' => null, 'is_leader' => false, 'sort_order' => 6],

            // Ekonomi
            ['group' => 'divisi', 'division_id' => $divisiEkonomi->id, 'name' => 'Bpk. Ali M. Abduh', 'role' => 'Koordinator', 'image' => null, 'is_leader' => true, 'sort_order' => 1],
            ['group' => 'divisi', 'division_id' => $divisiEkonomi->id, 'name' => 'Bpk. Ujang Kurnia', 'role' => 'Anggota', 'image' => null, 'is_leader' => false, 'sort_order' => 2],
            ['group' => 'divisi', 'division_id' => $divisiEkonomi->id, 'name' => 'Bpk. Erwin Darmawan', 'role' => 'Anggota', 'image' => null, 'is_leader' => false, 'sort_order' => 3],
            ['group' => 'divisi', 'division_id' => $divisiEkonomi->id, 'name' => 'Bpk. Ade Ramdhani', 'role' => 'Anggota', 'image' => null, 'is_leader' => false, 'sort_order' => 4],

            // Logistik
            ['group' => 'divisi', 'division_id' => $divisiLogistik->id, 'name' => 'Bpk. H. Redi Sasriandi', 'role' => 'Koordinator', 'image' => null, 'is_leader' => true, 'sort_order' => 1],
            ['group' => 'divisi', 'division_id' => $divisiLogistik->id, 'name' => 'Bpk. Aditya Astra Prayudha', 'role' => 'Anggota', 'image' => null, 'is_leader' => false, 'sort_order' => 2],
            ['group' => 'divisi', 'division_id' => $divisiLogistik->id, 'name' => 'Bpk. Sukardi', 'role' => 'Anggota', 'image' => null, 'is_leader' => false, 'sort_order' => 3],
            ['group' => 'divisi', 'division_id' => $divisiLogistik->id, 'name' => 'Bpk. Nanang Barkah', 'role' => 'Anggota', 'image' => null, 'is_leader' => false, 'sort_order' => 4],

            // Remaja
            ['group' => 'divisi', 'division_id' => $divisiRemaja->id, 'name' => "Bpk. Gojali Abdul Syafi'i", 'role' => 'Koordinator', 'image' => null, 'is_leader' => true, 'sort_order' => 1],
            ['group' => 'divisi', 'division_id' => $divisiRemaja->id, 'name' => 'Usth. Rani Rahmayati', 'role' => 'Anggota', 'image' => null, 'is_leader' => false, 'sort_order' => 2],
            ['group' => 'divisi', 'division_id' => $divisiRemaja->id, 'name' => 'Usth. Rayanthi', 'role' => 'Anggota', 'image' => null, 'is_leader' => false, 'sort_order' => 3],
        ];

        CommitteeMember::insert($membersDivisi);
    }
}
