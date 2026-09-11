@extends('layouts.app')

@section('content')
<article class="article-reference">
    <header class="article-reference-hero section-indexed">
        <div class="container-shell">
            <nav class="breadcrumbs" aria-label="مسار الصفحة"><a href="{{ route('home') }}">الرئيسية</a><span>/</span><a href="{{ route('guide.index') }}">المقالات</a><span>/</span><b aria-current="page">{{ $article->title }}</b></nav>
            <div class="article-hero-grid">
                <div class="article-title-block" data-hero-copy><p class="eyebrow">{{ $article->category->name }}</p><h1>{{ $article->title }}</h1><p>{{ $article->excerpt }}</p><div class="article-meta"><span>دليل عملي</span><i></i><span>{{ $readingMinutes }} {{ $readingMinutes === 1 ? 'دقيقة' : 'دقائق' }} قراءة</span><i></i><time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->translatedFormat('j F Y') }}</time></div></div>
                @if($article->featured_image || $articleImage)<div class="article-hero-image technical-image" data-hero-visual><x-article-cover :article="$article" :image="$articleImage" variant="gallery" loading="eager" fetchpriority="high" sizes="(max-width: 780px) 100vw, 42vw" /><span class="measure measure-top" aria-hidden="true">صورة مرتبطة بموضوع المقال</span></div>@endif
            </div>
        </div>
    </header>

    <div class="container-shell article-reference-layout">
        <aside class="article-toc" aria-label="فهرس المحتوى"><strong>في هذا الدليل</strong><ol>@foreach($articleSections as $section)@if($section['title'])<li><a href="#{{ $section['id'] }}"><span>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $section['title'] }}</a></li>@endif @endforeach</ol></aside>
        <div class="article-reference-body">
            @foreach($articleSections as $section)
                <section id="{{ $section['id'] }}" data-reveal>
                    @if($section['title'])<div class="article-section-title"><span>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h2>{{ $section['title'] }}</h2></div>@endif
                    @foreach($section['paragraphs'] as $paragraph)<p>{{ $paragraph }}</p>@endforeach
                    @if($loop->iteration === 2 && $articleImages->get(1))<figure class="article-inline-image technical-image"><x-responsive-image :image="$articleImages->get(1)" :alt="$articleImages->get(1)->alt_text ?: $article->title" sizes="(max-width: 780px) 100vw, 62vw" /><figcaption>{{ $articleImages->get(1)->caption ?: $articleImages->get(1)->title }}</figcaption></figure>@endif
                </section>
            @endforeach

            @if($article->services->isNotEmpty())
                <aside class="article-service-cta" data-reveal><div><p class="eyebrow">خدمات مرتبطة بهذا المقال</p><h2>راجع الخدمة ثم احجز الموعد</h2></div><div>@foreach($article->services->take(3) as $service)<a href="{{ route('services.show', $service->slug) }}">{{ $service->name }} <span>←</span></a>@endforeach</div></aside>
            @endif
        </div>
    </div>
</article>

@if($article->faqs->isNotEmpty())<x-faqs :faqs="$article->faqs" />@endif

@if($article->relatedArticles->isNotEmpty())<section class="section-block editorial-reference section-indexed" data-reveal><div class="container-shell"><div class="section-row"><div><p class="eyebrow">أكمل القراءة</p><h2>مقالات وخدمات مرتبطة</h2></div></div><div class="editorial-reference-grid">@foreach($article->relatedArticles as $item)<x-article-card :article="$item" />@endforeach</div></div></section>@endif

<section class="article-contact-strip"><div class="container-shell"><div><p class="eyebrow">لديك صورة للجهاز أو المشكلة؟</p><h2>أرسل التفاصيل واحجز التركيب أو الصيانة</h2></div><a class="button button-primary" href="{{ route('quote') }}">احجز الآن</a></div></section>
@endsection
