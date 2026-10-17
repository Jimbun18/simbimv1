<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhotoCompressionService
{
    /**
     * Kompres dan simpan foto ke disk publik.
     *
     * @param UploadedFile $file
     * @param string $folder e.g. 'attendance/teachers' or 'attendance/evidence'
     * @param string|null $watermarkText
     * @param int $maxWidth
     * @param int $quality (1-100)
     * @return string Path relatif file yang tersimpan
     */
    public function compressAndStore(
        UploadedFile $file,
        string $folder = 'attendance',
        ?string $watermarkText = null,
        int $maxWidth = 1280,
        int $quality = 75
    ): string {
        $filename = Str::uuid() . '.jpg';
        $fullFolderPath = storage_path("app/public/{$folder}");

        if (!file_exists($fullFolderPath)) {
            mkdir($fullFolderPath, 0755, true);
        }

        $targetPath = "{$fullFolderPath}/{$filename}";
        $relativePath = "{$folder}/{$filename}";

        // Jika GD tersedia, proses kompresi & resize & watermark
        if (extension_loaded('gd')) {
            $image = $this->createImageFromFile($file);
            if ($image) {
                $origWidth = imagesx($image);
                $origHeight = imagesy($image);

                // Resize jika melebihi maxWidth
                if ($origWidth > $maxWidth) {
                    $newWidth = $maxWidth;
                    $newHeight = (int) round(($origHeight / $origWidth) * $maxWidth);
                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                    imagedestroy($image);
                    $image = $resized;
                }

                // Tambahkan cap waktu / watermark jika ada
                if ($watermarkText) {
                    $this->addWatermark($image, $watermarkText);
                }

                ob_start();
                imagejpeg($image, null, $quality);
                $imageContents = ob_get_clean();
                imagedestroy($image);

                if ($imageContents !== false) {
                    Storage::disk('public')->put($relativePath, $imageContents);
                    return $relativePath;
                }
            }
        }

        // Fallback jika pemrosesan GD gagal
        return $file->storeAs($folder, $filename, 'public');
    }

    private function createImageFromFile(UploadedFile $file)
    {
        $path = $file->getRealPath();
        $mime = $file->getMimeType();

        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => null,
        };
    }

    private function addWatermark($image, string $text): void
    {
        $textColor = imagecolorallocate($image, 255, 255, 255);
        $bgColor = imagecolorallocatealpha($image, 0, 0, 0, 60);

        $imgHeight = imagesy($image);
        $imgWidth = imagesx($image);

        // Bar watermark di bagian bawah
        $barHeight = 36;
        imagefilledrectangle($image, 0, $imgHeight - $barHeight, $imgWidth, $imgHeight, $bgColor);

        // Teks cap waktu
        $fontSize = 4; // Built-in GD font 1-5
        imagestring($image, $fontSize, 15, $imgHeight - 26, $text, $textColor);
    }
}
