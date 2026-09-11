<?php

namespace App\Support;

use App\Models\Area;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Faq;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\SeoMetadata;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Testimonial;
use App\Models\TrustItem;

final class AdminContent
{
    public static function all(): array
    {
        return [
            'product-categories' => ['label' => 'تصنيفات المنتجات', 'model' => ProductCategory::class, 'title' => 'name', 'columns' => ['name' => 'التصنيف', 'is_active' => 'نشط', 'sort_order' => 'الترتيب'], 'basic_fields' => ['name', 'description', 'is_active'], 'image_meta_fields' => ['featured_image_alt', 'featured_image_caption'], 'fields' => [
                'name' => self::text('اسم التصنيف', true), 'slug' => self::text('الرابط القصير'), 'description' => self::textarea('وصف التصنيف'),
                'featured_image_alt' => self::text('النص البديل للصورة'), 'featured_image_caption' => self::textarea('تعليق الصورة'),
                'sort_order' => self::number('الترتيب'), 'is_active' => array_replace(self::boolean('نشط'), ['default' => true]),
            ], 'image' => true],
            'product-tags' => ['label' => 'وسوم المنتجات', 'model' => ProductTag::class, 'title' => 'name', 'columns' => ['name' => 'الوسم', 'slug' => 'الرابط الداخلي', 'is_active' => 'نشط'], 'fields' => [
                'name' => self::text('اسم الوسم', true), 'slug' => self::text('الرابط القصير'), 'is_active' => array_replace(self::boolean('نشط'), ['default' => true]),
            ], 'seo' => false],
            'products' => ['label' => 'المنتجات', 'model' => Product::class, 'title' => 'name', 'columns' => ['name' => 'المنتج', 'price' => 'السعر', 'stock_quantity' => 'المخزون', 'status' => 'الحالة'], 'basic_fields' => ['product_category_id', 'name', 'excerpt', 'price', 'stock_quantity', 'status'], 'image_meta_fields' => ['featured_image_alt', 'featured_image_caption'], 'fields' => [
                'product_category_id' => self::select('التصنيف', ProductCategory::class, true), 'name' => self::text('اسم المنتج', true), 'slug' => self::text('الرابط القصير'),
                'sku' => self::text('رمز SKU'), 'gtin' => self::text('GTIN إن وجد'), 'mpn' => self::text('MPN إن وجد'), 'brand' => self::text('العلامة التجارية'),
                'excerpt' => self::textarea('الوصف المختصر'), 'description' => self::textarea('وصف المنتج'),
                'price' => self::decimal('السعر بالريال'), 'compare_at_price' => self::decimal('السعر قبل الخصم إن وجد'), 'currency' => self::options('العملة', ['SAR' => 'ريال سعودي']),
                'stock_quantity' => self::number('كمية المخزون'), 'track_stock' => array_replace(self::boolean('تتبع المخزون'), ['default' => true]), 'allow_backorder' => self::boolean('السماح بالطلب عند نفاد المخزون'),
                'featured_image_alt' => self::text('النص البديل للصورة'), 'featured_image_caption' => self::textarea('تعليق الصورة'),
                'condition' => self::options('حالة المنتج', ['new' => 'جديد']), 'is_featured' => self::boolean('منتج مميز'), 'sort_order' => self::number('الترتيب'),
                'status' => self::options('حالة النشر', ['draft' => 'مسودة', 'published' => 'منشور']), 'published_at' => self::datetime('تاريخ النشر'),
            ], 'image' => true, 'relations' => ['tag_ids' => ['label' => 'وسوم المنتج', 'relation' => 'tags', 'model' => ProductTag::class]]],
            'service-categories' => ['label' => 'تصنيفات الخدمات', 'model' => ServiceCategory::class, 'title' => 'name', 'columns' => ['name' => 'الاسم', 'is_active' => 'نشط'], 'basic_fields' => ['name', 'excerpt', 'is_active'], 'image_meta_fields' => ['featured_image_alt', 'featured_image_caption'], 'fields' => [
                'name' => self::text('الاسم', true), 'slug' => self::text('الرابط العربي'), 'excerpt' => self::textarea('الملخص'), 'description' => self::textarea('الوصف'),
                'featured_image_alt' => self::text('النص البديل للصورة'), 'featured_image_caption' => self::textarea('تعليق الصورة'),
                'sort_order' => self::number('الترتيب'), 'is_active' => self::boolean('نشط'),
            ], 'image' => true],
            'services' => ['label' => 'الخدمات', 'model' => Service::class, 'title' => 'name', 'columns' => ['name' => 'الخدمة', 'status' => 'الحالة', 'is_price_published' => 'السعر منشور'], 'basic_fields' => ['name', 'excerpt', 'service_category_id', 'status'], 'image_meta_fields' => ['featured_image_alt', 'featured_image_caption'], 'fields' => [
                'service_category_id' => self::select('التصنيف', ServiceCategory::class, true), 'name' => self::text('اسم الخدمة', true), 'slug' => self::text('الرابط العربي'),
                'excerpt' => self::textarea('الملخص'), 'content' => self::textarea('تعريف الخدمة'), 'types' => self::textarea('أنواع الخدمة — سطر لكل نوع'),
                'use_cases' => self::textarea('الحالات المناسبة — سطر لكل حالة'), 'materials_details' => self::textarea('الخامات وتفاصيلها — سطر لكل خامة'),
                'advantages' => self::textarea('المميزات — سطر لكل ميزة'), 'disadvantages' => self::textarea('العيوب أو القيود — سطر لكل قيد'),
                'price_factors' => self::textarea('عوامل تحديد السعر — سطر لكل عامل'), 'installation_steps' => self::textarea('خطوات المعاينة والتركيب — سطر لكل خطوة'),
                'selection_tips' => self::textarea('نصائح الاختيار — سطر لكل نصيحة'), 'cta' => self::textarea('دعوة التواصل CTA'),
                'featured_image_alt' => self::text('النص البديل للصورة'), 'featured_image_caption' => self::textarea('تعليق الصورة'),
                'video_url' => self::url('رابط الفيديو'), 'video_title' => self::text('عنوان الفيديو'), 'video_thumbnail' => self::text('رابط صورة الفيديو'), 'video_duration_seconds' => self::number('مدة الفيديو بالثواني'),
                'price_from' => self::decimal('السعر من'), 'price_to' => self::decimal('السعر إلى'),
                'price_unit' => self::text('وحدة السعر'), 'price_note' => self::textarea('ملاحظة السعر'), 'is_price_published' => self::boolean('نشر السعر'),
                'is_featured' => self::boolean('مميزة في الرئيسية'), 'is_popular' => self::boolean('معتمدة ضمن الأكثر طلبًا'), 'sort_order' => self::number('الترتيب'),
                'is_active' => self::boolean('متاحة للإدارة'), 'status' => self::options('حالة النشر', ['draft' => 'مسودة', 'published' => 'منشورة']), 'published_at' => self::datetime('تاريخ النشر'),
            ], 'image' => true, 'relations' => ['material_ids' => ['label' => 'الخامات', 'relation' => 'materials', 'model' => Material::class]]],
            'materials' => ['label' => 'الخامات والأسعار', 'model' => Material::class, 'title' => 'name', 'columns' => ['name' => 'الخامة', 'is_price_published' => 'السعر منشور', 'is_active' => 'نشطة'], 'basic_fields' => ['name', 'excerpt', 'is_active'], 'image_meta_fields' => ['featured_image_alt', 'featured_image_caption'], 'fields' => [
                'name' => self::text('الاسم', true), 'slug' => self::text('الرابط العربي'), 'excerpt' => self::textarea('الملخص'), 'description' => self::textarea('الوصف'),
                'featured_image_alt' => self::text('النص البديل للصورة'), 'featured_image_caption' => self::textarea('تعليق الصورة'),
                'price_from' => self::decimal('السعر من'), 'price_to' => self::decimal('السعر إلى'), 'price_unit' => self::text('وحدة السعر'),
                'price_note' => self::textarea('ملاحظة السعر'), 'is_price_published' => self::boolean('نشر السعر'), 'is_active' => self::boolean('نشطة'),
            ], 'image' => true, 'relations' => ['service_ids' => ['label' => 'الخدمات المرتبطة', 'relation' => 'services', 'model' => Service::class]]],
            'areas' => ['label' => 'مناطق الخدمة', 'model' => Area::class, 'title' => 'name', 'columns' => ['name' => 'المنطقة', 'status' => 'الحالة', 'is_primary' => 'الموقع الأساسي'], 'basic_fields' => ['name', 'excerpt', 'status'], 'image_meta_fields' => ['featured_image_alt', 'featured_image_caption'], 'fields' => [
                'name' => self::text('الاسم', true), 'slug' => self::text('الرابط العربي'), 'excerpt' => self::textarea('الملخص'), 'content' => self::textarea('المحتوى'),
                'featured_image_alt' => self::text('النص البديل للصورة'), 'featured_image_caption' => self::textarea('تعليق الصورة'),
                'is_active' => self::boolean('متاحة للإدارة'), 'is_primary' => self::boolean('الموقع الأساسي للنشاط'),
                'status' => self::options('حالة الصفحة', ['draft' => 'مسودة', 'published' => 'منشورة']), 'published_at' => self::datetime('تاريخ النشر'),
            ], 'image' => true, 'relations' => ['faq_ids' => ['label' => 'الأسئلة المرتبطة', 'relation' => 'faqs', 'model' => Faq::class, 'title' => 'question']]],
            'projects' => ['label' => 'المشاريع الحقيقية', 'model' => Project::class, 'title' => 'title', 'columns' => ['title' => 'المشروع', 'status' => 'الحالة', 'published_at' => 'تاريخ النشر'], 'basic_fields' => ['title', 'excerpt', 'service_id', 'area_id', 'status'], 'fields' => [
                'service_id' => self::select('الخدمة', Service::class, true), 'area_id' => self::select('المنطقة', Area::class, true), 'title' => self::text('عنوان المشروع', true),
                'slug' => self::text('الرابط العربي'), 'excerpt' => self::textarea('الملخص'), 'description' => self::textarea('تفاصيل موثقة'),
                'video_url' => self::url('رابط الفيديو'), 'video_title' => self::text('عنوان الفيديو'), 'video_thumbnail' => self::text('رابط صورة الفيديو'), 'video_duration_seconds' => self::number('مدة الفيديو بالثواني'),
                'completed_at' => self::date('تاريخ الإنجاز'), 'is_featured' => self::boolean('مميز'),
                'status' => self::options('حالة النشر', ['draft' => 'مسودة', 'published' => 'منشور']), 'published_at' => self::datetime('تاريخ النشر'),
            ], 'gallery' => true],
            'article-categories' => ['label' => 'تصنيفات المقالات', 'model' => ArticleCategory::class, 'title' => 'name', 'columns' => ['name' => 'التصنيف', 'is_active' => 'نشط'], 'fields' => [
                'name' => self::text('الاسم', true), 'slug' => self::text('الرابط العربي'), 'description' => self::textarea('الوصف'), 'is_active' => self::boolean('نشط'),
            ]],
            'articles' => ['label' => 'مقالات الدليل', 'model' => Article::class, 'title' => 'title', 'columns' => ['title' => 'المقال', 'status' => 'الحالة', 'published_at' => 'النشر'], 'basic_fields' => ['title', 'excerpt', 'article_category_id', 'status'], 'image_meta_fields' => ['featured_image_alt', 'featured_image_caption'], 'fields' => [
                'article_category_id' => self::select('التصنيف', ArticleCategory::class, true), 'title' => self::text('العنوان', true), 'slug' => self::text('الرابط العربي'),
                'excerpt' => self::textarea('الملخص'), 'body' => self::textarea('المحتوى'), 'status' => self::options('الحالة', ['draft' => 'مسودة', 'published' => 'منشور']),
                'featured_image_alt' => self::text('النص البديل للصورة'), 'featured_image_caption' => self::textarea('تعليق الصورة'),
                'video_url' => self::url('رابط الفيديو'), 'video_title' => self::text('عنوان الفيديو'), 'video_thumbnail' => self::text('رابط صورة الفيديو'), 'video_duration_seconds' => self::number('مدة الفيديو بالثواني'),
                'published_at' => self::datetime('تاريخ النشر'),
            ], 'image' => true, 'relations' => [
                'service_ids' => ['label' => 'الخدمات المرتبطة', 'relation' => 'services', 'model' => Service::class],
                'related_article_ids' => ['label' => 'مقالات مرتبطة', 'relation' => 'relatedArticles', 'model' => Article::class, 'title' => 'title'],
                'faq_ids' => ['label' => 'الأسئلة المرتبطة', 'relation' => 'faqs', 'model' => Faq::class, 'title' => 'question'],
            ]],
            'faqs' => ['label' => 'الأسئلة الشائعة', 'model' => Faq::class, 'title' => 'question', 'columns' => ['question' => 'السؤال', 'is_active' => 'نشط'], 'fields' => [
                'question' => self::textarea('السؤال', true), 'answer' => self::textarea('الإجابة', true), 'sort_order' => self::number('الترتيب'), 'is_active' => self::boolean('نشط'),
            ]],
            'testimonials' => ['label' => 'آراء العملاء الموثقة', 'model' => Testimonial::class, 'title' => 'customer_name', 'columns' => ['customer_name' => 'الاسم', 'rating' => 'التقييم', 'is_approved' => 'معتمد'], 'fields' => [
                'area_id' => self::select('المنطقة', Area::class), 'project_id' => self::select('المشروع', Project::class), 'customer_name' => self::text('اسم العميل', true),
                'quote' => self::textarea('النص', true), 'rating' => ['label' => 'التقييم', 'type' => 'number', 'rules' => ['nullable', 'integer', 'between:1,5']], 'is_approved' => self::boolean('اعتماد للنشر'),
            ]],
            'trust-items' => ['label' => 'شريط الثقة', 'model' => TrustItem::class, 'title' => 'label', 'columns' => ['label' => 'العنصر', 'value' => 'القيمة', 'is_active' => 'منشور'], 'fields' => [
                'label' => self::text('العنوان', true), 'value' => self::text('القيمة الحقيقية'), 'description' => self::textarea('وصف مختصر'),
                'sort_order' => self::number('الترتيب'), 'is_active' => self::boolean('نشر في شريط الثقة'),
            ], 'seo' => false],
            'redirects' => ['label' => 'التحويلات', 'model' => Redirect::class, 'title' => 'old_path', 'columns' => ['old_path' => 'المسار القديم', 'new_path' => 'المسار الجديد', 'hits' => 'الزيارات', 'is_active' => 'نشط'], 'fields' => [
                'old_path' => self::text('المسار القديم', true), 'new_path' => self::text('المسار الجديد', true),
                'status_code' => ['label' => 'كود التحويل', 'type' => 'select-options', 'rules' => ['required', 'integer', 'in:301,302'], 'options' => [301 => '301 دائم', 302 => '302 مؤقت']],
                'is_active' => self::boolean('نشط'),
            ], 'seo' => false],
            'static-seo' => ['label' => 'SEO للصفحات الثابتة', 'model' => SeoMetadata::class, 'title' => 'route_name', 'columns' => ['route_name' => 'الصفحة', 'meta_title' => 'العنوان', 'robots' => 'Robots'], 'fields' => [
                'route_name' => ['label' => 'الصفحة', 'type' => 'select-options', 'rules' => ['required', 'string', 'max:100'], 'options' => [
                    'home' => 'الرئيسية', 'about' => 'من نحن', 'services.index' => 'الخدمات', 'projects.index' => 'المشاريع',
                    'areas.index' => 'المناطق', 'guide.index' => 'المقالات', 'prices' => 'الباقات والأسعار', 'quote' => 'احجز الآن',
                    'products.index' => 'المنتجات', 'cart.show' => 'سلة المشتريات',
                    'contact' => 'تواصل معنا', 'privacy' => 'سياسة الخصوصية', 'terms' => 'الشروط والأحكام',
                ]],
                'meta_title' => self::text('عنوان محرك البحث', true), 'meta_description' => self::textarea('الوصف التعريفي', true),
                'canonical_url' => self::text('Canonical مخصص'), 'robots' => self::text('تعليمات Robots'), 'og_title' => self::text('عنوان المشاركة'),
                'og_description' => self::textarea('وصف المشاركة'), 'og_image' => self::text('مسار صورة المشاركة'), 'schema_type' => self::text('نوع Schema إضافي'),
            ], 'seo' => false],
        ];
    }

