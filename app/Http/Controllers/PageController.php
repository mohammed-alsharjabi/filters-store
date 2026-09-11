<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Material;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\TrustItem;
use App\Support\ArticleContent;
use App\Support\Seo;
use App\Support\SettingsRepository;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        $services = Service::published()
            ->with([
                'category', 'parent',
                'images' => fn ($query) => $query->where('processing_status', 'processed')->reorder()->orderByDesc('is_cover')->orderBy('sort_order')->limit(1),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $mainServices = $services->whereNull('parent_service_id')->values();
        // كل خدمة رئيسية تتبعها خدماتها الفرعية حتى تقرأ الشبكة بترتيب التصنيف نفسه.
        $serviceCards = $mainServices
            ->flatMap(fn (Service $service) => [$service, ...$services->where('parent_service_id', $service->id)])
            ->values();
        $serviceCards = $serviceCards
            ->concat($services->whereNotIn('id', $serviceCards->pluck('id')))
            ->values();
        $heroService = $mainServices->firstWhere('name', 'تركيب فلاتر المياه بالرياض') ?: $mainServices->first();
        $galleryImages = MediaAsset::active()->orderBy('sort_order')->orderBy('id')->get();
        $featuredProducts = Product::published()->with(['category', 'mediaAsset', 'tags' => fn ($query) => $query->where('is_active', true)])
            ->orderByDesc('is_featured')->orderBy('sort_order')->limit(8)->get();
        $trustItems = TrustItem::query()->where('is_active', true)->orderBy('sort_order')->get();
        $seo = Seo::page('فلاتر وتحلية المياه بالرياض | تركيب وصيانة', 'تركيب وصيانة فلاتر المياه وأجهزة ومحطات التحلية وأنظمة الضباب والرذاذ في الرياض. تواصل للحجز وإرسال صورة الجهاز.', $heroService);
        $seo['og_image'] = app(SettingsRepository::class)->public()['hero_image'] ?? config('site.hero_image');

        return view('pages.home', compact('serviceCards', 'galleryImages', 'featuredProducts', 'trustItems', 'seo'));
    }

    public function about(): View
    {
        $seo = Seo::page('من نحن | فلاتر وتحلية المياه بالرياض', 'نتخصص في تركيب وصيانة فلاتر المياه وأجهزة ومحطات التحلية وخدمة العملاء داخل مدينة الرياض.', null, $this->crumbs(['من نحن' => route('about')]));

        return view('pages.about', compact('seo'));
    }

    public function services(): View
    {
        $categories = ServiceCategory::query()->where('is_active', true)->with(['services' => fn ($q) => $q->published()
            ->with(['category', 'images' => fn ($images) => $images->where('processing_status', 'processed')->reorder()->orderByDesc('is_cover')->orderBy('sort_order')->limit(1)])
            ->orderBy('sort_order')])->orderBy('sort_order')->get();
        $seo = Seo::page('خدمات فلاتر وتحلية المياه في الرياض', 'تصفح خدمات تركيب وصيانة الفلاتر وأجهزة ومحطات التحلية وأنظمة الرذاذ في الرياض واحجز الخدمة المناسبة.', null, $this->crumbs(['الخدمات' => route('services.index')]));
        if ($categories->sum(fn (ServiceCategory $category): int => $category->services->count()) === 0) {
            $seo = Seo::noindex($seo);
        }

        return view('pages.services.index', compact('categories', 'seo'));
    }

    public function serviceCategory(string $slug): View
    {
        $category = ServiceCategory::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $services = $category->services()->published()->with(['category', 'materials', 'images' => fn ($images) => $images->where('processing_status', 'processed')->reorder()->orderByDesc('is_cover')->orderBy('sort_order')->limit(1)])->orderBy('sort_order')->paginate(12);
        $seo = Seo::page($category->name.' في الرياض', $category->excerpt ?: 'خدمات '.$category->name.' داخل مدينة الرياض.', $category, $this->crumbs(['الخدمات' => route('services.index'), $category->name => url()->current()]));
        $seo = Seo::paginate($services->isEmpty() ? Seo::noindex($seo) : $seo, $services);

        return view('pages.services.category', compact('category', 'services', 'seo'));
    }

    public function service(string $slug): View
    {
        $service = Service::published()->where('slug', $slug)->with([
            'category', 'materials', 'parent',
            'images' => fn ($q) => $q->where('processing_status', 'processed')->reorder()->orderBy('sort_order'),
            'children' => fn ($q) => $q->published()->with([
                'category',
                'images' => fn ($images) => $images->where('processing_status', 'processed')->reorder()->orderBy('sort_order'),
            ]),
            'seo',
        ])->firstOrFail();
        $isMainService = $service->parent_service_id === null;
        $legacyImages = $service->images->where('processing_status', 'processed')->unique('optimized_path')->values()->take(10);
        $legacyPaths = $legacyImages->pluck('optimized_path')->filter();
        $mediaAssets = $service->mediaAssets()->active()->reorder()
            ->when($legacyPaths->isNotEmpty(), fn ($query) => $query->whereNotIn('media_assets.path', $legacyPaths))
            ->inRandomOrder()->limit(max(0, 10 - $legacyImages->count()))->get();
        $galleryImages = $legacyImages->concat($mediaAssets)->unique(fn ($image) => $image->optimized_path ?? $image->path)->take(10)->values();
        $service->setRelation('mediaAssets', $mediaAssets);
        $childServices = $isMainService ? $service->children : collect();
        $related = Service::published()->whereKeyNot($service->id)
            ->where(fn ($query) => $query
                ->whereNotNull('featured_image')
                ->orWhereHas('images', fn ($images) => $images->where('processing_status', 'processed')))
            ->with(['category', 'images' => fn ($q) => $q->where('processing_status', 'processed')->reorder()->orderByDesc('is_cover')->orderBy('sort_order')->limit(1)])
            ->orderByRaw('CASE WHEN service_category_id = ? THEN 0 ELSE 1 END', [$service->service_category_id])->limit(3)->get();
        $seo = Seo::page($service->name.' | فلاتر وتحلية المياه بالرياض', $service->excerpt ?: 'تفاصيل '.$service->name.' وخيارات الحجز داخل الرياض.', $service, $this->crumbs(['الخدمات' => route('services.index'), $service->name => url()->current()]));

        return view('pages.services.show', compact('service', 'related', 'galleryImages', 'childServices', 'isMainService', 'seo'));
    }

    public function projects(): View
    {
        $projects = Project::published()->with(['service', 'area', 'images'])->latest('published_at')->paginate(12);
        $seo = Seo::page('أعمال فلاتر وتحلية المياه بالرياض', 'أعمال تركيب وصيانة موثقة يضيفها فريق الموقع بعد التنفيذ داخل أحياء الرياض.', null, $this->crumbs(['الأعمال' => route('projects.index')]));
        $seo = Seo::paginate($projects->isEmpty() ? Seo::noindex($seo) : $seo, $projects);

        return view('pages.projects.index', compact('projects', 'seo'));
    }

    public function project(string $slug): View
    {
        $project = Project::published()->where('slug', $slug)->with(['service', 'area', 'images', 'seo'])->firstOrFail();
        $seo = Seo::page($project->title.' | مشروع في '.$project->area->name, $project->excerpt ?: 'تفاصيل مشروع '.$project->title.' في '.$project->area->name, $project, $this->crumbs(['المشاريع' => route('projects.index'), $project->title => url()->current()]));

        return view('pages.projects.show', compact('project', 'seo'));
    }

    public function areas(): View
    {
        $areas = Area::published()->withCount(['projects' => fn ($q) => $q->published()])->orderByDesc('is_primary')->orderBy('name')->paginate(18);
        $seo = Seo::page('مناطق خدمة فلاتر وتحلية المياه داخل الرياض', 'موقع النشاط في حي المونسية ونستقبل طلبات تركيب وصيانة الفلاتر والتحلية من مختلف أحياء الرياض.', null, $this->crumbs(['المناطق' => route('areas.index')]));
        $seo = Seo::paginate($areas->isEmpty() ? Seo::noindex($seo) : $seo, $areas);

        return view('pages.areas.index', compact('areas', 'seo'));
    }

    public function area(string $slug): View
    {
        $area = Area::published()->where('slug', $slug)->with(['faqs' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'), 'seo'])->firstOrFail();
        $projects = $area->projects()->published()->with(['service', 'images'])->latest('published_at')->paginate(9);
        $seo = Seo::page('فلاتر وتحلية المياه في '.$area->name, $area->excerpt ?: 'خدمات فلاتر وتحلية المياه في '.$area->name.' داخل مدينة الرياض.', $area, $this->crumbs(['المناطق' => route('areas.index'), $area->name => url()->current()]), [Seo::faqSchema($area->faqs)]);
        $seo = Seo::paginate($seo, $projects);

        return view('pages.areas.show', compact('area', 'projects', 'seo'));
    }

    public function guide(): View
    {
        $articles = Article::published()->with('category')->latest('published_at')->paginate(12);
        $categories = ArticleCategory::query()->where('is_active', true)->withCount(['articles' => fn ($q) => $q->published()])->get();
        $seo = Seo::page('مقالات فلاتر وتحلية المياه', 'مقالات عملية عن اختيار فلاتر المياه ومراحل التحلية ومواعيد تغيير الشمعات وتشخيص الأعطال الشائعة.', null, $this->crumbs(['المقالات' => route('guide.index')]));
        $seo = Seo::paginate($articles->isEmpty() ? Seo::noindex($seo) : $seo, $articles);

        return view('pages.guide.index', compact('articles', 'categories', 'seo'));
    }

    public function article(string $slug, ArticleContent $content): View
    {
        $article = Article::published()->where('slug', $slug)->with([
            'category',
            'mediaAssets' => fn ($query) => $query->active(),
            'services' => fn ($query) => $query->published()->with(['category', 'images' => fn ($images) => $images->where('processing_status', 'processed')->reorder()->orderByDesc('is_cover')->orderBy('sort_order')->limit(4)]),
            'faqs' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
            'relatedArticles' => fn ($q) => $q->published()->with('category')->limit(4),
            'seo',
        ])->firstOrFail();
        $articleSections = $content->sections($article->body);
        $readingMinutes = $content->readingMinutes($article->body);
        $articleImages = $article->mediaAssets->unique('id')->values();
        $articleImage = $articleImages->first();
        $seo = Seo::page($article->title, $article->excerpt ?: 'مقال من دليل فلاتر وتحلية المياه.', $article, $this->crumbs(['المقالات' => route('guide.index'), $article->title => url()->current()]), [Seo::faqSchema($article->faqs)]);

        return view('pages.guide.show', compact('article', 'articleSections', 'readingMinutes', 'articleImages', 'articleImage', 'seo'));
    }

    public function prices(): View
    {
        $services = Service::published()->where('is_price_published', true)->whereNotNull('price_from')->orderBy('name')->get();
        $materials = Material::query()->where('is_active', true)->where('is_price_published', true)->whereNotNull('price_from')->orderBy('name')->get();
        $seo = Seo::page('باقات وأسعار فلاتر المياه بالرياض', 'صفحة الباقات والأسعار قيد التجهيز، ولن يظهر أي سعر قبل اعتماده مع تفاصيل الجهاز والتركيب والضريبة.', null, $this->crumbs(['الباقات والأسعار' => route('prices')]));
        if ($services->isEmpty() && $materials->isEmpty()) {
            $seo = Seo::noindex($seo);
        }

        return view('pages.prices', compact('services', 'materials', 'seo'));
    }

    public function quote(): View
    {
        $services = Service::published()->orderBy('name')->get(['id', 'name']);
        $areaSize = request()->integer('area_size');
        $estimateMessage = $areaSize > 0 && $areaSize <= 10000 ? 'عدد الأجهزة التقريبي: '.$areaSize.'. أرجو التواصل لتحديد الخدمة المناسبة.' : '';
        $seo = Seo::page('احجز تركيب أو صيانة فلتر مياه بالرياض', 'أرسل نوع الجهاز وصورته وموقعك في الرياض لترتيب خدمة التركيب أو الصيانة.', null, $this->crumbs(['احجز الآن' => route('quote')]));

        return view('pages.quote', compact('services', 'estimateMessage', 'seo'));
    }

    public function contact(): View
    {
        $services = Service::published()->orderBy('name')->get(['id', 'name']);
        $seo = Seo::page('تواصل مع فلاتر وتحلية المياه بالرياض', 'اتصل أو تواصل عبر واتساب لحجز تركيب أو صيانة فلاتر المياه وأجهزة ومحطات التحلية داخل الرياض.', null, $this->crumbs(['تواصل معنا' => route('contact')]));

        return view('pages.contact', compact('services', 'seo'));
    }

    public function privacy(): View
    {
        $seo = Seo::page('سياسة الخصوصية', 'كيفية جمع واستخدام وحماية البيانات المرسلة عبر موقع فلاتر وتحلية المياه بالرياض.', null, $this->crumbs(['سياسة الخصوصية' => route('privacy')]));

        return view('pages.privacy', compact('seo'));
    }

    public function terms(): View
    {
        $seo = Seo::page('الشروط والأحكام', 'الشروط المنظمة لاستخدام موقع فلاتر وتحلية المياه بالرياض وإرسال طلبات الحجز والتواصل.', null, $this->crumbs(['الشروط والأحكام' => route('terms')]));

        return view('pages.terms', compact('seo'));
    }

    private function crumbs(array $items): array
    {
        return [['name' => 'الرئيسية', 'url' => route('home')], ...collect($items)->map(fn ($url, $name) => ['name' => $name, 'url' => $url])->values()->all()];
    }
}
