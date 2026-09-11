<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaunchContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_launch_catalog_publishes_only_explicitly_confirmed_services_and_launch_articles(): void
    {
        $this->seed();

        $this->assertSame(4, ServiceCategory::query()->count());
        $this->assertSame(10, Service::query()->count());
        $this->assertSame(0, Service::query()->where('status', 'draft')->count());
        $this->assertSame(10, Service::published()->count());
        $this->assertSame(0, Service::query()->where('is_price_published', true)->count());
        $this->assertSame(10, Article::published()->count());
        $this->assertSame(10, Article::published()->whereNotNull('featured_image')->count());
        $this->assertEqualsCanonicalizing(config('site.launch_services'), Service::published()->pluck('name')->all());

        foreach (config('site.launch_services') as $name) {
            $this->assertDatabaseHas('services', ['name' => $name, 'status' => 'published']);
            $service = Service::query()->where('name', $name)->firstOrFail();
            $this->assertSame($name, $service->seo()->value('meta_title'));
            $this->assertSame('Service', $service->seo()->value('schema_type'));
        }

        foreach (config('site.service_featured_images') as $serviceName => $path) {
            $this->assertDatabaseHas('services', ['name' => $serviceName, 'featured_image' => $path]);
            $this->assertFileExists(storage_path('app/public/'.$path));
        }

        Article::query()->with('mediaAssets')->each(function (Article $article): void {
            $this->assertCount(5, $article->mediaAssets);
            $this->assertSame($article->mediaAssets->first()->path, $article->featured_image);
            $this->assertFileExists(storage_path('app/public/'.$article->featured_image));
        });
        $this->assertSame(19, MediaAsset::query()->count());
    }

    public function test_every_seeded_service_has_the_full_editable_content_template(): void
    {
        $this->seed();

        $fields = ['content', 'types', 'use_cases', 'materials_details', 'advantages', 'disadvantages', 'installation_steps', 'price_factors', 'selection_tips', 'cta'];

        Service::query()->with(['materials', 'faqs'])->each(function (Service $service) use ($fields): void {
            foreach ($fields as $field) {
                $this->assertNotEmpty($service->{$field}, $service->name.' is missing '.$field);
            }
            $this->assertGreaterThanOrEqual(1, $service->materials->count(), $service->name.' has no linked material');
            $this->assertSame(2, $service->faqs->count(), $service->name.' must have two FAQs');
        });
    }

    public function test_only_central_riyadh_is_published_and_other_area_pages_are_noindex(): void
    {
        $this->seed();

        $this->assertSame(['حي المونسية'], Area::published()->pluck('name')->all());
        $this->assertDatabaseHas('areas', ['name' => 'حي المونسية', 'is_primary' => true, 'status' => 'published']);

        Area::query()->where('name', '!=', 'حي المونسية')->with('seo')->each(function (Area $area): void {
            $this->assertSame('draft', $area->status);
            $this->assertSame('noindex,follow', $area->seo->robots);
            $this->get(route('areas.show', $area->slug))->assertNotFound();
        });
    }

    public function test_reseeding_does_not_duplicate_catalog_records(): void
    {
        $this->seed();
        $service = Service::query()->where('name', 'تركيب فلاتر المياه بالرياض')->firstOrFail();
        $canonicalContent = $service->content;
        $service->update(['content' => 'نص مؤقت للاختبار']);
        $area = Area::query()->where('name', 'شمال الرياض')->firstOrFail();
        $area->update(['status' => 'published', 'published_at' => now()]);
        $this->seed();

        $this->assertSame(10, Service::query()->count());
        $this->assertSame(10, Article::query()->count());
        $this->assertSame(19, MediaAsset::query()->count());
        $this->assertSame(5, Area::query()->count());
        $this->assertSame($canonicalContent, $service->fresh()->content);
        $this->assertSame('draft', $area->fresh()->status);
    }
}
