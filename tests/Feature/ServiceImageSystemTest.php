<?php

namespace Tests\Feature;

use App\Livewire\Admin\ServiceImageManager;
use App\Models\Service;
use App\Models\ServiceImage;
use App\Models\User;
use App\Support\ServiceImageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class ServiceImageSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_services_command_imports_safe_unique_images_and_preserves_originals(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $zipPath = tempnam(sys_get_temp_dir(), 'service-images-test-');
        $image = UploadedFile::fake()->image('sample.jpg', 800, 600);
        $second = UploadedFile::fake()->image('hangar.jpg', 900, 700);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('assets/installation/filter.jpeg', file_get_contents($image->getRealPath()));
        $zip->addFromString('assets/replacement/duplicate.jpeg', file_get_contents($image->getRealPath()));
        $zip->addFromString('assets/jumbo/jumbo.jpeg', file_get_contents($second->getRealPath()));
        $zip->addFromString('assets/jumbo/notes.pdf', '%PDF-test');
        $zip->close();

        try {
            $this->artisan('services', ['--zip' => $zipPath, '--manifest' => 'automated-test'])
                ->assertSuccessful();

            $this->assertFileExists($zipPath);
            $this->assertDatabaseCount('service_images', 2);
            $this->assertSame(2, ServiceImage::where('processing_status', 'processed')->count());
            foreach (ServiceImage::all() as $stored) {
                Storage::disk('local')->assertExists($stored->original_path);
                Storage::disk('public')->assertExists($stored->optimized_path);
                $this->assertMatchesRegularExpression('/^[a-z0-9-]+\.webp$/', $stored->file_name);
                $this->assertNotEmpty(collect($stored->variants['webp'])->firstWhere('role', 'gallery'));
                $this->assertNotEmpty(collect($stored->variants['webp'])->firstWhere('role', 'mobile'));
                $this->assertNotEmpty(collect($stored->variants['webp'])->firstWhere('role', 'thumbnail'));
                $this->assertNotEmpty(collect($stored->variants['webp'])->firstWhere('role', 'cover_1600'));
                $this->assertNotEmpty($stored->alt_text);
                $this->assertLessThanOrEqual($stored->original_width * 2, $stored->width);
            }
            Storage::disk('local')->assertExists('service-image-imports/automated-test.json');
            Storage::disk('local')->assertExists('service-image-imports/automated-test.csv');
        } finally {
            @unlink($zipPath);
        }
    }

    public function test_processor_removes_metadata_and_creates_all_supported_formats(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $service = Service::where('name', 'تركيب فلاتر المياه بالرياض')->firstOrFail();
        $file = UploadedFile::fake()->image('private-source.jpg', 500, 400);
        $originalPath = 'service-images/originals/'.$service->id.'/source.jpg';
        Storage::disk('local')->put($originalPath, file_get_contents($file->getRealPath()));
        $image = $service->images()->create([
            'original_name' => 'filter.jpeg', 'file_name' => 'water-filter-installation-riyadh-01.webp',
            'original_path' => $originalPath, 'content_hash' => hash_file('sha256', $file->getRealPath()),
            'title' => 'تركيب فلتر مياه في الرياض', 'alt_text' => 'جهاز فلترة مياه منزلي بعد التركيب في الرياض',
        ]);

        $processed = app(ServiceImageProcessor::class)->process($image);

        $this->assertSame('processed', $processed->processing_status);
        $this->assertSame(1000, $processed->width);
        $this->assertStringContainsString('EXIF وGPS', $processed->processing_notes);
        $this->assertNotEmpty($processed->variants['webp']);
        $this->assertNotEmpty($processed->variants['jpeg']);
        if (function_exists('imageavif')) {
            $this->assertNotEmpty($processed->variants['avif']);
        }
        Storage::disk('local')->assertExists($originalPath);
    }

    public function test_filter_image_import_mappings_cover_the_launch_services(): void
    {
        $folders = collect(config('service-images.folders'));
        $stems = collect(config('service-images.service_stems'));

        $this->assertNotEmpty($folders);
        $this->assertEqualsCanonicalizing(config('site.launch_services'), $stems->keys()->all());
        $this->assertSame('water-filter-installation-riyadh', $stems->get('تركيب فلاتر المياه بالرياض'));
        $this->assertSame('mist-fog-system-riyadh', $stems->get('تركيب وصيانة أنظمة الضباب والرذاذ بالرياض'));
    }

    public function test_admin_can_manage_sort_cover_metadata_soft_delete_and_restore(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $service = Service::firstOrFail();
        $first = $this->makeProcessedImage($service, 1, true);
        $second = $this->makeProcessedImage($service, 2, false);

        $component = Livewire::actingAs($admin)->test(ServiceImageManager::class, ['service' => $service])
            ->set('metadata.'.$first->id.'.title', 'عنوان عربي محدّث')
            ->set('metadata.'.$first->id.'.alt_text', 'هيكل مظلة معدنية في موقع العمل بالرياض')
            ->set('metadata.'.$first->id.'.caption', 'تعليق موثّق للصورة')
            ->call('saveMetadata', $first->id)
            ->assertHasNoErrors()
            ->call('updateOrder', (string) $second->id, 0)
            ->call('setCover', $second->id)
            ->call('deleteImage', $first->id);

        $this->assertDatabaseHas('service_images', ['id' => $first->id, 'title' => 'عنوان عربي محدّث']);
        $this->assertSame(1, $second->refresh()->sort_order);
        $this->assertTrue($second->refresh()->is_cover);
        $this->assertSoftDeleted('service_images', ['id' => $first->id]);

        $component->call('restoreImage', $first->id);
        $this->assertNotSoftDeleted('service_images', ['id' => $first->id]);
    }

    public function test_admin_upload_is_queued_and_non_admin_is_forbidden(): void
    {
        Storage::fake('local');
        Queue::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $regular = User::factory()->create(['is_admin' => false]);
        $service = Service::firstOrFail();

        Livewire::actingAs($admin)->test(ServiceImageManager::class, ['service' => $service])
            ->set('uploads', [UploadedFile::fake()->image('new-image.jpg', 900, 700)])
            ->call('uploadImages')
            ->assertHasNoErrors();
        $this->assertDatabaseHas('service_images', ['service_id' => $service->id, 'processing_status' => 'queued']);

        Livewire::actingAs($regular)->test(ServiceImageManager::class, ['service' => $service])->assertForbidden();
    }

    public function test_service_page_uses_picture_srcset_lazy_gallery_image_schema_and_image_sitemap(): void
    {
        $service = Service::firstOrFail();
        $service->update(['status' => 'published', 'is_active' => true, 'published_at' => now()]);
        $cover = $this->makeProcessedImage($service, 1, true);
        $gallery = $this->makeProcessedImage($service, 2, false);

        $response = $this->get(route('services.show', $service->slug));
        $response->assertOk()
            ->assertSee('class="site-header"', false)
            ->assertDontSee('service-design-header', false)
            ->assertSee('class="home-hero-direct"', false)
            ->assertSee('صور منتجات وتجهيزات مرتبطة بالخدمة')
            ->assertDontSee('data-gallery-toggle', false)
            ->assertSee('<picture>', false)
            ->assertSee('type="image/avif"', false)
            ->assertSee('srcset=', false)
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('loading="lazy"', false)
            ->assertDontSee('class="srvc-location"', false)
            ->assertSee($cover->alt_text)
            ->assertSee($gallery->caption)
            ->assertSee('"@type":"ImageObject"', false);

        $this->get(route('sitemaps.images'))->assertOk()
            ->assertSee($cover->optimized_path)
            ->assertSee($gallery->optimized_path);
    }

    private function makeProcessedImage(Service $service, int $order, bool $cover): ServiceImage
    {
        $base = 'service-images/'.$service->id.'/test-'.$order;
        $variants = [
            'webp' => [
                ['role' => 'gallery', 'path' => $base.'.webp', 'width' => 1200, 'height' => 800, 'size' => 100],
                ['role' => 'mobile', 'path' => $base.'-480.webp', 'width' => 480, 'height' => 320, 'size' => 50],
                ['role' => 'cover_1600', 'path' => $base.'-cover.webp', 'width' => 1200, 'height' => 675, 'size' => 90],
            ],
            'avif' => [
                ['role' => 'gallery', 'path' => $base.'.avif', 'width' => 1200, 'height' => 800, 'size' => 80],
                ['role' => 'cover_1600', 'path' => $base.'-cover.avif', 'width' => 1200, 'height' => 675, 'size' => 70],
            ],
            'jpeg' => [
                ['role' => 'gallery', 'path' => $base.'.jpg', 'width' => 1200, 'height' => 800, 'size' => 150],
                ['role' => 'cover_1600', 'path' => $base.'-cover.jpg', 'width' => 1200, 'height' => 675, 'size' => 130],
            ],
        ];

        return $service->images()->create([
            'original_name' => 'source-'.$order.'.jpg', 'file_name' => 'test-'.$order.'.webp',
            'original_path' => 'service-images/originals/source-'.$order.'.jpg',
            'optimized_path' => $base.'.webp', 'content_hash' => hash('sha256', $service->id.'-'.$order),
            'title' => 'عنوان صورة الخدمة '.$order, 'alt_text' => 'وصف مرئي لصورة الخدمة '.$order,
            'caption' => 'تعليق صورة الخدمة '.$order, 'width' => 1200, 'height' => 800,
            'mime_type' => 'image/webp', 'file_size' => 100, 'variants' => $variants,
            'quality_score' => 88, 'quality_status' => 'good', 'sort_order' => $order,
            'is_cover' => $cover, 'processing_status' => 'processed',
        ]);
    }
}
