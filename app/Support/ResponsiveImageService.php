<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ResponsiveImageService
{
    public function storeProjectImage(UploadedFile $file, string $directory = 'projects'): array
    {
        [$image, $originalWidth, $originalHeight] = $this->decode($file);
        $maximumWidth = min($originalWidth, 1600);
        $widths = collect([480, 768, 1200, 1600, $maximumWidth])
            ->filter(fn (int $width): bool => $width <= $maximumWidth)
            ->unique()->sort()->values();
        $id = (string) Str::uuid();
        $variants = ['webp' => [], 'avif' => []];

        foreach ($widths as $width) {
            $height = (int) round($originalHeight * ($width / $originalWidth));
            $resized = imagescale($image, $width, $height, IMG_BILINEAR_FIXED);
            if (! $resized) {
                continue;
            }

            $webpPath = $directory.'/'.$id.'-'.$width.'.webp';
            Storage::disk('public')->put($webpPath, $this->encode($resized, 'webp'));
            $variants['webp'][] = ['width' => $width, 'path' => $webpPath];

            if (function_exists('imageavif')) {
                try {
                    $avifPath = $directory.'/'.$id.'-'.$width.'.avif';
                    Storage::disk('public')->put($avifPath, $this->encode($resized, 'avif'));
                    $variants['avif'][] = ['width' => $width, 'path' => $avifPath];
                } catch (Throwable) {
                    // WebP remains the supported fallback when AVIF encoding is unavailable.
                }
            }
            unset($resized);
        }
        unset($image);

        $fallback = collect($variants['webp'])->last();
        if (! $fallback) {
            throw new RuntimeException('تعذر إنشاء نسخة محسنة من الصورة.');
        }

        return [
            'path' => $fallback['path'],
            'variants' => $variants,
            'width' => $fallback['width'],
            'height' => (int) round($originalHeight * ($fallback['width'] / $originalWidth)),
        ];
    }

    public function storePrimaryImage(UploadedFile $file, string $directory = 'content'): string
    {
        [$image, $originalWidth, $originalHeight] = $this->decode($file);
        $width = min($originalWidth, 1600);
        $height = (int) round($originalHeight * ($width / $originalWidth));
        $resized = imagescale($image, $width, $height, IMG_BILINEAR_FIXED);
        unset($image);
        if (! $resized) {
            throw new RuntimeException('تعذر معالجة الصورة.');
        }
        $path = $directory.'/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, $this->encode($resized, 'webp'));
        unset($resized);

        return $path;
    }

    public function deleteProjectImage(array $image): void
    {
        $paths = collect($image['variants'] ?? [])->flatten(1)->pluck('path')->push($image['path'] ?? null)->filter()->unique();
        Storage::disk('public')->delete($paths->all());
    }

    private function decode(UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath());
        $size = getimagesizefromstring($contents ?: '');
        $image = $contents ? imagecreatefromstring($contents) : false;
        if (! $size || ! $image) {
            throw new RuntimeException('ملف الصورة غير صالح.');
        }

        return [$image, (int) $size[0], (int) $size[1]];
    }

    private function encode(\GdImage $image, string $format): string
    {
        ob_start();
        $success = $format === 'avif' ? imageavif($image, null, 58) : imagewebp($image, null, 82);
        $contents = ob_get_clean();
        if (! $success || ! is_string($contents)) {
            throw new RuntimeException('تعذر ترميز الصورة.');
        }

        return $contents;
    }
}
