<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SeoController extends Controller
{
    public function index(): Response
    {
        $sitemaps = collect([
            ['loc' => route('sitemaps.pages'), 'lastmod' => $this->latestDate(ServiceCategory::class)],
            ['loc' => route('sitemaps.services'), 'lastmod' => $this->latestPublishedDate(Service::class)],
            ['loc' => route('sitemaps.projects'), 'lastmod' => $this->latestPublishedDate(Project::class)],
            ['loc' => route('sitemaps.areas'), 'lastmod' => $this->latestPublishedDate(Area::class)],
            ['loc' => route('sitemaps.articles'), 'lastmod' => $this->latestPublishedDate(Article::class)],
            ['loc' => route('sitemaps.images'), 'lastmod' => $this->latestPublishedDate(Project::class)],
        ]);

        return $this->xml('seo.sitemap-index', compact('sitemaps'));
    }

    public function pages(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'lastmod' => now()->toDateString(), 'priority' => '1.0'],
            ...collect(['about', 'services.index', 'areas.index', 'guide.index', 'prices', 'quote', 'contact', 'privacy', 'terms'])
                ->map(fn ($name) => ['loc' => route($name), 'lastmod' => now()->toDateString(), 'priority' => '0.7'])->all(),
            ...(Project::published()->exists()
                ? [['loc' => route('projects.index'), 'lastmod' => now()->toDateString(), 'priority' => '0.7']]
                : []),
        ]);
        ServiceCategory::query()->where('is_active', true)->whereHas('services', fn ($query) => $query->published())->each(
            fn ($item) => $urls->push(['loc' => route('services.category', $item->slug), 'lastmod' => $item->updated_at->toDateString(), 'priority' => '0.8'])
        );

        return $this->urlset($urls);
    }

    public function services(): Response
    {
        return $this->urlset(Service::published()->get()->map(fn ($item) => [
            'loc' => route('services.show', $item->slug), 'lastmod' => $item->updated_at->toDateString(), 'priority' => '0.9',
        ]));
    }

    public function projects(): Response
    {
        return $this->urlset(Project::published()->get()->map(fn ($item) => [
            'loc' => route('projects.show', $item->slug), 'lastmod' => $item->updated_at->toDateString(), 'priority' => '0.8',
        ]));
    }

    public function areas(): Response
    {
        return $this->urlset(Area::published()->get()->map(fn ($item) => [
            'loc' => route('areas.show', $item->slug), 'lastmod' => $item->updated_at->toDateString(), 'priority' => '0.8',
        ]));
    }

    public function articles(): Response
    {
        return $this->urlset(Article::published()->get()->map(fn ($item) => [
            'loc' => route('guide.show', $item->slug), 'lastmod' => $item->updated_at->toDateString(), 'priority' => '0.7',
        ]));
    }

    public function images(): Response
    {
        $items = collect();

        Service::published()->with(['images' => fn ($query) => $query->where('processing_status', 'processed')->orderBy('sort_order')])->get()->each(function (Service $service) use ($items): void {
            foreach ($service->images as $image) {
                $items->push([
                    'page' => route('services.show', $service->slug),
                    'image' => asset('storage/'.$image->optimized_path),
                    'title' => $image->title ?: $image->alt_text ?: $service->name,
                    'caption' => $image->caption,
                ]);
            }
        });
        Service::published()->whereNotNull('featured_image')->get()->each(fn (Service $service) => $items->push([
            'page' => route('services.show', $service->slug),
            'image' => asset('storage/'.$service->featured_image),
            'title' => $service->featured_image_alt ?: $service->name,
            'caption' => $service->featured_image_caption,
        ]));
        Article::published()->whereNotNull('featured_image')->get()->each(fn (Article $article) => $items->push([
            'page' => route('guide.show', $article->slug),
            'image' => asset('storage/'.$article->featured_image),
            'title' => $article->featured_image_alt ?: $article->title,
            'caption' => $article->featured_image_caption,
        ]));
        Area::published()->whereNotNull('featured_image')->get()->each(fn (Area $area) => $items->push([
            'page' => route('areas.show', $area->slug),
            'image' => asset('storage/'.$area->featured_image),
            'title' => $area->featured_image_alt ?: $area->name,
            'caption' => $area->featured_image_caption,
        ]));
        Project::published()->with('images')->get()->each(function (Project $project) use ($items): void {
            foreach ($project->images as $image) {
                $items->push([
                    'page' => route('projects.show', $project->slug),
                    'image' => asset('storage/'.$image->path),
                    'title' => $image->alt_text ?: $project->title,
                    'caption' => $image->caption,
                ]);
            }
        });

        return $this->xml('seo.image-sitemap', compact('items'));
    }

    public function robots(): Response
    {
        $basePath = rtrim((string) parse_url(url('/'), PHP_URL_PATH), '/');
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: '.$basePath.'/admin',
            'Disallow: '.$basePath.'/طلبات',
            'Disallow: '.$basePath.'/*?*filter=',
            'Disallow: '.$basePath.'/*?*search=',
            'Disallow: '.$basePath.'/*?*sort=',
            'Sitemap: '.route('sitemaps.index'),
            '',
        ]);

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function urlset(Collection $urls): Response
    {
        return $this->xml('seo.sitemap', compact('urls'));
    }

    private function xml(string $view, array $data): Response
    {
        return response()->view($view, $data)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function latestDate(string $model): string
    {
        $latest = $model::query()->max('updated_at');

        return $latest ? Carbon::parse($latest)->toDateString() : now()->toDateString();
    }

    private function latestPublishedDate(string $model): string
    {
        $latest = $model::published()->max('updated_at');

        return $latest ? Carbon::parse($latest)->toDateString() : now()->toDateString();
    }
}
