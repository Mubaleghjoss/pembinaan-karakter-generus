<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class TaskProofImageService
{
    public const MAX_FILE_SIZE_BYTES = 512000;

    /**
     * Store proof image after compressing it to at most 500 KB.
     *
     * @return array{path:string,original_size_kb:int,compressed_size_kb:int}
     */
    public function storeProof(UploadedFile $file, int $siswaId, int $karakterId): array
    {
        $originalSize = (int) ($file->getSize() ?? 0);
        $directory = 'tugas-bukti/' . now()->format('Y/m');
        $filename = 'proof_s' . $siswaId . '_k' . $karakterId . '_' . now()->format('YmdHis') . '_' . Str::lower(Str::random(6)) . '.jpg';
        $path = $directory . '/' . $filename;

        if (! extension_loaded('gd')) {
            if ($originalSize > self::MAX_FILE_SIZE_BYTES) {
                throw new \RuntimeException('Server belum mendukung kompresi gambar. Upload bukti yang lebih kecil atau aktifkan ekstensi GD.');
            }

            Storage::disk('public')->putFileAs($directory, $file, $filename);

            return [
                'path' => $path,
                'original_size_kb' => (int) ceil($originalSize / 1024),
                'compressed_size_kb' => (int) ceil($originalSize / 1024),
            ];
        }

        $manager = new ImageManager(new Driver());
        $widthCandidates = [1600, 1440, 1280, 1120, 960, 840, 720, 640];
        $qualityCandidates = [82, 76, 70, 64, 58, 52, 46];
        $bestEncoded = null;

        foreach ($widthCandidates as $width) {
            foreach ($qualityCandidates as $quality) {
                $image = $manager->read($file->getPathname());
                $image->scaleDown(width: $width, height: $width);
                $encoded = $image->toJpeg($quality);
                $encodedString = (string) $encoded;

                if ($bestEncoded === null || strlen($encodedString) < strlen($bestEncoded)) {
                    $bestEncoded = $encodedString;
                }

                if (strlen($encodedString) <= self::MAX_FILE_SIZE_BYTES) {
                    Storage::disk('public')->put($path, $encodedString);

                    return [
                        'path' => $path,
                        'original_size_kb' => (int) ceil($originalSize / 1024),
                        'compressed_size_kb' => (int) ceil(strlen($encodedString) / 1024),
                    ];
                }
            }
        }

        if ($bestEncoded !== null && strlen($bestEncoded) <= self::MAX_FILE_SIZE_BYTES) {
            Storage::disk('public')->put($path, $bestEncoded);

            return [
                'path' => $path,
                'original_size_kb' => (int) ceil($originalSize / 1024),
                'compressed_size_kb' => (int) ceil(strlen($bestEncoded) / 1024),
            ];
        }

        throw new \RuntimeException('Foto bukti belum bisa dikompres sampai maksimal 500 KB. Coba unggah foto yang lebih kecil atau resolusi lebih rendah.');
    }
}
