<?php

namespace App\Support;

use App\Models\Area;
use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\SeoMetadata;
use App\Models\Service;
use App\Models\ServiceImage;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class Seo
{
    public static function page(string $title, string $description, ?Model $model = null, array $breadcrumbs = [], array $extraSchemas = []): array
    {
        $metadata = $model
            ? ($model->relationLoaded('seo') ? $model->seo : $model->seo()->first())
            : (Schema::hasTable('seo_metadata') ? SeoMetadata::query()->where('route_name', request()->route()?->getName())->first() : null);
        $title = $metadata?->meta_title ?: $title;
        $description = Str::limit(trim(strip_tags($metadata?->meta_description ?: $description)), 160, '');
        $canonical = $metadata?->canonical_url ?: self::requestCanonical();
        $robots = $metadata?->robots ?: 'index,follow,max-image-preview:large';

        if (collect(request()->query())->except('page')->isNotEmpty()) {
            $robots = 'noindex,follow';
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'og_title' => $metadata?->og_title ?: $title,
            'og_description' => $metadata?->og_description ?: $description,
            'og_image' => $metadata?->og_image ?: self::modelOgImage($model),
            'og_type' => match (true) {
                $model instanceof Article => 'article',
                $model instanceof Product => 'product',
                default => 'website',
            },
            'prev' => null,
            'next' => null,
            'schemas' => array_values(array_filter([
                self::organizationSchema(),
                self::businessSchema(),
                $breadcrumbs ? self::breadcrumbSchema($breadcrumbs) : null,
                ...self::modelSchemas($model),
                $metadata?->schema_extra,
                ...$extraSchemas,
            ])),
        ];
    }

    public static function paginate(array $seo, Paginator $paginator): array
    {
        if ($paginator->currentPage() > 1) {
            $seo['canonical'] = $paginator->url($paginator->currentPage());
        }
        $seo['prev'] = $paginator->previousPageUrl();
        $seo['next'] = $paginator->nextPageUrl();

        return $seo;
    }

    public static function noindex(array $seo): array
    {
        $seo['robots'] = 'noindex,follow';

        return $seo;
    }

    public static function organizationSchema(): array
    {
        $settings = app(SettingsRepository::class)->public();

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => $settings['site_name'],
            'url' => url('/'),
            'logo' => filled($settings['logo_url'] ?? null) ? $settings['logo_url'] : asset('brand/filters-store-logo.png'),
            'telephone' => $settings['phone_e164'],
        ]);
    }

    public static function businessSchema(): array
    {
        $settings = app(SettingsRepository::class)->public();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            '@id' => url('/').'#localbusiness',
            'name' => $settings['site_name'],
            'url' => url('/'),
            'parentOrganization' => ['@id' => url('/').'#organization'],
            'telephone' => $settings['phone_e164'],
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => $settings['address'], 'addressLocality' => $settings['city'], 'addressCountry' => 'SA'],
            'areaServed' => ['@type' => 'City', 'name' => $settings['city']],
            'contactPoint' => ['@type' => 'ContactPoint', 'telephone' => $settings['phone_e164'], 'contactType' => 'customer service', 'availableLanguage' => 'Arabic'],
        ];
    }

    public static function faqSchema(iterable $faqs): ?array
    {
        $items = collect($faqs)->where('is_active', true)->map(fn ($faq) => [
            '@type' => 'Question', 'name' => $faq->question, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($faq->answer)],
        ])->values()->all();

        return $items ? ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items] : null;
    }

    private static function requestCanonical(): string
    {
        $page = request()->integer('page');

        return $page > 1 ? request()->url().'?page='.$page : request()->url();
    }

    private static function breadcrumbSchema(array $items): array
    {
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => collect($items)->values()->map(
            fn ($item, $index) => ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $item['name'], 'item' => $item['url']]
        )->all()];
    }

    private static function modelSchemas(?Model $model): array
    {
        if (! $model) {
            return [];
        }

        $settings = app(SettingsRepository::class)->public();
        $schemas = [];

        if ($model instanceof Service) {
            $schemas[] = ['@context' => 'https://schema.org', '@type' => 'Service', 'name' => $model->name, 'description' => $model->excerpt, 'url' => route('services.show', $model->slug), 'areaServed' => ['@type' => 'City', 'name' => $settings['city']], 'provider' => ['@id' => url('/').'#localbusiness']];
            if ($model->featured_image) {
                $schemas[] = self::featuredImageSchema($model, $model->name);
            }
            if ($model->relationLoaded('images')) {
                foreach ($model->images as $image) {
                    $schemas[] = self::serviceImageSchema($image, $model);
                }
            }
            if ($model->relationLoaded('mediaAssets')) {
                foreach ($model->mediaAssets as $image) {
                    $schemas[] = self::mediaAssetSchema($image);
                }
            }
        } elseif ($model instanceof Product) {
            $productSchema = array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                '@id' => route('products.show', $model->slug).'#product',
                'name' => $model->name,
                'description' => $model->excerpt ?: strip_tags((string) $model->description),
                'url' => route('products.show', $model->slug),
                'image' => $model->imageUrl(),
                'sku' => $model->sku,
                'gtin' => $model->gtin,
                'mpn' => $model->mpn,
                'brand' => $model->brand ? ['@type' => 'Brand', 'name' => $model->brand] : null,
            ], fn ($value) => $value !== null && $value !== '');
            if ($model->price !== null && (float) $model->price > 0) {
                $productSchema['offers'] = [
                    '@type' => 'Offer',
                    'url' => route('products.show', $model->slug),
                    'priceCurrency' => $model->currency,
                    'price' => $model->price,
                    'availability' => $model->schemaAvailability(),
                    'itemCondition' => 'https://schema.org/NewCondition',
                    'seller' => ['@id' => url('/').'#localbusiness'],
                ];
            }
            $schemas[] = $productSchema;
            if ($model->featured_image) {
                $schemas[] = self::featuredImageSchema($model, $model->name);
            }
        } elseif ($model instanceof ProductCategory) {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $model->name,
                'description' => $model->description ?: 'منتجات '.$model->name,
                'url' => route('products.category', $model->slug),
                'isPartOf' => ['@id' => url('/').'#organization'],
            ];
            if ($model->featured_image) {
                $schemas[] = self::featuredImageSchema($model, $model->name);
            }
        } elseif ($model instanceof Article) {
            $articleImage = $model->relationLoaded('mediaAssets') ? $model->mediaAssets->first() : null;
            $fallbackServiceImage = self::articleServiceImage($model);
            $schemas[] = array_filter(['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $model->title, 'description' => $model->excerpt, 'datePublished' => $model->published_at?->toAtomString(), 'dateModified' => $model->updated_at?->toAtomString(), 'mainEntityOfPage' => route('guide.show', $model->slug), 'author' => ['@id' => url('/').'#organization'], 'publisher' => ['@id' => url('/').'#organization'], 'image' => $model->featured_image ? asset('storage/'.$model->featured_image) : ($articleImage?->imageUrl() ?: ($fallbackServiceImage ? asset('storage/'.$fallbackServiceImage->optimized_path) : null))]);
            if ($model->featured_image) {
                $schemas[] = self::featuredImageSchema($model, $model->title);
            } elseif ($articleImage) {
                $schemas[] = self::mediaAssetSchema($articleImage);
            } elseif ($fallbackServiceImage) {
                $schemas[] = self::serviceImageSchema($fallbackServiceImage, $fallbackServiceImage->service);
            }
        } elseif ($model instanceof Project) {
            $schemas[] = ['@context' => 'https://schema.org', '@type' => 'CreativeWork', 'name' => $model->title, 'description' => $model->excerpt, 'url' => route('projects.show', $model->slug), 'locationCreated' => ['@type' => 'Place', 'name' => $model->area->name], 'about' => ['@type' => 'Service', 'name' => $model->service->name]];
            foreach ($model->images as $image) {
                $schemas[] = self::projectImageSchema($image, $model);
            }
        } elseif ($model instanceof Area) {
            $schemas[] = ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => $model->name, 'description' => $model->excerpt, 'url' => route('areas.show', $model->slug)];
            if ($model->featured_image) {
                $schemas[] = self::featuredImageSchema($model, $model->name);
            }
        }

        if (filled($model->video_url ?? null)) {
            $schemas[] = self::videoSchema($model);
        }

        return array_values(array_filter($schemas));
    }

    private static function featuredImageSchema(Model $model, string $fallbackName): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ImageObject',
            'contentUrl' => asset('storage/'.$model->featured_image),
            'name' => $model->featured_image_alt ?: $fallbackName,
            'caption' => $model->featured_image_caption,
        ]);
    }

    private static function projectImageSchema(ProjectImage $image, Project $project): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ImageObject',
            'contentUrl' => asset('storage/'.$image->path),
            'name' => $image->alt_text ?: $project->title,
            'caption' => $image->caption,
            'width' => $image->width,
            'height' => $image->height,
            'representativeOfPage' => $image->is_cover,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private static function serviceImageSchema(ServiceImage $image, Service $service): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ImageObject',
            'contentUrl' => asset('storage/'.$image->optimized_path),
            'name' => $image->title ?: $image->alt_text ?: $service->name,
            'caption' => $image->caption,
            'width' => $image->width,
            'height' => $image->height,
            'representativeOfPage' => $image->is_cover,
            'encodingFormat' => $image->mime_type,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private static function mediaAssetSchema(MediaAsset $image): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ImageObject',
            'contentUrl' => $image->imageUrl(),
            'name' => $image->name,
            'caption' => $image->caption,
            'width' => $image->width,
            'height' => $image->height,
            'encodingFormat' => $image->mime_type,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private static function videoSchema(Model $model): array
    {
        $title = $model->video_title ?: ($model->title ?? $model->name ?? 'فيديو');
        $thumbnail = $model->video_thumbnail ?: self::modelOgImage($model);
        $seconds = (int) ($model->video_duration_seconds ?? 0);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $title,
            'description' => $model->excerpt ?? $title,
            'contentUrl' => $model->video_url,
            'thumbnailUrl' => $thumbnail ? self::absoluteImage($thumbnail) : null,
            'uploadDate' => $model->updated_at?->toAtomString(),
            'duration' => $seconds > 0 ? 'PT'.$seconds.'S' : null,
        ]);
    }

    private static function modelOgImage(?Model $model): ?string
    {
        if (! $model) {
            return null;
        }
        if ($model instanceof Project) {
            return $model->images->firstWhere('is_cover', true)?->path ?: $model->images->first()?->path;
        }
        if ($model instanceof Service && $model->relationLoaded('images')) {
            return $model->images->firstWhere('is_cover', true)?->optimized_path ?: $model->images->first()?->optimized_path ?: $model->featured_image;
        }
        if ($model instanceof Article) {
            return $model->featured_image ?: ($model->relationLoaded('mediaAssets') ? $model->mediaAssets->first()?->path : null) ?: self::articleServiceImage($model)?->optimized_path;
        }
        if ($model instanceof Product || $model instanceof ProductCategory) {
            return $model->featured_image;
        }

        return $model->featured_image ?? null;
    }

    private static function articleServiceImage(Article $article): ?ServiceImage
    {
        if (! $article->relationLoaded('services')) {
            return null;
        }

        return $article->services
            ->filter(fn (Service $service): bool => $service->relationLoaded('images'))
            ->flatMap(fn (Service $service) => $service->images)
            ->sortByDesc('is_cover')
            ->first();
    }

    private static function absoluteImage(string $path): string
    {
        return Str::startsWith($path, ['http://', 'https://']) ? $path : asset('storage/'.ltrim($path, '/'));
    }
}
