<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class MediaCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['name' => 'فلاتر جامبو ومركزية', 'slug' => 'فلاتر-جامبو', 'description' => 'فلاتر جامبو وأنظمة التنقية الأولية للخزانات والخطوط الرئيسية.'],
            ['name' => 'أجهزة التحلية المنزلية', 'slug' => 'اجهزه-التحليه', 'description' => 'أجهزة تناضح عكسي منزلية متعددة المراحل وملحقاتها.'],
            ['name' => 'فلاتر الشاور والملحقات', 'slug' => 'فلاتر-وملحقات', 'description' => 'فلاتر شاور وخزانات وملحقات مرتبطة بأنظمة تنقية المياه.'],
        ])->mapWithKeys(function (array $data, int $index): array {
            $category = ProductCategory::query()->updateOrCreate(['slug' => $data['slug']], $data + ['sort_order' => $index + 1, 'is_active' => true]);

            return [$data['name'] => $category];
        });

        $tags = collect(['جامبو 3 مراحل', 'تناضح عكسي', '7 مراحل', '8 مراحل', 'فلتر شاور', 'خزان مياه', 'مع عداد ضغط', 'صنع في فيتنام', 'صنع في تايوان'])
            ->mapWithKeys(fn (string $name): array => [$name => ProductTag::query()->updateOrCreate(['name' => $name], ['is_active' => true])]);

        $assets = collect();
        foreach ($this->catalog() as $index => $item) {
            $source = base_path($item['source']);
            if (! is_file($source)) {
                $this->command?->warn('تعذر العثور على صورة الكتالوج: '.$item['source']);

                continue;
            }

            $disk = Storage::disk('public');
            $hash = hash_file('sha256', $source);
            if (! $disk->exists($item['path']) || hash('sha256', (string) $disk->get($item['path'])) !== $hash) {
                $disk->put($item['path'], (string) file_get_contents($source));
            }
            [$width, $height] = getimagesize($source);
            $variants = ['webp' => collect(['thumbnail', 'gallery', 'cover_mobile', 'cover_desktop'])->map(fn (string $role): array => [
                'role' => $role, 'width' => $width, 'height' => $height, 'path' => $item['path'],
            ])->all()];

            $media = MediaAsset::query()->firstOrNew(['source_key' => $item['key']]);
            if (! $media->exists) {
                $media->fill([
                    'name' => $item['name'],
                    'alt_text' => $item['alt'],
                    'caption' => $item['caption'],
                    'usage_notes' => $item['usage_notes'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]);
            }
            $media->fill([
                'path' => $item['path'],
                'mime_type' => 'image/webp',
                'width' => $width,
                'height' => $height,
                'file_size' => filesize($source),
                'content_hash' => $hash,
                'variants' => $variants,
            ])->save();
            $assets->put($item['key'], $media);

            $category = $categories->get($item['category']);
            $product = Product::query()->where('catalog_source_key', $item['key'])->first()
                ?: Product::query()->whereNull('catalog_source_key')->where('media_asset_id', $media->id)->first()
                ?: new Product;
            $isNewProduct = ! $product->exists;
            if ($isNewProduct) {
                $product->fill([
                    'product_category_id' => $category->id,
                    'media_asset_id' => $media->id,
                    'name' => $item['name'],
                    'slug' => $item['slug'],
                    'brand' => $item['brand'],
                    'excerpt' => $item['excerpt'],
                    'description' => $item['description'],
                    'price' => null,
                    'compare_at_price' => null,
                    'currency' => 'SAR',
                    'stock_quantity' => 0,
                    'track_stock' => false,
                    'allow_backorder' => false,
                    'featured_image' => $media->path,
                    'featured_image_alt' => $media->alt_text,
                    'featured_image_caption' => $media->caption,
                    'condition' => 'new',
                    'status' => 'published',
                    'is_featured' => $index < 8,
                    'sort_order' => $index + 1,
                    'published_at' => now(),
                ]);
            }
            $product->catalog_source_key = $item['key'];
            $product->save();
            if ($isNewProduct) {
                $product->tags()->sync(collect($item['tags'])->map(fn (string $name) => $tags->get($name)?->id)->filter()->values());
                $product->seo()->create([
                    'meta_title' => $item['name'].' | فلاتر المياه بالرياض',
                    'meta_description' => $item['excerpt'].' تواصل لمعرفة السعر والتوفر وخيارات التركيب في الرياض.',
                    'focus_keyword' => $item['name'],
                    'robots' => 'index,follow,max-image-preview:large',
                    'og_title' => $item['name'],
                    'og_description' => $item['excerpt'],
                    'og_image' => $media->path,
                    'schema_type' => 'Product',
                ]);
            }

            if (! $category->featured_image) {
                $category->update([
                    'featured_image' => $media->path,
                    'featured_image_alt' => $media->alt_text,
                    'featured_image_caption' => $media->caption,
                ]);
            }
        }

        $this->syncServices($assets);
        $this->syncArticles($assets);
    }

    private function syncServices($assets): void
    {
        $maps = [
            'تركيب فلاتر المياه بالرياض' => ['mpure-white', 'aqua-plus', 'shower', 'mpure-blue', 'aqua-commercial', 'mpure-installed', 'enpure-jumbo', 'angel-complete', 'helsy', 'aquapure-black'],
            'تغيير شمعات فلاتر المياه بالرياض' => ['aqua-plus', 'aqua-plus-gauge', 'aqua-kit', 'angel-complete', 'helsy', 'aqua-taiwan-kit', 'pureena', 'super-pro-8', 'ivlife', 'bwater'],
            'تركيب فلتر جامبو للخزان بالرياض' => ['mpure-white', 'mpure-blue', 'mpure-installed', 'enpure-jumbo', 'aquapure-black', 'aqua-commercial', 'angel-complete', 'ivlife', 'aqua-taiwan-kit', 'pressure-tank'],
            'تركيب فلتر شاور بالرياض' => ['shower', 'shower-angle', 'mpure-white', 'mpure-blue', 'mpure-installed', 'enpure-jumbo', 'aquapure-black', 'aqua-commercial', 'ivlife', 'pressure-tank'],
            'تركيب أجهزة تحلية المياه المنزلية بالرياض' => ['aqua-plus', 'aqua-plus-gauge', 'aqua-kit', 'angel-complete', 'helsy', 'aqua-taiwan-kit', 'pureena', 'super-pro-8', 'ivlife', 'bwater'],
            'صيانة أجهزة تحلية المياه بالرياض' => ['aqua-plus-gauge', 'pressure-tank', 'aqua-kit', 'angel-complete', 'helsy', 'aqua-taiwan-kit', 'pureena', 'super-pro-8', 'ivlife', 'bwater'],
            'تركيب محطات تحلية المياه بالرياض' => ['aqua-commercial', 'mpure-white', 'mpure-blue', 'mpure-installed', 'enpure-jumbo', 'aquapure-black', 'angel-complete', 'aqua-kit', 'ivlife', 'pressure-tank'],
            'صيانة محطات تحلية المياه بالرياض' => ['aqua-commercial', 'mpure-installed', 'enpure-jumbo', 'aquapure-black', 'pressure-tank', 'aqua-kit', 'angel-complete', 'super-pro-8', 'ivlife', 'bwater'],
            'صيانة فلاتر المياه بالرياض' => ['mpure-installed', 'pressure-tank', 'aqua-plus-gauge', 'aqua-kit', 'angel-complete', 'helsy', 'pureena', 'super-pro-8', 'ivlife', 'bwater'],
            // لا توجد ضمن الملفات صورة فعلية للرذاذ؛ هذه صور أنظمة المعالجة الأولية التي تحمي المضخة والفوهات من الرواسب.
            'تركيب وصيانة أنظمة الضباب والرذاذ بالرياض' => ['mpure-white', 'mpure-blue', 'mpure-installed', 'enpure-jumbo', 'aquapure-black', 'aqua-commercial', 'ivlife', 'pressure-tank', 'angel-complete', 'aqua-kit'],
        ];

        foreach ($maps as $serviceName => $keys) {
            $service = Service::query()->where('name', $serviceName)->first();
            if (! $service) {
                continue;
            }
            $context = str_contains($serviceName, 'الضباب') ? 'فلترة أولية مساندة لنظام الرذاذ' : 'منتجات وتجهيزات مرتبطة بالخدمة';
            $service->mediaAssets()->sync(collect($keys)->mapWithKeys(fn (string $key, int $index): array => [
                $assets->get($key)?->id => ['context' => $context, 'sort_order' => $index + 1],
            ])->filter(fn ($pivot, $id) => filled($id))->all());
        }
    }

    private function syncArticles($assets): void
    {
        $maps = [
            'أفضل فلتر مياه منزلي في الرياض: كيف تختار الجهاز المناسب؟' => ['aqua-plus', 'helsy', 'pureena', 'super-pro-8', 'bwater'],
            'أسعار فلاتر المياه المنزلية مع التركيب في الرياض' => ['mpure-white', 'shower', 'aqua-plus-gauge', 'enpure-jumbo', 'aqua-kit'],
            'فلتر مياه 7 مراحل: المميزات والمراحل والسعر في الرياض' => ['aqua-plus', 'aqua-plus-gauge', 'helsy', 'pureena', 'bwater'],
            'فلتر جامبو 3 مراحل للخزان: المميزات والسعر والتركيب بالرياض' => ['mpure-white', 'mpure-blue', 'mpure-installed', 'enpure-jumbo', 'aquapure-black'],
            'متى يجب تغيير شمعات فلتر المياه؟ دليل الصيانة في الرياض' => ['mpure-installed', 'pressure-tank', 'aqua-kit', 'ivlife', 'super-pro-8'],
            'الفرق بين فلتر تنقية المياه وجهاز تحلية المياه المنزلي' => ['mpure-white', 'shower-angle', 'aqua-plus', 'pressure-tank', 'aqua-commercial'],
            'أفضل محطة تحلية مياه للمطاعم والكافيهات في الرياض' => ['aqua-commercial', 'enpure-jumbo', 'aquapure-black', 'ivlife', 'angel-complete'],
            'محطة تحلية 400 جالون: المواصفات والاستخدام والسعر بالرياض' => ['aqua-commercial', 'aquapure-black', 'ivlife', 'enpure-jumbo', 'mpure-blue'],
            'أسباب ضعف ضغط الماء في فلتر التحلية وطريقة صيانته' => ['pressure-tank', 'ivlife', 'aqua-kit', 'angel-complete', 'super-pro-8'],
            'أفضل شركة تركيب وصيانة فلاتر مياه بالرياض' => ['mpure-installed', 'shower', 'aqua-plus', 'aqua-commercial', 'bwater'],
        ];

        foreach ($maps as $title => $keys) {
            $article = Article::query()->where('title', $title)->first();
            if (! $article) {
                continue;
            }
            $ids = collect($keys)->map(fn (string $key) => $assets->get($key)?->id)->filter()->values();
            $article->mediaAssets()->sync($ids->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort_order' => $index + 1]])->all());
            if ($first = $assets->first(fn (MediaAsset $asset) => $asset->id === $ids->first())) {
                $article->update([
                    'featured_image' => $first->path,
                    'featured_image_alt' => $first->alt_text,
                    'featured_image_caption' => $first->caption,
                ]);
            }
        }
    }

    private function catalog(): array
    {
        $home = 'أجهزة التحلية المنزلية';
        $jumbo = 'فلاتر جامبو ومركزية';
        $parts = 'فلاتر الشاور والملحقات';
        $description = fn (string $name): string => $name.' كما يظهر في الصورة الأصلية. بيانات السعر والمخزون والمواصفات التفصيلية متروكة للإدارة حتى اعتمادها، ويمكن تعديلها بالكامل من لوحة التحكم.';

        return [
            $this->item('mpure-white', 'assets/mist-and-fog-system-installation-riyadh.webp', 'services/mist-and-fog-system-installation-riyadh.webp', 'فلتر جامبو M-PURE ثلاث مراحل بقاعدة بيضاء', 'فلتر جامبو M-PURE ثلاث مراحل مع عدادي ضغط بقاعدة بيضاء', 'mpure-jumbo-white', 'M-PURE', $jumbo, ['جامبو 3 مراحل', 'مع عداد ضغط'], $description),
            $this->item('aqua-plus', 'assets/seven-stage-home-water-purifier.webp', 'services/seven-stage-home-water-purifier.webp', 'جهاز تحلية Aqua Plus سبع مراحل', 'جهاز تحلية مياه منزلي Aqua Plus سبع مراحل بتقنية التناضح العكسي', 'aqua-plus-7-stage', 'Aqua Plus', $home, ['تناضح عكسي', '7 مراحل', 'صنع في فيتنام'], $description),
            $this->item('aqua-plus-gauge', 'assets/seven-stage-home-water-purifier2.webp', 'services/seven-stage-home-water-purifier2.webp', 'جهاز تحلية Aqua Plus سبع مراحل مع عداد ضغط', 'جهاز تحلية Aqua Plus سبع مراحل مزود بعداد ضغط', 'aqua-plus-gauge', 'Aqua Plus', $home, ['تناضح عكسي', '7 مراحل', 'مع عداد ضغط'], $description),
            $this->item('shower', 'assets/shower-water-filter.webp', 'services/shower-water-filter.webp', 'فلتر شاور كروم قابل للاستبدال', 'فلتر مياه للشاور بتصميم كروم وعلبة المنتج', 'shower-filter', null, $parts, ['فلتر شاور'], $description),
            $this->item('shower-angle', 'assets/shower-water-filter2.webp', 'services/shower-water-filter2.webp', 'فلتر شاور كروم مدمج', 'فلتر شاور كروم مدمج لتنقية مياه الدش', 'shower-filter-chrome', null, $parts, ['فلتر شاور'], $description),
            $this->item('mpure-blue', 'assets/three-stage-jumbo-water-filter.webp', 'services/three-stage-jumbo-water-filter.webp', 'فلتر جامبو M-PURE ثلاث مراحل مع عدادات', 'فلتر جامبو M-PURE ثلاث مراحل بهوزنج أزرق وعدادات ضغط', 'mpure-jumbo-blue', 'M-PURE', $jumbo, ['جامبو 3 مراحل', 'مع عداد ضغط'], $description),
            $this->item('pressure-tank', 'assets/water-filter-faucet.webp', 'services/water-filter-faucet.webp', 'خزان ضغط شفاف لجهاز تحلية المياه', 'خزان ضغط وتخزين شفاف أزرق وأبيض لجهاز تحلية المياه', 'water-pressure-tank', null, $parts, ['خزان مياه'], $description),
            $this->item('aqua-commercial', 'assets/water-filter-installation-riyadh.webp', 'services/water-filter-installation-riyadh.webp', 'محطة تحلية Aqua بهوزنج كبير', 'محطة تحلية Aqua مركبة على قاعدة مع ثلاث مراحل أولية كبيرة', 'aqua-water-station', 'Aqua', $home, ['تناضح عكسي'], $description),
            $this->item('mpure-installed', 'assets/water-filter-replacement-riyadh.webp', 'services/water-filter-replacement-riyadh.webp', 'نظام فلتر جامبو M-PURE موصل للخزان', 'فلتر جامبو M-PURE ثلاث مراحل موصل بالمضخة وخط الخزان', 'mpure-jumbo-installed', 'M-PURE', $jumbo, ['جامبو 3 مراحل', 'مع عداد ضغط'], $description),
            $this->item('aqua-kit', 'assets/water-treatment-station-maintenance-riyadh.webp', 'services/water-treatment-station-maintenance-riyadh.webp', 'طقم جهاز تحلية Aqua مع خزان وملحقات', 'جهاز تحلية Aqua كامل مع خزان ضغط وملحقات التركيب', 'aqua-complete-kit', 'Aqua', $home, ['تناضح عكسي', 'خزان مياه'], $description),
            $this->item('enpure-jumbo', 'assets/newimages/1.webp', 'services/catalog/enpure-jumbo-three-stage.webp', 'فلتر جامبو EnPure ثلاث مراحل', 'فلتر جامبو EnPure ثلاث مراحل بهوزنج أزرق وعدادات ضغط', 'enpure-jumbo', 'EnPure', $jumbo, ['جامبو 3 مراحل', 'مع عداد ضغط'], $description),
            $this->item('angel-complete', 'assets/newimages/2.webp', 'services/catalog/angel-aqua-complete-system.webp', 'جهاز تحلية Angel Aqua مع خزان وفلتر علوي', 'جهاز تحلية Angel Aqua متكامل مع خزان وصنبور وفلتر علوي', 'angel-aqua-system', 'Angel Aqua', $home, ['تناضح عكسي', 'خزان مياه', 'مع عداد ضغط'], $description),
            $this->item('helsy', 'assets/newimages/3.webp', 'services/catalog/helsy-seven-stage.webp', 'جهاز تحلية Helsy سبع مراحل فيتنامي', 'جهاز تحلية Helsy سبع مراحل بتقنية التناضح العكسي صنع في فيتنام', 'helsy-7-stage', 'Helsy', $home, ['تناضح عكسي', '7 مراحل', 'صنع في فيتنام', 'مع عداد ضغط'], $description),
            $this->item('aquapure-black', 'assets/newimages/4.webp', 'services/catalog/aquapure-jumbo-black-frame.webp', 'فلتر جامبو AquaPure ثلاث مراحل بهيكل أسود', 'فلتر جامبو AquaPure ثلاث مراحل بهيكل أسود وعدادات ضغط', 'aquapure-jumbo', 'AquaPure', $jumbo, ['جامبو 3 مراحل', 'مع عداد ضغط', 'صنع في فيتنام'], $description),
            $this->item('aqua-taiwan-kit', 'assets/newimages/5.webp', 'services/catalog/aqua-taiwan-ro-kit.webp', 'جهاز تحلية Aqua تايواني مع طقم تركيب', 'جهاز تحلية Aqua بتقنية التناضح العكسي مع خزان وصنبور وطقم تركيب', 'aqua-taiwan-kit', 'Aqua', $home, ['تناضح عكسي', 'خزان مياه', 'صنع في تايوان'], $description),
            $this->item('pureena', 'assets/newimages/6.webp', 'services/catalog/pureena-seven-stage.webp', 'جهاز تحلية Pureena سبع مراحل فيتنامي', 'جهاز تحلية Pureena سبع مراحل مع خزان وصنبور صنع في فيتنام', 'pureena-7-stage', 'Pureena', $home, ['تناضح عكسي', '7 مراحل', 'خزان مياه', 'صنع في فيتنام'], $description),
            $this->item('super-pro-8', 'assets/newimages/7.webp', 'services/catalog/super-pro-eight-stage.webp', 'جهاز تحلية Super Pro ثماني مراحل تايواني', 'جهاز تحلية Super Pro ثماني مراحل بتقنية التناضح العكسي صنع في تايوان', 'super-pro-8-stage', 'Super Pro', $home, ['تناضح عكسي', '8 مراحل', 'صنع في تايوان'], $description),
            $this->item('ivlife', 'assets/newimages/8.webp', 'services/catalog/ivlife-auto-flush-system.webp', 'جهاز تحلية IVlife بنظام غسيل تلقائي', 'جهاز تحلية IVlife مزود بنظام غسيل تلقائي وعدادَي ضغط وصنبور', 'ivlife-auto-flush', 'IVlife', $home, ['تناضح عكسي', 'مع عداد ضغط'], $description),
            $this->item('bwater', 'assets/newimages/9.webp', 'services/catalog/bwater-seven-stage.webp', 'جهاز تحلية Bwater سبع مراحل', 'جهاز تحلية Bwater سبع مراحل بمراحل معدنية وقلوية', 'bwater-7-stage', 'Bwater', $home, ['تناضح عكسي', '7 مراحل', 'صنع في فيتنام'], $description),
        ];
    }

    private function item(string $key, string $source, string $path, string $name, string $alt, string $slug, ?string $brand, string $category, array $tags, callable $description): array
    {
        return [
            'key' => $key, 'source' => $source, 'path' => $path, 'name' => $name, 'alt' => $alt,
            'caption' => $alt.' — صورة المنتج الأصلية المتاحة لدى المتجر.',
            'usage_notes' => 'مناسب لصفحات المنتجات والخدمات والمقالات المرتبطة بنوع الجهاز الظاهر في الصورة.',
            'slug' => $slug, 'brand' => $brand, 'category' => $category, 'tags' => $tags,
            'excerpt' => $alt.'. السعر والتوفر والمواصفات النهائية عند الطلب.',
            'description' => $description($name),
        ];
    }
}
