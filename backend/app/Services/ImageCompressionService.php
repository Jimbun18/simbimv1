<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageCompressionService
{
    /**
     * Kompres dan resize gambar proporsional, lalu simpan ke disk public dalam format WebP
     *
     * @param UploadedFile $file
     * @param string $folder Direktori penyimpanan di disk public (misal: 'cms/galleries')
     * @param int $maxDimension Dimensi maksimum lebar/tinggi (default: 1400px)
     * @param int $quality Kualitas WebP 1-100 (default: 82)
     * @return string Path relatif file yang tersimpan
     */
    public static function compressAndStore(
        UploadedFile $file,
        string $folder = 'cms/uploads',
        int $maxDimension = 1400,
        int $quality = 82
    ): string {
        $realPath = $file->getRealPath();
        $mime = $file->getMimeType();

        // 1. Buat GD image resource berdasarkan format asli
        $sourceImage = null;
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = @imagecreatefromjpeg($realPath);
                break;
            case 'image/png':
                $sourceImage = @imagecreatefrompng($realPath);
                break;
            case 'image/webp':
                $sourceImage = @imagecreatefromwebp($realPath);
                break;
            default:
                // Fallback jika format lain, simpan biasa
                return $file->store($folder, 'public');
        }

        if (!$sourceImage) {
            return $file->store($folder, 'public');
        }

        // Tangani auto-rotate orientasi EXIF untuk foto kamera HP (jika ada)
        if (function_exists('exif_read_data') && ($mime === 'image/jpeg' || $mime === 'image/jpg')) {
            $exif = @exif_read_data($realPath);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 8:
                        $sourceImage = imagerotate($sourceImage, 90, 0);
                        break;
                    case 3:
                        $sourceImage = imagerotate($sourceImage, 180, 0);
                        break;
                    case 6:
                        $sourceImage = imagerotate($sourceImage, -90, 0);
                        break;
                }
            }
        }

        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        // 2. Hitung dimensi target proporsional
        $targetWidth = $origWidth;
        $targetHeight = $origHeight;

        if ($origWidth > $maxDimension || $origHeight > $maxDimension) {
            if ($origWidth >= $origHeight) {
                $targetWidth = $maxDimension;
                $targetHeight = (int) round(($origHeight / $origWidth) * $maxDimension);
            } else {
                $targetHeight = $maxDimension;
                $targetWidth = (int) round(($origWidth / $origHeight) * $maxDimension);
            }
        }

        // 3. Buat canvas target dengan resampling halus
        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        // Pertahankan transparansi jika PNG/WebP
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);

        imagecopyresampled(
            $targetImage,
            $sourceImage,
            0, 0, 0, 0,
            $targetWidth,
            $targetHeight,
            $origWidth,
            $origHeight
        );

        // 4. Simpan ke file WebP terkompresi di storage disk public
        $filename = Str::random(40) . '.webp';
        $relativeFolder = trim($folder, '/');
        $fullDirectory = storage_path('app/public/' . $relativeFolder);

        if (!is_dir($fullDirectory)) {
            mkdir($fullDirectory, 0755, true);
        }

        $targetPath = $fullDirectory . '/' . $filename;
        imagewebp($targetImage, $targetPath, $quality);

        // Bebaskan memori GD
        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        return $relativeFolder . '/' . $filename;
    }
}
