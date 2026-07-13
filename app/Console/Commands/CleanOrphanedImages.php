<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\TransactionAttachment;
use App\Models\BankKas;
use App\Models\WebProfile\Setting;
use App\Models\WebProfile\Service;
use App\Models\WebProfile\Gallery;
use App\Models\WebProfile\CtaSetting;
use App\Models\WebProfile\CommitteeMember;
use App\Models\WebProfile\Event;

class CleanOrphanedImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:orphaned-images {--dry-run : Tampilkan daftar file yang akan dihapus tanpa menghapusnya}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mencari dan menghapus gambar di folder storage yang sudah tidak dipakai di database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pengecekan gambar orphaned (tidak terpakai)...');
        
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE: Tidak ada file yang akan benar-benar dihapus, hanya menampilkan daftar.');
        }

        // 1. Kumpulkan semua path file gambar yang tercatat di database
        $usedPaths = [];

        // Bank Kas (QR Code)
        $usedPaths = array_merge($usedPaths, BankKas::whereNotNull('qr_image_path')->pluck('qr_image_path')->toArray());
        
        // Bukti Transfer (Transaction Attachments)
        $usedPaths = array_merge($usedPaths, TransactionAttachment::whereNotNull('file_path')->pluck('file_path')->toArray());
        
        // WebProfile Gallery
        $usedPaths = array_merge($usedPaths, Gallery::whereNotNull('image_path')->pluck('image_path')->toArray());
        
        // WebProfile Committee
        $usedPaths = array_merge($usedPaths, CommitteeMember::whereNotNull('image')->pluck('image')->toArray());
        
        // WebProfile Event
        $usedPaths = array_merge($usedPaths, Event::whereNotNull('image')->pluck('image')->toArray());
        
        // Jika ada model Article
        if (class_exists('App\Models\WebProfile\Article')) {
            $usedPaths = array_merge($usedPaths, \App\Models\WebProfile\Article::whereNotNull('image')->pluck('image')->toArray());
        }

        // Services (bg_image dan supervisorImage di JSON details)
        $services = Service::all();
        foreach ($services as $service) {
            if ($service->getRawOriginal('bg_image')) {
                $usedPaths[] = $service->getRawOriginal('bg_image');
            }
            $details = json_decode($service->getRawOriginal('details'), true);
            if (is_array($details) && isset($details['supervisorImage'])) {
                $usedPaths[] = $details['supervisorImage'];
            }
        }

        // Settings (history_image dan hero_images)
        $settings = Setting::all();
        foreach ($settings as $setting) {
            if ($setting->getRawOriginal('history_image')) {
                $usedPaths[] = $setting->getRawOriginal('history_image');
            }
            $heroImages = json_decode($setting->getRawOriginal('hero_images'), true);
            if (is_array($heroImages)) {
                foreach ($heroImages as $img) {
                    $usedPaths[] = $img;
                }
            }
        }

        // CTA Settings (slider_images)
        $ctaSettings = CtaSetting::all();
        foreach ($ctaSettings as $cta) {
            $sliderImages = json_decode($cta->getRawOriginal('slider_images'), true);
            if (is_array($sliderImages)) {
                foreach ($sliderImages as $img) {
                    $usedPaths[] = $img;
                }
            }
        }

        // Normalisasi path agar cocok dengan format dari Storage::disk('public')->allFiles()
        $normalizedUsedPaths = [];
        foreach ($usedPaths as $path) {
            if (empty($path)) continue;
            
            // Jika tersimpan sebagai URL lengkap, ambil path-nya saja
            if (str_starts_with($path, 'http')) {
                $path = parse_url($path, PHP_URL_PATH);
            }
            
            // Hapus awalan '/storage/' atau 'storage/' agar path-nya relatif terhadap disk 'public'
            $path = preg_replace('/^\/?storage\//', '', $path);
            $path = ltrim($path, '/');
            
            $normalizedUsedPaths[] = $path;
        }
        
        // Buat agar tidak ada duplikasi
        $normalizedUsedPaths = array_unique($normalizedUsedPaths);

        // 2. Ambil semua file yang ada di folder storage/app/public
        $allFiles = Storage::disk('public')->allFiles();
        
        $orphanedFiles = [];
        
        foreach ($allFiles as $file) {
            // Abaikan file sistem / tersembunyi
            if (str_starts_with(basename($file), '.')) {
                continue;
            }
            
            // Jika file di storage TIDAK ADA di daftar database, masukkan ke daftar orphaned
            if (!in_array($file, $normalizedUsedPaths)) {
                $orphanedFiles[] = $file;
            }
        }

        if (empty($orphanedFiles)) {
            $this->info('Selamat! Tidak ada file sampah (orphaned images). Storage Anda bersih.');
            return;
        }

        $this->warn('Ditemukan ' . count($orphanedFiles) . ' file sampah (tidak terpakai di DB).');
        
        if ($this->confirm('Apakah Anda ingin melihat daftar file sampah tersebut?')) {
            foreach ($orphanedFiles as $file) {
                $this->line($file);
            }
        }

        if (!$isDryRun) {
            if ($this->confirm('Apakah Anda YAKIN ingin menghapus file-file ini secara PERMANEN?')) {
                $deletedCount = 0;
                foreach ($orphanedFiles as $file) {
                    if (Storage::disk('public')->delete($file)) {
                        $deletedCount++;
                    }
                }
                $this->info("Berhasil menghapus $deletedCount file sampah.");
            } else {
                $this->info('Aksi dibatalkan. File tetap aman.');
            }
        } else {
            $this->info('Dry run selesai. Tidak ada file yang dihapus. Jalankan tanpa --dry-run untuk menghapus sungguhan.');
        }
    }
}
