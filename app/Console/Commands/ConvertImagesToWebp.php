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

class ConvertImagesToWebp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image:convert-webp {--delete-original : Hapus file asli (JPG/PNG) setelah berhasil diconvert}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mencari gambar JPG/PNG di database, mengonversinya ke WebP, dan mengupdate database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!extension_loaded('gd')) {
            $this->error('Ekstensi PHP GD tidak aktif. Dibutuhkan untuk konversi WebP.');
            return;
        }
        if (!function_exists('imagewebp')) {
            $this->error('Fungsi imagewebp() tidak tersedia di server ini.');
            return;
        }

        $this->info('Memulai konversi gambar ke WebP...');
        $deleteOriginal = $this->option('delete-original');

        if ($deleteOriginal) {
            $this->warn('Opsi --delete-original AKTIF. File asli akan dihapus setelah konversi.');
        }

        $convertedCount = 0;

        // 1. BankKas
        foreach (BankKas::whereNotNull('qr_image_path')->get() as $item) {
            $path = $this->extractRelativePath($item->getRawOriginal('qr_image_path'));
            $newPath = $this->processImage($path, $deleteOriginal);
            if ($newPath && $newPath !== $path) {
                // Update DB dengan full URL atau relative path, tergantung dari accessor sebelumnya
                // Biasanya kita simpan relative path ke storage folder
                $item->qr_image_path = $newPath;
                $item->save();
                $convertedCount++;
                $this->info("Converted BankKas QR ID {$item->id}");
            }
        }

        // 2. TransactionAttachment
        foreach (TransactionAttachment::whereNotNull('file_path')->get() as $item) {
            $path = $this->extractRelativePath($item->getRawOriginal('file_path'));
            $newPath = $this->processImage($path, $deleteOriginal);
            if ($newPath && $newPath !== $path) {
                $item->file_path = $newPath;
                // Update extension if file_name has one
                $item->file_name = preg_replace('/\.(jpe?g|png)$/i', '.webp', $item->file_name);
                $item->save();
                $convertedCount++;
                $this->info("Converted Attachment ID {$item->id}");
            }
        }

        // 3. WebProfile Gallery
        foreach (Gallery::whereNotNull('image_path')->get() as $item) {
            $path = $this->extractRelativePath($item->getRawOriginal('image_path'));
            $newPath = $this->processImage($path, $deleteOriginal);
            if ($newPath && $newPath !== $path) {
                $item->image_path = $newPath;
                $item->save();
                $convertedCount++;
                $this->info("Converted Gallery ID {$item->id}");
            }
        }

        // 4. WebProfile Committee
        foreach (CommitteeMember::whereNotNull('image')->get() as $item) {
            $path = $this->extractRelativePath($item->getRawOriginal('image'));
            $newPath = $this->processImage($path, $deleteOriginal);
            if ($newPath && $newPath !== $path) {
                $item->image = $newPath;
                $item->save();
                $convertedCount++;
                $this->info("Converted Committee ID {$item->id}");
            }
        }

        // 5. WebProfile Event
        foreach (Event::whereNotNull('image')->get() as $item) {
            $path = $this->extractRelativePath($item->getRawOriginal('image'));
            $newPath = $this->processImage($path, $deleteOriginal);
            if ($newPath && $newPath !== $path) {
                $item->image = $newPath;
                $item->save();
                $convertedCount++;
                $this->info("Converted Event ID {$item->id}");
            }
        }

        // 6. WebProfile Article (jika ada)
        if (class_exists('App\Models\WebProfile\Article')) {
            foreach (\App\Models\WebProfile\Article::whereNotNull('image')->get() as $item) {
                $path = $this->extractRelativePath($item->getRawOriginal('image'));
                $newPath = $this->processImage($path, $deleteOriginal);
                if ($newPath && $newPath !== $path) {
                    $item->image = $newPath;
                    $item->save();
                    $convertedCount++;
                    $this->info("Converted Article ID {$item->id}");
                }
            }
        }

        // 7. Services (JSON)
        foreach (Service::all() as $item) {
            $updated = false;
            
            // bg_image
            if ($item->getRawOriginal('bg_image')) {
                $path = $this->extractRelativePath($item->getRawOriginal('bg_image'));
                $newPath = $this->processImage($path, $deleteOriginal);
                if ($newPath && $newPath !== $path) {
                    $item->bg_image = $newPath;
                    $updated = true;
                }
            }

            // supervisorImage in details JSON
            $details = json_decode($item->getRawOriginal('details'), true);
            if (is_array($details) && isset($details['supervisorImage'])) {
                $path = $this->extractRelativePath($details['supervisorImage']);
                $newPath = $this->processImage($path, $deleteOriginal);
                if ($newPath && $newPath !== $path) {
                    $details['supervisorImage'] = $newPath;
                    $item->details = json_encode($details);
                    $updated = true;
                }
            }

            if ($updated) {
                $item->save();
                $convertedCount++;
                $this->info("Converted Service ID {$item->id}");
            }
        }

        // 8. Settings (JSON)
        foreach (Setting::all() as $item) {
            $updated = false;

            if ($item->getRawOriginal('history_image')) {
                $path = $this->extractRelativePath($item->getRawOriginal('history_image'));
                $newPath = $this->processImage($path, $deleteOriginal);
                if ($newPath && $newPath !== $path) {
                    $item->history_image = $newPath;
                    $updated = true;
                }
            }

            $heroImages = json_decode($item->getRawOriginal('hero_images'), true);
            if (is_array($heroImages)) {
                $newHero = [];
                $heroUpdated = false;
                foreach ($heroImages as $img) {
                    $path = $this->extractRelativePath($img);
                    $newPath = $this->processImage($path, $deleteOriginal);
                    if ($newPath && $newPath !== $path) {
                        $newHero[] = $newPath;
                        $heroUpdated = true;
                    } else {
                        $newHero[] = $img;
                    }
                }
                if ($heroUpdated) {
                    $item->hero_images = json_encode($newHero);
                    $updated = true;
                }
            }

            if ($updated) {
                $item->save();
                $convertedCount++;
                $this->info("Converted Setting ID {$item->id}");
            }
        }

        // 9. CtaSettings (JSON)
        foreach (CtaSetting::all() as $item) {
            $sliderImages = json_decode($item->getRawOriginal('slider_images'), true);
            if (is_array($sliderImages)) {
                $newSlider = [];
                $sliderUpdated = false;
                foreach ($sliderImages as $img) {
                    $path = $this->extractRelativePath($img);
                    $newPath = $this->processImage($path, $deleteOriginal);
                    if ($newPath && $newPath !== $path) {
                        $newSlider[] = $newPath;
                        $sliderUpdated = true;
                    } else {
                        $newSlider[] = $img;
                    }
                }
                if ($sliderUpdated) {
                    $item->slider_images = json_encode($newSlider);
                    $item->save();
                    $convertedCount++;
                    $this->info("Converted CtaSetting ID {$item->id}");
                }
            }
        }

        $this->info("Selesai! Berhasil mengonversi {$convertedCount} record gambar.");
    }

    /**
     * Helper untuk mengambil path relatif ke storage.
     */
    private function extractRelativePath($path)
    {
        if (empty($path)) return null;
        if (str_starts_with($path, 'http')) {
            $path = parse_url($path, PHP_URL_PATH);
        }
        $path = preg_replace('/^\/?storage\//', '', $path);
        return ltrim($path, '/');
    }

    /**
     * Konversi gambar ke WebP dan kembalikan path barunya.
     */
    private function processImage($path, $deleteOriginal)
    {
        if (!$path) return null;

        // Jika bukan jpg/jpeg/png, abaikan
        if (!preg_match('/\.(jpe?g|png)$/i', $path)) {
            return $path;
        }

        if (!Storage::disk('public')->exists($path)) {
            return $path; // File fisik tidak ditemukan
        }

        $fullPath = Storage::disk('public')->path($path);
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        $img = null;
        if (in_array($ext, ['jpg', 'jpeg'])) {
            $img = @imagecreatefromjpeg($fullPath);
        } elseif ($ext === 'png') {
            $img = @imagecreatefrompng($fullPath);
            if ($img) {
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
            }
        }

        if (!$img) {
            return $path; // Gagal load gambar
        }

        $newPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
        $newFullPath = Storage::disk('public')->path($newPath);

        // Convert ke webp (quality 85)
        imagewebp($img, $newFullPath, 85);
        imagedestroy($img);

        if ($deleteOriginal && Storage::disk('public')->exists($newPath)) {
            Storage::disk('public')->delete($path);
        }

        return $newPath;
    }
}