    public static function get(string $type): array
    {
        return self::all()[$type] ?? abort(404);
    }

    private static function text(string $label, bool $required = false): array
    {
        return ['label' => $label, 'type' => 'text', 'rules' => [$required ? 'required' : 'nullable', 'string', 'max:255']];
    }

    private static function textarea(string $label, bool $required = false): array
    {
        return ['label' => $label, 'type' => 'textarea', 'rules' => [$required ? 'required' : 'nullable', 'string', 'max:30000']];
    }

    private static function boolean(string $label): array
    {
        return ['label' => $label, 'type' => 'checkbox', 'rules' => ['boolean'], 'default' => false];
    }

    private static function number(string $label): array
    {
        return ['label' => $label, 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0'], 'default' => 0];
    }

    private static function decimal(string $label): array
    {
        return ['label' => $label, 'type' => 'number', 'step' => '0.01', 'rules' => ['nullable', 'numeric', 'min:0']];
    }

    private static function url(string $label): array
    {
        return ['label' => $label, 'type' => 'url', 'rules' => ['nullable', 'url', 'max:2048']];
    }

    private static function date(string $label): array
    {
        return ['label' => $label, 'type' => 'date', 'rules' => ['nullable', 'date']];
    }

    private static function datetime(string $label): array
    {
        return ['label' => $label, 'type' => 'datetime-local', 'rules' => ['nullable', 'date']];
    }

    private static function select(string $label, string $model, bool $required = false): array
    {
        return ['label' => $label, 'type' => 'select', 'model' => $model, 'rules' => [$required ? 'required' : 'nullable', 'integer', 'exists:'.(new $model)->getTable().',id']];
    }

    private static function options(string $label, array $options): array
    {
        return ['label' => $label, 'type' => 'select-options', 'options' => $options, 'rules' => ['required', 'string', 'in:'.implode(',', array_keys($options))], 'default' => array_key_first($options)];
    }
}
