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
        try {
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
                ob_start();
                imagewebp($img, null, 85);
                $imageContent = ob_get_clean();
                imagedestroy($img);

                if (! empty($imageContent)) {
                    Storage::disk($disk)->put($fullPath, $imageContent);

                    return $fullPath;
                }
            }
        } catch (\Throwable $e) {
            if (isset($img) && (is_resource($img) || $img instanceof \GdImage)) {
                @imagedestroy($img);
            }
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        return $file->store($directory, $disk);
    }
}
