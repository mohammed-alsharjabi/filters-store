<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Models\ServiceImage;
use App\Support\CuratedServiceAssetImporter;
use App\Support\ServiceImageImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class ImportServiceImages extends Command
{
    protected $signature = 'services
        {--zip= : مسار ملف ZIP؛ الافتراضي storage/app/assets.zip}
        {--source= : استيراد المجلد المنظم storage/app/assets بدل ملف ZIP}
        {--sync : مزامنة المعرض ليطابق ملفات المصدر المؤكدة فقط}
        {--publish : نشر الخدمات المؤكدة في كتالوج الإطلاق بعد نجاح الاستيراد}
        {--queue : إرسال التحسين إلى Queue بدل تنفيذه فورًا}
        {--reprocess : إعادة معالجة السجلات الفاشلة المطابقة بدل اعتبارها مكررة}
        {--dry-run : فحص وتصنيف وكتابة Manifest دون تغيير قاعدة البيانات}
        {--manifest= : اسم Manifest داخل storage/app/private/service-image-imports}';

    protected $description = 'استيراد صور الخدمات بأمان، تحسينها، وربطها بالخدمات مع تقرير Manifest';

    private array $manifest = [];

    private array $counts = ['processed' => 0, 'queued' => 0, 'duplicate' => 0, 'failed' => 0, 'unclassified' => 0, 'ignored' => 0];

    public function handle(ServiceImageImportService $importer, CuratedServiceAssetImporter $curatedImporter): int
    {
        if ($this->option('source')) {
            try {
                $report = $curatedImporter->import(
                    $this->resolveSourcePath((string) $this->option('source')),
                    (bool) $this->option('sync'),
                    (bool) $this->option('publish'),
                    (bool) $this->option('queue'),
                );
                $this->table(['الخدمة', 'المتوقع', 'الموجود'], collect($report['services'])->map(fn ($row) => array_values($row))->all());
                $this->newLine();
                $this->info('الإجمالي المرتبط: '.collect($report['services'])->sum('imported').' صورة.');
                $this->line('المكرر: '.$report['counts']['duplicate'].' — المستبعد: '.$report['counts']['excluded'].' — الفاشل: '.$report['counts']['failed']);
                $this->line('Manifest: '.$report['manifest_path']);

                return $report['counts']['failed'] > 0 ? self::FAILURE : self::SUCCESS;
            } catch (Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }
        }

        $zipPath = $this->resolveZipPath();
        if (! is_file($zipPath)) {
            $this->error('ملف الصور غير موجود: '.$zipPath);

            return self::FAILURE;
        }

        $archive = new ZipArchive;
        if ($archive->open($zipPath) !== true) {
            $this->error('تعذر فتح ملف ZIP أو أنه تالف.');

            return self::FAILURE;
        }

        $temporaryDirectory = storage_path('app/private/tmp/service-images-'.Str::uuid());
        File::makeDirectory($temporaryDirectory, 0700, true);
        $this->newLine();
        $this->info('بدأ فحص '.basename($zipPath).' دون تعديل الملف الأصلي.');

        try {
            $this->validateArchive($archive);
            $services = Service::query()->get()->keyBy('name');
            $total = $archive->numFiles;

            for ($index = 0; $index < $total; $index++) {
                $stat = $archive->statIndex($index, ZipArchive::FL_UNCHANGED);
                $entryName = is_array($stat) ? (string) ($stat['name'] ?? '') : '';
                if ($entryName === '' || str_ends_with($entryName, '/')) {
                    continue;
                }
                if ($this->isIgnoredEntry($entryName)) {
                    $this->counts['ignored']++;

                    continue;
                }

                $extension = mb_strtolower(pathinfo($entryName, PATHINFO_EXTENSION));
                if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'avif'], true)) {
                    $this->counts['ignored']++;

                    continue;
                }

                $temporaryFile = $temporaryDirectory.'/'.str_pad((string) $index, 5, '0', STR_PAD_LEFT).'.'.$extension;
                try {
                    $this->extractEntry($archive, $entryName, $temporaryFile);
                    $details = $importer->inspect($temporaryFile);
                    $mapping = $this->mappingFor(dirname(str_replace('\\', '/', $entryName)), $details['hash']);

                    if (! isset($mapping['service']) || $mapping['service'] === null) {
                        $reason = $mapping['reason'] ?? 'لم يطابق المجلد خدمة معروفة بثقة.';
                        $originalPath = null;
                        if (! $this->option('dry-run')) {
                            $originalPath = $this->preserveUnclassified($temporaryFile, $details['hash'], $details['mime']);
                        }
                        $this->counts['unclassified']++;
                        $this->manifest[] = $this->manifestRow($entryName, null, $details, null, 'unclassified', $reason, $originalPath);

                        continue;
                    }

                    $service = $services->get($mapping['service']);
                    if (! $service) {
                        throw new RuntimeException('الخدمة غير موجودة في قاعدة البيانات: '.$mapping['service']);
                    }

                    if ($this->option('dry-run')) {
                        $this->counts['processed']++;
                        $this->manifest[] = $this->manifestRow($entryName, $service->name, $details, null, 'dry-run', 'فحص ناجح دون حفظ.');

                        continue;
                    }

                    $result = $importer->ingest(
                        $service,
                        $temporaryFile,
                        basename($entryName),
                        dirname(str_replace('\\', '/', $entryName)),
                        $mapping['stem'] ?? null,
                        $mapping['context'] ?? null,
                        (bool) $this->option('queue'),
                    );
                    $status = $result['status'];
                    if ($status === 'duplicate' && $this->option('reprocess') && $result['image']->processing_status === 'failed' && ! $result['image']->trashed()) {
                        $result['image'] = $importer->reprocess($result['image'], (bool) $this->option('queue'));
                        $status = $this->option('queue') ? 'queued' : 'processed';
                    }
                    $this->counts[$status]++;
                    $this->manifest[] = $this->manifestRow(
                        $entryName,
                        $result['image']->service?->name ?? $service->name,
                        $details,
                        $result['image'],
                        $status,
                        $status === 'duplicate' ? 'صورة مكررة ببصمة SHA-256؛ لم تُنشأ نسخة ثانية.' : $result['image']->processing_notes,
                    );
                } catch (Throwable $exception) {
                    $this->counts['failed']++;
                    $this->manifest[] = $this->manifestRow($entryName, $mapping['service'] ?? null, $details ?? [], null, 'failed', $exception->getMessage());
                    $this->warn('فشل: '.$entryName.' — '.$exception->getMessage());
                } finally {
                    File::delete($temporaryFile);
                    unset($details, $mapping);
                }
            }
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            $archive->close();
            File::deleteDirectory($temporaryDirectory);
        }

        [$jsonPath, $csvPath] = $this->writeManifest($zipPath);
        $this->renderReport($jsonPath, $csvPath);

        return $this->counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveZipPath(): string
    {
        $path = (string) ($this->option('zip') ?: config('service-images.source_zip'));
        if (! str_starts_with($path, DIRECTORY_SEPARATOR)) {
            $path = base_path($path);
        }

        return $path;
    }

    private function resolveSourcePath(string $path): string
    {
        if (! str_starts_with($path, DIRECTORY_SEPARATOR)) {
            $path = base_path($path);
        }

        return $path;
    }

    private function validateArchive(ZipArchive $archive): void
    {
        if ($archive->numFiles > (int) config('service-images.maximum_entries')) {
            throw new RuntimeException('عدد ملفات الأرشيف يتجاوز الحد الآمن.');
        }

        $totalBytes = 0;
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $stat = $archive->statIndex($index, ZipArchive::FL_UNCHANGED);
            if (! is_array($stat)) {
                throw new RuntimeException('تعذر قراءة أحد مداخل الأرشيف.');
            }
            $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));
            if ($name === '' || str_contains($name, "\0") || str_starts_with($name, '/') || preg_match('/^[A-Za-z]:\//', $name) || in_array('..', explode('/', $name), true)) {
                throw new RuntimeException('اكتُشف مسار غير آمن داخل الأرشيف: '.$name);
            }
            $size = (int) ($stat['size'] ?? 0);
            if ($size > (int) config('service-images.maximum_entry_bytes')) {
                throw new RuntimeException('أحد الملفات يتجاوز الحجم الآمن: '.$name);
            }
            $totalBytes += $size;
            if ($totalBytes > (int) config('service-images.maximum_archive_bytes')) {
                throw new RuntimeException('الحجم غير المضغوط للأرشيف يتجاوز الحد الآمن.');
            }
        }
    }

    private function extractEntry(ZipArchive $archive, string $entryName, string $target): void
    {
        $stream = $archive->getStream($entryName);
        if (! is_resource($stream)) {
            throw new RuntimeException('تعذر قراءة الملف من الأرشيف.');
        }
        $output = fopen($target, 'wb');
        if (! is_resource($output)) {
            fclose($stream);
            throw new RuntimeException('تعذر فتح الملف المؤقت للكتابة.');
        }
        $bytes = stream_copy_to_stream($stream, $output, (int) config('service-images.maximum_entry_bytes') + 1);
        fclose($stream);
        fclose($output);
        if ($bytes === false || $bytes > (int) config('service-images.maximum_entry_bytes')) {
            File::delete($target);
            throw new RuntimeException('تجاوز الملف المستخرج الحجم الآمن.');
        }
    }

    private function isIgnoredEntry(string $entryName): bool
    {
        $segments = explode('/', str_replace('\\', '/', $entryName));

        return collect($segments)->contains(fn (string $segment): bool => $segment === '__MACOSX' || str_starts_with($segment, '.') || $segment === 'Thumbs.db');
    }

    private function mappingFor(string $folder, string $hash): array
    {
        if (array_key_exists($hash, config('service-images.visual_overrides', []))) {
            return config('service-images.visual_overrides.'.$hash);
        }

        $normalized = $this->normalize($folder);
        foreach (config('service-images.folders', []) as $mapping) {
            foreach ($mapping['contains'] as $needle) {
                if (str_contains($normalized, $this->normalize($needle))) {
                    return $mapping;
                }
            }
        }

        return ['service' => null, 'reason' => 'اسم المجلد غير معروف ولم تتوفر قرينة بصرية موثقة.'];
    }

    private function normalize(string $value): string
    {
        if (class_exists(\Normalizer::class)) {
            $value = \Normalizer::normalize($value, \Normalizer::FORM_C) ?: $value;
        }

        return mb_strtolower(trim($value));
    }

    private function preserveUnclassified(string $path, string $hash, string $mime): string
    {
        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            default => 'jpg',
        };
        $originalPath = 'service-images/unclassified-originals/'.$hash.'.'.$extension;
        Storage::disk('local')->put($originalPath, file_get_contents($path));

        return $originalPath;
    }

    private function manifestRow(string $entryName, ?string $service, array $details, ?ServiceImage $image, string $status, ?string $notes = null, ?string $originalPath = null): array
    {
        return [
            'original_folder' => dirname(str_replace('\\', '/', $entryName)),
            'linked_service' => $service,
            'old_name' => basename($entryName),
            'new_name' => $image?->file_name,
            'title' => $image?->title,
            'alt_text' => $image?->alt_text,
            'caption' => $image?->caption,
            'content_hash' => $details['hash'] ?? null,
            'dimensions_before' => isset($details['width'], $details['height']) ? $details['width'].'x'.$details['height'] : null,
            'dimensions_after' => $image?->width && $image?->height ? $image->width.'x'.$image->height : null,
            'size_before' => $details['size'] ?? null,
            'size_after' => $image?->file_size,
            'original_path' => $image?->original_path ?? $originalPath,
            'optimized_path' => $image?->optimized_path,
            'quality_status' => $image?->quality_status,
            'processing_status' => $status,
            'processing_notes' => $notes,
        ];
    }

    private function writeManifest(string $zipPath): array
    {
        $name = (string) ($this->option('manifest') ?: 'manifest-'.now()->format('Ymd-His'));
        $name = pathinfo(basename($name), PATHINFO_FILENAME);
        $directory = 'service-image-imports';
        $jsonPath = $directory.'/'.$name.'.json';
        $csvPath = $directory.'/'.$name.'.csv';
        $payload = [
            'source_zip' => $zipPath,
            'source_zip_sha256' => hash_file('sha256', $zipPath),
            'generated_at' => now()->toAtomString(),
            'dry_run' => (bool) $this->option('dry-run'),
            'counts' => $this->counts,
            'items' => $this->manifest,
        ];
        Storage::disk('local')->put($jsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $stream = fopen('php://temp', 'w+');
        $columns = array_keys($this->manifest[0] ?? []);
        fputcsv($stream, $columns);
        foreach ($this->manifest as $row) {
            fputcsv($stream, array_map(fn ($column) => $row[$column] ?? null, $columns));
        }
        rewind($stream);
        Storage::disk('local')->put($csvPath, stream_get_contents($stream));
        fclose($stream);

        return [$jsonPath, $csvPath];
    }

    private function renderReport(string $jsonPath, string $csvPath): void
    {
        $this->newLine();
        $this->info('اكتملت عملية الاستيراد.');
        $this->table(['ناجحة', 'في Queue', 'مكررة', 'غير مصنفة', 'فاشلة', 'متجاهلة'], [[
            $this->counts['processed'], $this->counts['queued'], $this->counts['duplicate'], $this->counts['unclassified'], $this->counts['failed'], $this->counts['ignored'],
        ]]);

        $byService = collect($this->manifest)->whereIn('processing_status', ['processed', 'queued', 'dry-run'])->groupBy('linked_service')->map->count()->sortDesc();
        if ($byService->isNotEmpty()) {
            $this->table(['الخدمة', 'عدد الصور'], $byService->map(fn (int $count, string $service): array => [$service, $count])->values()->all());
        }

        $unclassified = collect($this->manifest)->where('processing_status', 'unclassified');
        if ($unclassified->isNotEmpty()) {
            $this->warn('صور لم يمكن تصنيفها بثقة:');
            $this->table(['المجلد', 'الملف', 'السبب'], $unclassified->map(fn (array $row): array => [$row['original_folder'], $row['old_name'], $row['processing_notes']])->all());
        }

        $this->line('JSON: '.Storage::disk('local')->path($jsonPath));
        $this->line('CSV:  '.Storage::disk('local')->path($csvPath));
    }
}
