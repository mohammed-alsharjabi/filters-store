@props(['eyebrow'=>null,'title','description'=>null])
<section class="page-hero">
    <div class="container-shell relative z-10 py-16 lg:py-20">
        <nav class="page-breadcrumbs" aria-label="مسار الصفحة"><a href="{{ route('home') }}">الرئيسية</a><span aria-hidden="true">/</span><b aria-current="page">{{ $title }}</b></nav>
        @if($eyebrow)<p class="eyebrow">{{ $eyebrow }}</p>@endif
        <h1>{{ $title }}</h1>
        @if($description)<p>{{ $description }}</p>@endif
    </div>
</section>
