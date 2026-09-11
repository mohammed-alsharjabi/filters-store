<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Article;
use App\Models\Service;
use App\Models\User;
use App\Support\AdminContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteAndLinkHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_public_get_route_has_a_healthy_response(): void
    {
        $this->seed();
        $service = Service::query()->firstOrFail();
        $service->update(['status' => 'published', 'is_active' => true, 'published_at' => now()]);
        $article = Article::query()->firstOrFail();
        $article->update(['status' => 'published', 'published_at' => now()]);

        $urls = [
            route('home'), route('about'), route('services.index'), route('services.category', $service->category->slug), route('services.show', $service->slug),
            route('projects.index'), route('areas.index'), route('areas.show', Area::published()->firstOrFail()->slug),
            route('guide.index'), route('guide.show', $article->slug), route('prices'), route('quote'), route('contact'), route('privacy'), route('terms'),
            route('sitemap'), route('sitemaps.index'), route('sitemaps.pages'), route('sitemaps.services'), route('sitemaps.projects'),
            route('sitemaps.areas'), route('sitemaps.articles'), route('sitemaps.images'), route('robots'), route('admin.login'),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertSuccessful();
        }
    }

    public function test_all_admin_content_routes_render_for_admin_and_reject_regular_users(): void
    {
        $this->seed();
        $admin = User::factory()->create(['is_admin' => true]);
        $regular = User::factory()->create(['is_admin' => false]);

        foreach (array_keys(AdminContent::all()) as $type) {
            $this->actingAs($regular)->get(route('admin.content.index', $type))->assertForbidden();
            $this->actingAs($admin)->get(route('admin.content.index', $type))->assertOk();
            $this->actingAs($admin)->get(route('admin.content.create', $type))->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.settings'))->assertOk();
        $this->actingAs($admin)->get(route('admin.leads'))->assertOk();
    }

    public function test_rendered_public_pages_have_no_broken_internal_navigation_links(): void
    {
        $this->seed();
        $service = Service::query()->firstOrFail();
        $service->update(['status' => 'published', 'is_active' => true, 'published_at' => now()]);
        $startingPages = [route('home'), route('services.index'), route('services.show', $service->slug), route('quote'), route('contact')];
        $checked = [];

        foreach ($startingPages as $page) {
            $html = $this->get($page)->assertOk()->getContent();
            preg_match_all('/href="([^"]+)"/u', $html, $matches);

            foreach (array_unique($matches[1]) as $href) {
                $url = html_entity_decode($href);
                if (! str_starts_with($url, url('/')) || str_contains($url, '#') || str_contains($url, '/storage/') || str_contains($url, '/build/')) {
                    continue;
                }
                $path = parse_url($url, PHP_URL_PATH) ?: '/';
                if (isset($checked[$path])) {
                    continue;
                }
                $checked[$path] = true;
                if (pathinfo($path, PATHINFO_EXTENSION) !== '') {
                    $this->assertFileExists(public_path(ltrim($path, '/')), "ملف عام معطل: {$url}");

                    continue;
                }
                $response = $this->get($path);
                $this->assertLessThan(400, $response->getStatusCode(), "رابط داخلي معطل: {$url}");
            }
        }

        $this->assertNotEmpty($checked);
    }
}
