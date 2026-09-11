<?php

use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->foreignId('parent_service_id')->nullable()->after('service_category_id')->constrained('services')->nullOnDelete();
            $table->string('image_source_folder')->nullable()->after('parent_service_id')->index();
        });

        $this->ensureWinterService();
        $this->publishAndLinkServices();
        $this->refreshCovers();
        $this->refreshSettings();
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_service_id');
            $table->dropColumn('image_source_folder');
        });
    }

    private function ensureWinterService(): void
    {
        if (DB::table('services')->where('name', 'الجلسات الشتوية')->exists()) {
            return;
        }

        $source = DB::table('services')->where('name', 'جلسات زجاجية')->first();
        if (! $source) {
            return;
        }

        $record = (array) $source;
        unset($record['id']);
        $record['name'] = 'الجلسات الشتوية';
        $record['slug'] = 'الجلسات-الشتوية';
        $record['excerpt'] = 'جلسات شتوية زجاجية مريحة تحافظ على وضوح الإطلالة وتقلل أثر الرياح والغبار، مع دراسة التهوية والتظليل والتصريف.';
        $record['featured_image'] = 'services/geometric-tensile-shade-riyadh.webp';
        $record['featured_image_alt'] = 'جلسة شتوية زجاجية في الرياض';
        $record['featured_image_caption'] = 'تصميم جلسة شتوية زجاجية للاستخدام المريح في الأجواء الباردة.';
        $record['status'] = 'published';
        $record['is_active'] = true;
        $record['is_featured'] = true;
        $record['is_popular'] = true;
        $record['published_at'] = $record['published_at'] ?: now();
        $record['updated_at'] = now();
        $winterId = DB::table('services')->insertGetId($record);
        foreach (DB::table('material_service')->where('service_id', $source->id)->pluck('material_id') as $materialId) {
            DB::table('material_service')->insertOrIgnore(['material_id' => $materialId, 'service_id' => $winterId]);
        }
        foreach (DB::table('faqables')->where('faqable_id', $source->id)->where('faqable_type', Service::class)->pluck('faq_id') as $faqId) {
            DB::table('faqables')->insertOrIgnore([
                'faq_id' => $faqId, 'faqable_id' => $winterId, 'faqable_type' => Service::class,
            ]);
        }
    }

    private function publishAndLinkServices(): void
    {
        $categoryIds = DB::table('service_categories')->pluck('id', 'name');
        $electronicCategory = $categoryIds['الشترات والنوافذ والأبواب'] ?? null;
        if ($electronicCategory) {
            DB::table('services')->where('name', 'الشترات والأبواب الإلكترونية')->update([
                'service_category_id' => $electronicCategory,
            ]);
        }
        $electronicSlug = DB::table('services')->where('name', 'الشترات والأبواب الإلكترونية')->value('slug');
        if ($electronicSlug) {
            DB::table('redirects')->where('old_path', '/الخدمات/'.$electronicSlug)->update(['is_active' => false]);
        }

        DB::table('services')->whereIn('name', config('site.launch_services', []))->update([
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('services')->update(['parent_service_id' => null]);
        $hierarchy = [
            'الخيام' => ['بيوت الشعر'],
            'الجلسات الشتوية' => ['جلسات زجاجية'],
            'الشترات والأبواب الإلكترونية' => ['الشترات', 'النوافذ', 'الأبواب الكهربائية'],
        ];
        foreach ($hierarchy as $parentName => $children) {
            $parentId = DB::table('services')->where('name', $parentName)->value('id');
            if ($parentId) {
                DB::table('services')->whereIn('name', $children)->update(['parent_service_id' => $parentId]);
            }
        }

        $sources = [
            'مظلات PVC' => 'madtaltpfc',
            'مظلات الشد الإنشائي' => 'matalatalshdalnshai',
            'الجلسات الشتوية' => 'sandawitshpanel',
            'الشترات والأبواب الإلكترونية' => 'ElectronicShuttersDoors',
            'البرجولات' => 'bargolat',
            'بيوت الشعر' => 'bytalshar',
            'الهناجر' => 'hangers',
        ];
        DB::table('services')->update(['image_source_folder' => null]);
        foreach ($sources as $service => $folder) {
            DB::table('services')->where('name', $service)->update(['image_source_folder' => $folder]);
        }
    }

    private function refreshCovers(): void
    {
        foreach (config('site.service_featured_images', []) as $name => $path) {
            DB::table('services')->where('name', $name)->update([
                'featured_image' => $path,
                'featured_image_alt' => $name.' في الرياض',
                'updated_at' => now(),
            ]);
        }

        $services = DB::table('services')->whereIn('name', config('site.launch_services', []))->get();
        foreach ($services as $service) {
            if (config('site.service_featured_images.'.$service->name)) {
                continue;
            }

            $imagePath = DB::table('service_images')
                ->where('service_id', $service->id)
                ->whereNull('deleted_at')
                ->where('processing_status', 'processed')
                ->whereNotNull('optimized_path')
                ->orderByDesc('is_cover')
                ->orderBy('sort_order')
                ->value('optimized_path');
            if ($imagePath) {
                DB::table('services')->where('id', $service->id)->update(['featured_image' => $imagePath]);

                continue;
            }

            $childPath = DB::table('service_images')
                ->join('services', 'services.id', '=', 'service_images.service_id')
                ->where('services.parent_service_id', $service->id)
                ->whereNull('service_images.deleted_at')
                ->where('service_images.processing_status', 'processed')
                ->whereNotNull('service_images.optimized_path')
                ->orderByDesc('service_images.is_cover')
                ->orderBy('service_images.sort_order')
                ->value('service_images.optimized_path');
            if ($childPath) {
                DB::table('services')->where('id', $service->id)->update(['featured_image' => $childPath]);
            }
        }

        $children = DB::table('services')->whereNotNull('parent_service_id')->get();
        foreach ($children as $child) {
            if (! $child->featured_image) {
                $parentCover = DB::table('services')->where('id', $child->parent_service_id)->value('featured_image');
                if ($parentCover) {
                    DB::table('services')->where('id', $child->id)->update(['featured_image' => $parentCover]);
                }
            }
        }

        foreach (config('site.article_featured_images', []) as $title => $path) {
            DB::table('articles')->where('title', $title)->update(['featured_image' => $path, 'updated_at' => now()]);
        }
    }

    private function refreshSettings(): void
    {
        $settings = [
            'theme_primary' => ['#7A1F35', 'اللون الأساسي', 'appearance'],
            'theme_secondary' => ['#B88352', 'اللون الثانوي', 'appearance'],
            'theme_background' => ['#FFFFFF', 'خلفية الموقع', 'appearance'],
            'theme_text' => ['#2E2024', 'لون النص', 'appearance'],
            'theme_base_100' => ['#FFFFFF', 'خلفية البطاقات', 'appearance'],
            'theme_border' => ['#E9DDE0', 'الحدود والخطوط', 'appearance'],
            'theme_muted' => ['#746166', 'النص الثانوي', 'appearance'],
            'header_image' => [config('site.header_image'), 'صورة الهيدر بجوار اسم الموقع', 'business'],
        ];

        foreach ($settings as $key => [$value, $label, $group]) {
            DB::table('settings')->updateOrInsert(['key' => $key], [
                'value' => $value,
                'type' => 'string',
                'group' => $group,
                'label' => $label,
                'is_public' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }
};
