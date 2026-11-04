<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageService
{
    public function upload(UploadedFile $file, string $directory, ?int $maxWidth = 1920, ?int $quality = 85): string
    {
        $filename = Str::random(30) . '.' . $file->getClientOriginalExtension();
        $path = "{$directory}/{$filename}";

        /*
         * Both writes return false rather than throwing when the disk
         * refuses them -- a directory owned by another user is the usual
         * cause. Ignoring that returned a path for a file that was never
         * written: the caller saved it, the request answered 200, and the
         * image 404'd forever after. Fail loudly instead.
         */
        if ($maxWidth && $this->canResize($file)) {
            $image = $this->resizeImage($file, $maxWidth, $quality ?? 85);
            $written = Storage::disk('public')->put($path, $image);
        } else {
            $written = Storage::disk('public')->putFileAs($directory, $file, $filename);
        }

        if ($written === false) {
            throw new RuntimeException("Could not write the upload to {$path}.");
        }

        return $path;
    }

    public function uploadThumbnail(UploadedFile $file, string $directory, int $width = 400, int $quality = 80): string
    {
        $filename = 'thumb_' . Str::random(30) . '.' . $file->getClientOriginalExtension();
        $path = "{$directory}/{$filename}";

        // Same as upload(): a refused write must not look like a success.
        if ($this->canResize($file)) {
            $image = $this->resizeImage($file, $width, $quality);
            $written = Storage::disk('public')->put($path, $image);
        } else {
            $written = Storage::disk('public')->putFileAs($directory, $file, $filename);
        }

        if ($written === false) {
            throw new RuntimeException("Could not write the thumbnail to {$path}.");
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
