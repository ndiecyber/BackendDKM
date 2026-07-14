<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Store an uploaded file. If it's an image, convert to WebP automatically.
     *
     * @return string The stored file path
     */
    public static function storeAsWebp(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        // Jika ekstensi PHP GD tidak ada, langsung fallback ke upload normal agar tidak error (crash)
        if (! extension_loaded('gd') || ! function_exists('imagecreatefromjpeg') || ! function_exists('imagecreatefrompng') || ! function_exists('imagewebp')) {
            return $file->store($directory, $disk);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png']);

        if (! $isImage) {
            // Biarkan file non-gambar diupload secara normal
            return $file->store($directory, $disk);
        }

        // Buat nama file unik
        $filename = Str::random(40).'.webp';
        $fullPath = rtrim($directory, '/').'/'.$filename;

        $img = null;
        if (in_array($extension, ['jpg', 'jpeg'])) {
            $img = @imagecreatefromjpeg($file->getRealPath());
        } elseif ($extension === 'png') {
            $img = @imagecreatefrompng($file->getRealPath());
            if ($img) {
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
            }
        }

        if ($img) {
            // Simpan gambar ke temporary path terlebih dahulu karena imagewebp() butuh path file lokal
            $tempPath = tempnam(sys_get_temp_dir(), 'webp_');
            imagewebp($img, $tempPath, 85);
            imagedestroy($img);

            // Pindahkan dari temporary ke storage Laravel
            Storage::disk($disk)->put($fullPath, file_get_contents($tempPath));
            unlink($tempPath);

            return $fullPath;
        }

        // Jika karena suatu hal fungsi PHP GD gagal, fallback ke upload standar Laravel
        return $file->store($directory, $disk);
    }
}
