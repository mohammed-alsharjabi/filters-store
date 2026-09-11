<?php

namespace App\Support;

use App\Models\ServiceImage;
use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ServiceImageProcessor
{
    public function process(ServiceImage $image): ServiceImage
    {
        $absolutePath = Storage::disk('local')->path($image->original_path);

        try {
            [$source, $sourceWidth, $sourceHeight, $mime, $orientation] = $this->decode($absolutePath);
            [$source, $sourceWidth, $sourceHeight, $rotated] = $this->orient($source, $sourceWidth, $sourceHeight, $orientation);
            [$score, $quality] = $this->quality($sourceWidth, $sourceHeight, filesize($absolutePath) ?: 0);

            $notes = [];
            if ($rotated) {
                $notes[] = 'صُحح اتجاه الصورة تلقائيًا من بيانات Orientation.';
            }

            $this->enhanceConservatively($source, $mime);
            $notes[] = 'طُبق تصحيح محافظ للإضاءة والألوان والتشويش والحدة دون تغيير محتوى الصورة.';

            $targetWidth = $sourceWidth > 1600
                ? 1600
                : ($sourceWidth < 1200 ? min(1200, $sourceWidth * 2) : $sourceWidth);
            $working = $source;
            if ($targetWidth !== $sourceWidth) {
                $targetHeight = max(1, (int) round($sourceHeight * ($targetWidth / $sourceWidth)));
                $scaled = imagescale($source, $targetWidth, $targetHeight, IMG_BILINEAR_FIXED);
                if (! $scaled) {
                    throw new RuntimeException('تعذر ضبط أبعاد الصورة.');
                }
                $working = $scaled;
                if ($targetWidth > $sourceWidth) {
                    $notes[] = sprintf('حُسنت الدقة من %dpx إلى %dpx بعامل %.2fx ضمن الحد الأقصى 2x.', $sourceWidth, $targetWidth, $targetWidth / $sourceWidth);
                } else {
                    $notes[] = sprintf('خُفض العرض من %dpx إلى %dpx لتناسب الويب.', $sourceWidth, $targetWidth);
                }
            } else {
                $notes[] = 'لم تُكبر الصورة لأن دقتها الأصلية كافية.';
            }

            $this->sharpen($working, 0.10);
            $variants = $this->writeVariants($working, $image);
            $gallery = collect($variants['webp'])->firstWhere('role', 'gallery');
            if (! $gallery) {
                throw new RuntimeException('تعذر إنشاء نسخة Gallery بصيغة WebP.');
            }

            $notes[] = 'أزيلت بيانات EXIF وGPS بإعادة الترميز الآمنة.';
            if (empty($variants['avif'])) {
                $notes[] = 'AVIF غير مدعوم في هذه البيئة؛ أُنشئت WebP وJPEG.';
            }

            $image->forceFill([
                'optimized_path' => $gallery['path'],
                'file_name' => basename($gallery['path']),
                'original_width' => $sourceWidth,
                'original_height' => $sourceHeight,
                'original_file_size' => filesize($absolutePath) ?: null,
                'width' => $gallery['width'],
                'height' => $gallery['height'],
                'mime_type' => 'image/webp',
                'file_size' => $gallery['size'],
                'variants' => $variants,
                'quality_score' => $score,
                'quality_status' => $quality,
                'processing_status' => 'processed',
                'processing_notes' => implode(' ', $notes),
            ])->save();

            return $image->refresh();
        } catch (Throwable $exception) {
            $image->forceFill([
                'processing_status' => 'failed',
                'processing_notes' => 'فشلت المعالجة: '.$exception->getMessage(),
            ])->save();
            Log::error('فشل تحسين صورة خدمة.', ['service_image_id' => $image->id, 'exception' => $exception]);

            throw $exception;
        }
    }

    public function removeDerivatives(ServiceImage $image): void
    {
        $paths = collect($image->variants ?? [])->flatten(1)->pluck('path')
            ->push($image->optimized_path)->filter()->unique()->all();
        Storage::disk('public')->delete($paths);
    }

    private function decode(string $path): array
    {
        $contents = file_get_contents($path);
        $info = $contents ? getimagesizefromstring($contents) : false;
        $image = $contents ? imagecreatefromstring($contents) : false;
        if (! $info || ! $image) {
            throw new RuntimeException('ملف الصورة غير صالح أو ترميزه غير مدعوم.');
        }

        $orientation = 1;
        if (($info['mime'] ?? '') === 'image/jpeg' && function_exists('exif_read_data')) {
            try {
                $orientation = (int) (exif_read_data($path, 'IFD0', true, false)['IFD0']['Orientation'] ?? 1);
            } catch (Throwable) {
                $orientation = 1;
            }
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        return [$image, imagesx($image), imagesy($image), (string) $info['mime'], $orientation];
    }

    private function orient(GdImage $image, int $width, int $height, int $orientation): array
    {
        $rotated = false;
        $result = $image;

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($result, in_array($orientation, [2, 5], true) ? IMG_FLIP_HORIZONTAL : IMG_FLIP_VERTICAL);
            $rotated = true;
        }
        if (in_array($orientation, [3, 4], true)) {
            $result = imagerotate($result, 180, 0);
            $rotated = true;
        } elseif (in_array($orientation, [5, 6], true)) {
            $result = imagerotate($result, -90, 0);
            $rotated = true;
        } elseif (in_array($orientation, [7, 8], true)) {
            $result = imagerotate($result, 90, 0);
            $rotated = true;
        }

        if (! $result) {
            throw new RuntimeException('تعذر تصحيح اتجاه الصورة.');
        }

        return [$result, imagesx($result), imagesy($result), $rotated];
    }

    private function quality(int $width, int $height, int $bytes): array
    {
        $score = match (true) {
            $width >= 1600 => 95,
            $width >= 1200 => 88,
            $width >= 900 => 78,
            $width >= 640 => 66,
            $width >= 480 => 55,
            default => 42,
        };
        $density = $bytes / max(1, $width * $height);
        $score -= $density < 0.07 ? 10 : ($density < 0.12 ? 5 : 0);
        $score = max(1, min(100, $score));
        $status = match (true) {
            $score >= 85 => 'excellent',
            $score >= 70 => 'good',
            $score >= 55 => 'needs_improvement',
            default => 'weak',
        };

        return [$score, $status];
    }

    private function enhanceConservatively(GdImage $image, string $mime): void
    {
        [$mean, $contrast, $channels] = $this->sampleStatistics($image);
        $brightness = (int) round(max(-6, min(6, (128 - $mean) * 0.08)));
        if ($brightness !== 0) {
            imagefilter($image, IMG_FILTER_BRIGHTNESS, $brightness);
        }
        if ($contrast < 45) {
            imagefilter($image, IMG_FILTER_CONTRAST, -3);
        }
        $averageChannel = array_sum($channels) / 3;
        imagefilter(
            $image,
            IMG_FILTER_COLORIZE,
            (int) max(-3, min(3, ($averageChannel - $channels[0]) * 0.04)),
            (int) max(-3, min(3, ($averageChannel - $channels[1]) * 0.04)),
            (int) max(-3, min(3, ($averageChannel - $channels[2]) * 0.04)),
        );
        if ($mime === 'image/jpeg') {
            imagefilter($image, IMG_FILTER_SMOOTH, 1);
        }
        $this->sharpen($image, 0.15);
    }

    private function sampleStatistics(GdImage $image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $stepX = max(1, intdiv($width, 30));
        $stepY = max(1, intdiv($height, 30));
        $luminances = [];
        $channels = [0.0, 0.0, 0.0];
        $count = 0;

        for ($y = intdiv($stepY, 2); $y < $height; $y += $stepY) {
            for ($x = intdiv($stepX, 2); $x < $width; $x += $stepX) {
                $rgb = imagecolorat($image, $x, $y);
                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;
                $channels[0] += $red;
                $channels[1] += $green;
                $channels[2] += $blue;
                $luminances[] = 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
                $count++;
            }
        }

        if ($count === 0) {
            return [128.0, 50.0, [128.0, 128.0, 128.0]];
        }
        $mean = array_sum($luminances) / $count;
        $variance = array_sum(array_map(fn (float $value): float => ($value - $mean) ** 2, $luminances)) / $count;

        return [$mean, sqrt($variance), array_map(fn (float $value): float => $value / $count, $channels)];
    }

    private function sharpen(GdImage $image, float $strength): void
    {
        $edge = max(0.05, min(0.20, $strength));
        imageconvolution($image, [[0, -$edge, 0], [-$edge, 1 + 4 * $edge, -$edge], [0, -$edge, 0]], 1, 0);
    }

    private function writeVariants(GdImage $working, ServiceImage $image): array
    {
        $directory = 'service-images/'.$image->service_id;
        $base = pathinfo((string) $image->file_name, PATHINFO_FILENAME);
        $formats = ['webp', 'jpeg'];
        if (function_exists('imageavif')) {
            $formats[] = 'avif';
        }
        $variants = ['webp' => [], 'avif' => [], 'jpeg' => []];
        $created = [];
        $workingWidth = imagesx($working);
        $workingHeight = imagesy($working);

        foreach (['gallery' => 1600, 'medium' => 1200, 'tablet' => 768, 'mobile' => 480] as $role => $requestedWidth) {
            $width = min($requestedWidth, $workingWidth);
            $height = max(1, (int) round($workingHeight * ($width / $workingWidth)));
            $key = 'fit-'.$width.'x'.$height;
            $variantImage = $created[$key] ?? imagescale($working, $width, $height, IMG_BILINEAR_FIXED);
            if (! $variantImage) {
                throw new RuntimeException('تعذر إنشاء مقاس '.$role.'.');
            }
            $created[$key] = $variantImage;
            foreach ($formats as $format) {
                $suffix = $role === 'gallery' ? '' : '-'.($role === 'tablet' ? '768' : ($role === 'mobile' ? '480' : '1200'));
                try {
                    $variants[$format][] = $this->storeEncoded($variantImage, $format, $directory.'/'.$base.$suffix.'.'.$this->extension($format), $role);
                } catch (Throwable $exception) {
                    if ($format !== 'avif') {
                        throw $exception;
                    }
                }
            }
        }

        foreach (['cover_1600' => [1600, 900], 'cover_1200' => [1200, 675], 'cover_768' => [768, 432], 'cover_480' => [480, 270], 'thumbnail' => [640, 480]] as $role => [$requestedWidth, $requestedHeight]) {
            $crop = $this->smartCrop($working, $requestedWidth, $requestedHeight);
            foreach ($formats as $format) {
                $suffix = $role === 'thumbnail' ? '-thumbnail' : '-'.str_replace('_', '-', $role);
                try {
                    $variants[$format][] = $this->storeEncoded($crop, $format, $directory.'/'.$base.$suffix.'.'.$this->extension($format), $role);
                } catch (Throwable $exception) {
                    if ($format !== 'avif') {
                        throw $exception;
                    }
                }
            }
        }

        return $variants;
    }

    private function smartCrop(GdImage $source, int $requestedWidth, int $requestedHeight): GdImage
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $ratio = $requestedWidth / $requestedHeight;
        $cropWidth = $sourceWidth;
        $cropHeight = (int) round($cropWidth / $ratio);
        if ($cropHeight > $sourceHeight) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($cropHeight * $ratio);
        }

        $positions = $sourceHeight > $cropHeight
            ? [[0, 0], [0, intdiv($sourceHeight - $cropHeight, 2)], [0, $sourceHeight - $cropHeight]]
            : [[0, 0], [intdiv($sourceWidth - $cropWidth, 2), 0], [$sourceWidth - $cropWidth, 0]];
        $best = $positions[1] ?? $positions[0];
        $bestScore = -INF;
        foreach ($positions as [$x, $y]) {
            $score = $this->cropEnergy($source, $x, $y, $cropWidth, $cropHeight);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = [$x, $y];
            }
        }

        $maximumScale = min(1, $sourceWidth / $requestedWidth, $sourceHeight / $requestedHeight);
        $targetWidth = max(1, (int) floor($requestedWidth * $maximumScale));
        $targetHeight = max(1, (int) floor($requestedHeight * $maximumScale));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($canvas, $source, 0, 0, $best[0], $best[1], $targetWidth, $targetHeight, $cropWidth, $cropHeight);

        return $canvas;
    }

    private function cropEnergy(GdImage $image, int $startX, int $startY, int $width, int $height): float
    {
        $step = max(4, intdiv(min($width, $height), 24));
        $score = 0.0;
        for ($y = $startY + $step; $y < $startY + $height; $y += $step) {
            for ($x = $startX + $step; $x < $startX + $width; $x += $step) {
                $current = imagecolorat($image, $x, $y);
                $left = imagecolorat($image, max($startX, $x - $step), $y);
                $top = imagecolorat($image, $x, max($startY, $y - $step));
                $score += abs(($current & 0xFFFFFF) - ($left & 0xFFFFFF));
                $score += abs(($current & 0xFFFFFF) - ($top & 0xFFFFFF));
            }
        }

        return $score;
    }

    private function storeEncoded(GdImage $image, string $format, string $path, string $role): array
    {
        ob_start();
        $success = match ($format) {
            'webp' => imagewebp($image, null, (int) config('service-images.webp_quality', 82)),
            'avif' => imageavif($image, null, (int) config('service-images.avif_quality', 58)),
            default => imagejpeg($image, null, (int) config('service-images.jpeg_quality', 84)),
        };
        $contents = ob_get_clean();
        if (! $success || ! is_string($contents) || ! Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('تعذر ترميز أو حفظ نسخة '.$format.'.');
        }

        return [
            'role' => $role,
            'path' => $path,
            'width' => imagesx($image),
            'height' => imagesy($image),
            'size' => strlen($contents),
        ];
    }

    private function extension(string $format): string
    {
        return $format === 'jpeg' ? 'jpg' : $format;
    }
}
