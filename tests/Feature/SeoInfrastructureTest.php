<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_sitemap_index_robots_and_split_sitemaps_are_valid_and_exclude_drafts(): void
    {
        $published = Service::query()->firstOrFail();
        $published->update(['status' => 'published', 'is_active' => true, 'published_at' => now()]);
        $draft = Service::query()->whereKeyNot($published->id)->firstOrFail();
        $draft->update(['status' => 'draft', 'published_at' => null]);

        $this->get(route('sitemaps.index'))->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('sitemaps.services'), false)
            ->assertSee(route('sitemaps.projects'), false)
            ->assertSee(route('sitemaps.areas'), false)
            ->assertSee(route('sitemaps.articles'), false)
            ->assertSee(route('sitemaps.images'), false);

        $this->get(route('sitemaps.services'))->assertOk()
            ->assertSee(route('services.show', $published->slug), false)
            ->assertDontSee(route('services.show', $draft->slug), false);

        $this->get(route('robots'))->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.route('sitemaps.index'));
    }

    public function test_meta_canonical_social_cards_noindex_and_schema_are_rendered_without_meta_keywords(): void
    {
        $service = Service::query()->firstOrFail();
        $service->update([
            'status' => 'published', 'is_active' => true, 'published_at' => now(),
            'video_url' => 'https://cdn.example.test/video.mp4', 'video_title' => 'فيديو الخدمة',
            'video_thumbnail' => 'https://cdn.example.test/thumb.jpg',
        ]);

        $response = $this->get(route('services.show', $service->slug));
        $response->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('services.show', $service->slug).'">', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false)
            ->assertSee('"@type":"Organization"', false)
            ->assertSee(asset('brand/filters-store-logo.png'), false)
            ->assertSee('"@type":"LocalBusiness"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"@type":"Service"', false)
            ->assertSee('"@type":"VideoObject"', false)
            ->assertDontSee('meta name="keywords"', false)
            ->assertDontSee('"@type":"Product"', false);

        $this->get(route('services.index', ['filter' => 'pvc']))
            ->assertOk()->assertSee('content="noindex,follow"', false);
        $this->get('/admin/login')->assertOk()->assertDontSee('meta name="keywords"', false);
    }

    public function test_article_project_area_and_image_schema_are_emitted_only_for_published_content(): void
    {
        $service = Service::query()->firstOrFail();
        $service->update(['status' => 'published', 'is_active' => true, 'published_at' => now()]);
        $area = Area::published()->firstOrFail();
        $article = Article::query()->firstOrFail();
        $article->update(['status' => 'published', 'published_at' => now()]);
        $project = Project::create([
            'service_id' => $service->id, 'area_id' => $area->id,
            'title' => 'مشروع موثق للاختبار', 'excerpt' => 'وصف مشروع موثق.',
            'status' => 'published', 'is_published' => true, 'published_at' => now(),
        ]);
        $project->images()->create(['path' => 'content/project.webp', 'alt_text' => 'مشروع مظلة', 'caption' => 'صورة المشروع', 'is_cover' => true]);

        $this->get(route('guide.show', $article->slug))->assertOk()->assertSee('"@type":"Article"', false);
        $this->get(route('projects.show', $project->slug))->assertOk()->assertSee('"@type":"ImageObject"', false);
        $this->get(route('areas.show', $area->slug))->assertOk()->assertSee('"@type":"WebPage"', false);
        $this->get(route('sitemaps.images'))->assertOk()->assertSee('content/project.webp');
    }

    public function test_public_static_titles_and_descriptions_are_unique(): void
    {
        $routes = ['home', 'about', 'services.index', 'projects.index', 'areas.index', 'guide.index', 'prices', 'quote', 'contact', 'privacy', 'terms'];
        $titles = [];
        $descriptions = [];

        foreach ($routes as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();
            preg_match('/<title>(.*?)<\/title>/su', $html, $titleMatch);
            preg_match('/<meta name="description" content="([^"]*)">/su', $html, $descriptionMatch);
            $titles[] = html_entity_decode($titleMatch[1] ?? '');
            $descriptions[] = html_entity_decode($descriptionMatch[1] ?? '');
        }

        $this->assertCount(count($routes), array_unique($titles));
        $this->assertCount(count($routes), array_unique($descriptions));
        $this->assertNotContains('', $titles);
        $this->assertNotContains('', $descriptions);
    }
}
