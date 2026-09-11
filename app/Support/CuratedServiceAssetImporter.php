<?php

namespace App\Support;

use App\Models\Service;
use App\Models\ServiceImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CuratedServiceAssetImporter
{
    public function __construct(private ServiceImageImportService $images) {}

    public function import(string $source, bool $sync = false, bool $publish = false, bool $queue = false): array
    {
        $source = rtrim(realpath($source) ?: $source, DIRECTORY_SEPARATOR);
        if (! is_dir($source)) {
            throw new RuntimeException('مجلد الصور غير موجود: '.$source);
        }

        $manifest = [];
        $counts = ['processed' => 0, 'queued' => 0, 'duplicate' => 0, 'excluded' => 0, 'failed' => 0];
        $serviceRows = [];

        $assignments = $this->dynamicAssignments($source, $manifest, $counts);
        $assignedServiceNames = $assignments->pluck('service')->unique()->values()->all();
        $managedFolders = $this->managedFolders();
        foreach ($assignments->groupBy('service') as $serviceName => $items) {
            $service = Service::query()->where('name', $serviceName)->first();
            if (! $service) {
                throw new RuntimeException('الخدمة غير موجودة في قاعدة البيانات: '.$serviceName);
            }

            if ($publish && in_array($service->name, config('site.launch_services', []), true)) {
                $service->update([
                    'status' => 'published', 'is_active' => true, 'is_featured' => true,
                    'published_at' => $service->published_at ?: now(),
                ]);
            }

            // صور المجلد الأصلي تسبق صور المجلدات الإضافية فيبقى ترتيب المعرض ثابتًا.
            $items = $items->sortBy(fn (array $entry): string => $entry['priority'].'|'.$entry['relative'], SORT_NATURAL | SORT_FLAG_CASE)->values();
            $keptHashes = [];
            $coverImageId = null;
            foreach ($items as $index => $entry) {
                $file = $entry['file'];
                try {
                    $sequence = $index + 1;
                    $expectedName = $entry['stem'].'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT).'.webp';
                    $details = $entry['details'];
                    $result = $this->images->ingest(
                        $service,
                        $file,
                        basename($file),
                        dirname($entry['relative']),
                        $entry['stem'],
                        $entry['context'],
                        $queue,
                        true,
                        $sequence,
                    );

                    /** @var ServiceImage $image */
                    $image = $result['image'];
                    if ($image->trashed()) {
                        $image->restore();
                    }
                    $hasProcessedDerivative = $image->processing_status === 'processed'
                        && filled($image->optimized_path)
                        && Storage::disk('public')->exists($image->optimized_path);
                    if (! $hasProcessedDerivative) {
                        // A filename may have shifted after the source map changed. Clear stale
                        // derivative metadata first so reprocessing cannot remove another row's
                        // now-current deterministic path.
                        $image->forceFill(['file_name' => $expectedName, 'optimized_path' => null, 'variants' => null])->save();
                        $image = $this->images->reprocess($image, $queue);
                        $result['status'] = $queue ? 'queued' : 'processed';
                    }

                    $isCover = $entry['cover'] || ($coverImageId === null && $sequence === 1);
                    $image->update([
                        'sort_order' => $sequence,
                        'is_cover' => $isCover,
                        'source_folder' => dirname($entry['relative']),
                        'original_name' => basename($file),
                        'title' => $entry['context'].' في الرياض — صورة '.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
                        'alt_text' => $entry['context'].' في موقع التنفيذ في الرياض',
                        'caption' => 'صورة حقيقية من أعمال '.$service->name.' في الرياض.',
                    ]);
                    if ($isCover) {
                        $coverImageId = $image->id;
                    }
                    $keptHashes[] = $details['hash'];
                    $counts[$result['status']]++;
                    $manifest[] = $this->row($source, $file, $service, $image, $details, $result['status']);
                } catch (\Throwable $exception) {
                    $counts['failed']++;
                    $manifest[] = [
                        'source_path' => $entry['relative'],
                        'original_folder' => dirname($entry['relative']),
                        'linked_service' => $service->name,
                        'old_name' => basename($file),
                        'processing_status' => 'failed',
                        'processing_notes' => $exception->getMessage(),
                    ];
                }
            }

            if ($sync && $counts['failed'] === 0) {
                ServiceImage::query()
                    ->where('service_id', $service->id)
                    ->where(function ($query) use ($managedFolders): void {
                        foreach ($managedFolders as $folder) {
                            $query->orWhere('source_folder', $folder)->orWhere('source_folder', 'like', $folder.'/%');
                        }
                    })
                    ->whereNotIn('content_hash', $keptHashes)
                    ->get()
                    ->each->delete();
            }

            if ($coverImageId) {
                ServiceImage::query()->where('service_id', $service->id)->whereKeyNot($coverImageId)->update(['is_cover' => false]);
                ServiceImage::query()->whereKey($coverImageId)->update(['is_cover' => true]);
                if (! config('site.service_featured_images.'.$service->name)) {
                    $cover = ServiceImage::query()->find($coverImageId);
                    if ($cover?->optimized_path) {
                        $service->update(['featured_image' => $cover->optimized_path, 'featured_image_alt' => $service->name.' في الرياض']);
                    }
                }
            }
            $imported = ServiceImage::query()->where('service_id', $service->id)->whereNull('deleted_at')->count();
            $serviceRows[] = ['service' => $service->name, 'expected' => $items->count(), 'imported' => $imported];
        }

        if ($sync && $counts['failed'] === 0) {
            Service::query()
                ->whereNotNull('image_source_folder')
                ->whereNotIn('name', $assignedServiceNames)
                ->each(function (Service $service) use ($managedFolders): void {
                    $service->images()
                        ->where(function ($query) use ($managedFolders): void {
                            foreach ($managedFolders as $folder) {
                                $query->orWhere('source_folder', $folder)->orWhere('source_folder', 'like', $folder.'/%');
                            }
                        })
                        ->get()
                        ->each->delete();
                });
        }

        $this->ensurePublishedCovers();

        if ($sync && $counts['failed'] === 0) {
            ServiceImage::query()
                ->whereHas('service', fn ($query) => $query->whereIn('name', config('service-images.retired_services', [])))
                ->get()
                ->each->delete();
        }

        $used = collect($manifest)->pluck('source_path')->filter()->all();
        $usedHashes = collect($manifest)
            ->whereIn('processing_status', ['processed', 'queued', 'duplicate'])
            ->pluck('source_hash')
            ->filter()
            ->all();
        foreach ($this->allImages($source) as $file) {
            $relative = ltrim(str_replace($source, '', $file), DIRECTORY_SEPARATOR);
            if (! in_array($relative, $used, true)) {
                $hash = hash_file('sha256', $file);
                $isDuplicate = in_array($hash, $usedHashes, true);
                $counts[$isDuplicate ? 'duplicate' : 'excluded']++;
                $manifest[] = [
                    'source_path' => $relative,
                    'original_folder' => dirname(str_replace('\\', '/', $relative)),
                    'linked_service' => null,
                    'old_name' => basename($file),
                    'processing_status' => $isDuplicate ? 'duplicate_source' : 'excluded',
                    'processing_notes' => $isDuplicate
                        ? 'نسخة مطابقة بالـ Hash لصورة مستوردة؛ لم تُكرر داخل المعرض.'
                        : 'ملف زائد عن العدد المؤكد أو لا يثبت الخدمة بصريًا؛ لم يُنشر.',
                ];
            }
        }

        $payload = [
            'source_directory' => $source,
            'generated_at' => now()->toAtomString(),
            'counts' => $counts,
            'services' => $serviceRows,
            'items' => $manifest,
        ];
        $manifestPath = 'service-image-imports/curated-assets-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($manifestPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        $payload['manifest_path'] = storage_path('app/private/'.$manifestPath);

        return $payload;
    }

    private function dynamicAssignments(string $source, array &$manifest, array &$counts): Collection
    {
        $explicit = [];
        $serviceProfiles = [];
        foreach (config('service-images.curated_folders', []) as $mappingKey => $mapping) {
            $folder = $mapping['folder'] ?? $mappingKey;
            $serviceProfiles[$mapping['service']] = $mapping;
            foreach ($mapping['files'] ?? [] as $name) {
                $explicit[$folder.'/'.$name] = $mapping;
            }
            foreach ($mapping['include'] ?? [] as $relative) {
                $explicit[str_replace('\\', '/', $relative)] = $mapping;
            }
        }

        $visualContexts = config('service-images.visual_contexts', []);
        $visualOverrides = config('service-images.visual_overrides', []);
        $assignments = collect();
        foreach ($this->sourceFolders() as [$folder, $defaultServiceName, $priority, $required]) {
            $folderPath = $source.DIRECTORY_SEPARATOR.$folder;
            if (! is_dir($folderPath)) {
                if ($required) {
                    throw new RuntimeException('مجلد صور الخدمة غير موجود: '.$folderPath);
                }

                continue;
            }

            foreach ($this->allImages($folderPath) as $file) {
                $relative = str_replace('\\', '/', ltrim(str_replace($source, '', $file), DIRECTORY_SEPARATOR));
                $details = $this->images->inspect($file);
                $override = $visualOverrides[$details['hash']] ?? null;
                if (array_key_exists($details['hash'], $visualOverrides) && empty($override['service'])) {
                    $counts['excluded']++;
                    $manifest[] = [
                        'source_path' => $relative,
                        'source_hash' => $details['hash'],
                        'original_folder' => dirname($relative),
                        'linked_service' => null,
                        'old_name' => basename($file),
                        'processing_status' => 'excluded',
                        'processing_notes' => $override['reason'] ?? 'الصورة غير قابلة للإسناد البصري إلى خدمة مؤكدة.',
                    ];

                    continue;
                }

                $profile = $explicit[$relative] ?? null;
                $serviceName = $override['service'] ?? ($profile['service'] ?? $defaultServiceName);
                $profile = $serviceProfiles[$serviceName] ?? $profile ?? [];
                $stem = $override['stem'] ?? config('service-images.service_stems.'.$serviceName, 'service-riyadh');
                $context = $override['context']
                    ?? ($visualContexts[$folder][basename($file)] ?? null)
                    ?? ($profile['context'] ?? $serviceName);
                $cover = $priority === 0 && isset($profile['cover']) && basename($file) === $profile['cover'];
                $assignments->push(compact('file', 'relative', 'details', 'stem', 'context', 'cover', 'priority') + ['service' => $serviceName]);
            }
        }

        return $assignments
            ->unique(fn (array $entry): string => $entry['service'].'|'.$entry['details']['hash'])
            ->values();
    }

    /**
     * مجلدات المصدر مرتبة: مجلد كل خدمة من قاعدة البيانات أولًا (priority 0)
     * ثم المجلدات الإضافية المعرّفة في التهيئة (priority 1).
     *
     * @return list<array{0: string, 1: string, 2: int, 3: bool}>
     */
    private function sourceFolders(): array
    {
        $folders = Service::query()->whereNotNull('image_source_folder')->get()
            ->keyBy('image_source_folder')
            ->map(fn (Service $service, string $folder): array => [$folder, $service->name, 0, true])
            ->values()
            ->all();

        foreach (config('service-images.additional_folders', []) as $folder => $serviceName) {
            $folders[] = [$folder, $serviceName, 1, false];
        }

        return $folders;
    }

    /** @return list<string> */
    private function managedFolders(): array
    {
        return collect(Service::query()->whereNotNull('image_source_folder')->pluck('image_source_folder'))
            ->concat(array_keys(config('service-images.additional_folders', [])))
            ->unique()
            ->values()
            ->all();
    }

    private function ensurePublishedCovers(): void
    {
        $services = Service::published()->with(['parent', 'processedImages', 'children.processedImages'])->get();
        foreach ($services as $service) {
            if (config('site.service_featured_images.'.$service->name)) {
                continue;
            }
            $cover = $service->processedImages->firstWhere('is_cover', true) ?: $service->processedImages->first();
            $childCover = $service->children
                ->flatMap->processedImages
                ->sortByDesc('is_cover')
                ->first();
            $path = $cover?->optimized_path ?: $childCover?->optimized_path ?: $service->parent?->featured_image;
            if ($path) {
                $service->update(['featured_image' => $path, 'featured_image_alt' => $service->name.' في الرياض']);
            }
        }
    }

    private function filesFor(string $source, string $folder, array $mapping): array
    {
        $folderPath = $source.DIRECTORY_SEPARATOR.$folder;
        if (! is_dir($folderPath)) {
            throw new RuntimeException('المجلد المطلوب غير موجود: '.$folderPath);
        }

        $preferredSubdirectory = trim((string) ($mapping['source_subdirectory'] ?? ''), '/\\');
        $searchPath = $preferredSubdirectory !== '' && is_dir($folderPath.DIRECTORY_SEPARATOR.$preferredSubdirectory)
            ? $folderPath.DIRECTORY_SEPARATOR.$preferredSubdirectory
            : $folderPath;
        $extensions = array_map('mb_strtolower', $mapping['extensions'] ?? ['jpg', 'jpeg', 'png', 'webp', 'avif']);
        $excluded = $mapping['exclude'] ?? [];
        $selected = $mapping['files'] ?? null;
        $files = array_values(array_filter(
            $this->allImages($searchPath),
            fn (string $path): bool => in_array(mb_strtolower(pathinfo($path, PATHINFO_EXTENSION)), $extensions, true)
                && ! in_array(basename($path), $excluded, true)
                && ($selected === null || in_array(basename($path), $selected, true)),
        ));
        if ($selected !== null) {
            $missing = array_values(array_diff($selected, array_map('basename', $files)));
            if ($missing !== []) {
                throw new RuntimeException('ملفات الاختيار الموثق غير موجودة في '.$folder.': '.implode('، ', $missing));
            }
        }
        foreach ($mapping['include'] ?? [] as $relative) {
            $path = $source.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (! is_file($path)) {
                throw new RuntimeException('ملف الاستثناء البصري غير موجود: '.$relative);
            }
            $files[] = $path;
        }
        usort($files, fn (string $a, string $b): int => strnatcasecmp(basename($a), basename($b)));

        return array_values(array_unique($files));
    }

    private function allImages(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = collect(File::allFiles($directory))
            ->filter(fn (\SplFileInfo $file): bool => in_array(mb_strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'avif'], true))
            ->map(fn (\SplFileInfo $file): string => $file->getRealPath())
            ->values()
            ->all();
        usort($files, 'strnatcasecmp');

        return $files;
    }

    private function row(string $source, string $file, Service $service, ServiceImage $image, array $details, string $status): array
    {
        $relative = ltrim(str_replace($source, '', $file), DIRECTORY_SEPARATOR);

        return [
            'source_path' => $relative,
            'source_hash' => $details['hash'],
            'original_folder' => dirname(str_replace('\\', '/', $relative)),
            'linked_service' => $service->name,
            'old_name' => basename($file),
            'new_name' => $image->file_name,
            'title' => $image->title,
            'alt_text' => $image->alt_text,
            'caption' => $image->caption,
            'dimensions_before' => $details['width'].'x'.$details['height'],
            'dimensions_after' => $image->width.'x'.$image->height,
            'size_before' => $details['size'],
            'size_after' => $image->file_size,
            'processing_status' => $status,
            'processing_notes' => $image->processing_notes,
        ];
    }
}
