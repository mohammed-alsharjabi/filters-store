<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\ServiceCategory;
use App\Policies\ContentPolicy;
use App\Support\CuratedServiceAssetImporter;
use App\Support\SettingsRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('leads', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('admin-login', fn (Request $request) => Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
        Gate::define('manage-content', [ContentPolicy::class, 'manage']);
        Gate::define('view-leads', [ContentPolicy::class, 'viewLeads']);
        View::composer('*', function ($view): void {
            $view->with('siteSettings', app(SettingsRepository::class)->public());
        });
        View::composer(['partials.header', 'partials.footer'], function ($view): void {
            $categories = Schema::hasTable('service_categories')
                ? Cache::remember('navigation.service-categories', now()->addMinutes(20), fn () => ServiceCategory::query()
                    ->where('is_active', true)
                    ->with(['services' => fn ($query) => $query->published()->orderBy('sort_order')])
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (ServiceCategory $category): array => [
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'services' => $category->services->map(fn ($service): array => [
                            'name' => $service->name,
                            'slug' => $service->slug,
                        ])->all(),
                    ])->all())
                : collect();
            $view->with('navServiceCategories', $categories);
            $view->with('hasPublishedProjects', Schema::hasTable('projects')
                ? Cache::remember('navigation.has-published-projects', now()->addMinutes(20), fn () => Project::published()->exists())
                : false);
        });
        $this->scheduleServiceAssetSyncAfterResponse();
    }

    private function scheduleServiceAssetSyncAfterResponse(): void
    {
        if ($this->app->runningInConsole()
            || $this->app->runningUnitTests()
            || ! config('service-images.auto_sync_on_web_request')
            || ! request()->isMethod('GET')
            || ! request()->is('/', 'الخدمات*')) {
            return;
        }

        $source = (string) config('service-images.curated_source');
        if (! is_dir($source)) {
            return;
        }

        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
        $signatureParts = collect(File::allFiles($source))
            ->filter(fn (\SplFileInfo $file): bool => in_array(mb_strtolower($file->getExtension()), $extensions, true))
            ->map(fn (\SplFileInfo $file): string => str_replace('\\', '/', $file->getRelativePathname()).'|'.$file->getMTime().'|'.$file->getSize())
            ->sort()
            ->values()
            ->all();
        $signature = hash('sha256', implode("\n", $signatureParts));
        if (hash_equals((string) Cache::get('service-assets.source-signature', ''), $signature)
            || ! Cache::add('service-assets.sync-lock', true, now()->addMinutes(15))) {
            return;
        }

        $this->app->terminating(function () use ($source, $signature): void {
            try {
                app(CuratedServiceAssetImporter::class)->import($source, sync: true, publish: true, queue: false);
                Cache::forever('service-assets.source-signature', $signature);
            } catch (\Throwable $exception) {
                report($exception);
            } finally {
                Cache::forget('service-assets.sync-lock');
            }
        });
    }
}
