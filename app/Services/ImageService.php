<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    public function upload(UploadedFile $file, string $directory, ?int $maxWidth = 1920, ?int $quality = 85): string
    {
        $filename = Str::random(30) . '.' . $file->getClientOriginalExtension();
        $path = "{$directory}/{$filename}";

        if ($maxWidth && $this->canResize($file)) {
            $image = $this->resizeImage($file, $maxWidth, $quality ?? 85);
            Storage::disk('public')->put($path, $image);
        } else {
            Storage::disk('public')->putFileAs($directory, $file, $filename);
        }

        return $path;
    }

    public function uploadThumbnail(UploadedFile $file, string $directory, int $width = 400, int $quality = 80): string
    {
        $filename = 'thumb_' . Str::random(30) . '.' . $file->getClientOriginalExtension();
        $path = "{$directory}/{$filename}";

        if ($this->canResize($file)) {
            $image = $this->resizeImage($file, $width, $quality);
            Storage::disk('public')->put($path, $image);
        } else {
            Storage::disk('public')->putFileAs($directory, $file, $filename);
        }

        return $path;
    }

    public function delete(string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function url(string $path): string
    {
        if (! $path) {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    private function canResize(UploadedFile $file): bool
    {
        return in_array(
            strtolower($file->getClientOriginalExtension()),
            ['jpg', 'jpeg', 'png', 'webp'],
            true,
        ) && extension_loaded('gd');
    }

    private function resizeImage(UploadedFile $file, int $maxWidth, int $quality): string
    {
        $source = $file->getRealPath();
        $mime = $file->getMimeType();

        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($source),
            'image/png' => imagecreatefrompng($source),
            'image/webp' => imagecreatefromwebp($source),
            default => null,
        };

        if (! $image) {
            return file_get_contents($source);
        }

        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        if ($origWidth <= $maxWidth) {
            $newWidth = $origWidth;
            $newHeight = $origHeight;
        } else {
            $ratio = $maxWidth / $origWidth;
            $newWidth = $maxWidth;
            $newHeight = (int) round($origHeight * $ratio);
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        if ($mime === 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        ob_start();
        match ($mime) {
            'image/jpeg' => imagejpeg($resized, null, $quality),
            'image/png' => imagepng($resized, null, (int) round(9 - ($quality / 100 * 9))),
            'image/webp' => imagewebp($resized, null, $quality),
            default => imagejpeg($resized, null, $quality),
        };
        $output = ob_get_clean();

        imagedestroy($image);
        imagedestroy($resized);

        return $output;
    }
}
