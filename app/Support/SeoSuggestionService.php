<?php

namespace App\Support;

use App\Models\Area;
use App\Models\ArticleCategory;
use App\Models\ProductCategory;
use App\Models\SeoMetadata;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SeoSuggestionService
{
    public function suggest(string $type, array $data, ?Model $model = null): array
    {
        $title = trim((string) ($data[$this->titleField($type)] ?? ''));
        if ($title === '') {
            return [];
        }

        $slug = ArabicSlugger::normalize((string) (filled($data['slug'] ?? null) ? $data['slug'] : $title));
        $context = $this->context($type, $data);
        $metaTitle = Str::limit($title.' | فلاتر وتحلية المياه بالرياض', 70, '');
        $descriptionSource = trim(strip_tags((string) ($data['excerpt'] ?? $data['description'] ?? $data['body'] ?? $data['content'] ?? '')));
        $metaDescription = $descriptionSource !== ''
            ? Str::limit(preg_replace('/\s+/u', ' ', $descriptionSource) ?: $descriptionSource, 160, '')
            : Str::limit('تعرّف على '.$title.($context ? ' في '.$context : '').'، والتفاصيل التي تساعد على اختيار المواصفات وطلب المعاينة داخل الرياض.', 160, '');
        $focusKeyword = $this->focusKeyword($title);
        $relatedTerms = collect([
            $context,
            $this->categoryName($type, $data),
            'المعاينة في الرياض',
            'الخامات المناسبة',
            $type === 'projects' ? 'مشروع منفذ' : null,
            $type === 'articles' ? 'دليل الاختيار' : null,
        ])->filter()->unique()->implode("\n");
        $altText = trim($title.($context ? ' - '.$context : '').' - الرياض');

        return [
            'slug' => $slug,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'focus_keyword' => $focusKeyword,
            'related_terms' => $relatedTerms,
            'og_title' => $metaTitle,
            'og_description' => $metaDescription,
            'canonical_url' => $this->canonical($type, $slug),
            'internal_links' => $this->internalLinks($type, $data, $model),
            'schema_type' => match ($type) {
                'services' => 'Service',
                'products' => 'Product',
                'product-categories' => 'CollectionPage',
                'articles' => 'Article',
                'projects' => 'CreativeWork + ImageObject',
                'areas' => 'WebPage',
                default => 'WebPage',
            },
            'featured_image_alt' => $altText,
            'featured_image_caption' => trim($title.($context ? ' في '.$context : '')),
        ];
    }

    public function warnings(array $seo, ?Model $model = null): array
    {
        $warnings = [];
        $metaTitle = trim((string) ($seo['meta_title'] ?? ''));
        $focusKeyword = trim((string) ($seo['focus_keyword'] ?? ''));

        if ($metaTitle !== '' && $this->duplicateQuery($model)->where('meta_title', $metaTitle)->exists()) {
            $warnings[] = 'عنوان SEO مستخدم في صفحة أخرى. عدّله لتفادي تشابه نتائج البحث.';
        }
        if ($focusKeyword !== '' && $this->duplicateQuery($model)->where('focus_keyword', $focusKeyword)->exists()) {
            $warnings[] = 'الكلمة المستهدفة مستخدمة في صفحة أخرى وقد يحدث تنافس داخلي بين الصفحتين.';
        }

        return $warnings;
    }

    public function imageAlt(string $title, ?string $area = null, ?string $stage = null): string
    {
        return collect([$title, $stage, $area, 'الرياض'])->filter()->implode(' - ');
    }

    private function duplicateQuery(?Model $model)
    {
        return SeoMetadata::query()->when(
            $model?->exists,
            fn ($query) => $query->where(fn ($sub) => $sub
                ->whereNull('seoable_type')
                ->orWhere('seoable_type', '!=', $model::class)
                ->orWhereNull('seoable_id')
                ->orWhere('seoable_id', '!=', $model->getKey()))
        );
    }

    private function titleField(string $type): string
    {
        return in_array($type, ['projects', 'articles'], true) ? 'title' : 'name';
    }

    private function focusKeyword(string $title): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace(['|', '؟', '.', '،'], ' ', $title)) ?: $title);
    }

    private function context(string $type, array $data): ?string
    {
        if ($type === 'projects' && filled($data['area_id'] ?? null)) {
            return Area::query()->whereKey($data['area_id'])->value('name');
        }

        return $type === 'areas' ? trim((string) ($data['name'] ?? '')) : null;
    }

    private function categoryName(string $type, array $data): ?string
    {
        return match ($type) {
            'services' => filled($data['service_category_id'] ?? null) ? ServiceCategory::query()->whereKey($data['service_category_id'])->value('name') : null,
            'projects' => filled($data['service_id'] ?? null) ? Service::query()->whereKey($data['service_id'])->value('name') : null,
            'articles' => filled($data['article_category_id'] ?? null) ? ArticleCategory::query()->whereKey($data['article_category_id'])->value('name') : null,
            'products' => filled($data['product_category_id'] ?? null) ? ProductCategory::query()->whereKey($data['product_category_id'])->value('name') : null,
            default => null,
        };
    }

    private function canonical(string $type, string $slug): string
    {
        $prefix = match ($type) {
            'services' => 'الخدمات',
            'projects' => 'المشاريع',
            'articles' => 'الدليل',
            'areas' => 'المناطق',
            'service-categories' => 'الخدمات/تصنيف',
            'products' => 'المنتجات',
            'product-categories' => 'منتجات-الفلاتر/تصنيف',
            default => '',
        };

        return url(trim($prefix.'/'.$slug, '/'));
    }

    private function internalLinks(string $type, array $data, ?Model $model): string
    {
        $links = collect();

        if ($type === 'services') {
            $links->push('احجز الآن | '.route('quote'));
            $links->push('دليل الأسعار | '.route('prices'));
            $links->push('مناطق الخدمة | '.route('areas.index'));
        } elseif ($type === 'products') {
            $links->push('كل المنتجات | '.route('products.index'));
            $links->push('سلة المشتريات | '.route('cart.show'));
        } elseif ($type === 'product-categories') {
            $links->push('كل المنتجات | '.route('products.index'));
        } elseif ($type === 'projects') {
            if ($service = Service::query()->find($data['service_id'] ?? null)) {
                $links->push($service->name.' | '.route('services.show', $service->slug));
            }
            if ($area = Area::query()->find($data['area_id'] ?? null)) {
                $links->push($area->name.' | '.route('areas.show', $area->slug));
            }
            $links->push('طلب مشروع مشابه | '.route('quote'));
        } elseif ($type === 'articles') {
            $links->push('كل الخدمات | '.route('services.index'));
            $links->push('احجز الآن | '.route('quote'));
        } elseif ($type === 'areas') {
            $links->push('الخدمات | '.route('services.index'));
            $links->push('المشاريع | '.route('projects.index'));
        }

        return $links->filter()->unique()->implode("\n");
    }
}
