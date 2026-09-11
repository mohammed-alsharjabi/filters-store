<!doctype html>
<html lang="ar-SA" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $themePalette = app(\App\Support\ThemePalette::class);
        $themeValues = $themePalette->fromSettings($siteSettings);
    @endphp
    <meta name="theme-color" content="{{ $themeValues['--color-primary'] }}">
    <link rel="icon" href="{{ asset('brand/filters-store-logo.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('brand/filters-store-logo.png') }}">
    <title>{{ $seo['title'] ?? $siteSettings['site_name'] }}</title>
    <meta name="description" content="{{ $seo['description'] ?? '' }}">
    <meta name="robots" content="{{ $seo['robots'] ?? 'index,follow' }}">
    <link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">
    @if(!empty($seo['prev']))<link rel="prev" href="{{ $seo['prev'] }}">@endif
    @if(!empty($seo['next']))<link rel="next" href="{{ $seo['next'] }}">@endif
    <link rel="alternate" hreflang="ar-SA" href="{{ $seo['canonical'] ?? url()->current() }}">
    <link rel="alternate" hreflang="x-default" href="{{ $seo['canonical'] ?? url()->current() }}">
    <meta property="og:locale" content="ar_SA">
    <meta property="og:type" content="{{ $seo['og_type'] ?? 'website' }}">
    <meta property="og:site_name" content="{{ $siteSettings['site_name'] }}">
    <meta property="og:title" content="{{ $seo['og_title'] ?? ($seo['title'] ?? $siteSettings['site_name']) }}">
    <meta property="og:description" content="{{ $seo['og_description'] ?? ($seo['description'] ?? '') }}">
    <meta property="og:url" content="{{ $seo['canonical'] ?? url()->current() }}">
    @php
        $socialImage = !empty($seo['og_image']) ? (\Illuminate\Support\Str::startsWith($seo['og_image'], ['http://','https://']) ? $seo['og_image'] : asset('storage/'.$seo['og_image'])) : null;
    @endphp
    @if($socialImage)<meta property="og:image" content="{{ $socialImage }}"><meta property="og:image:alt" content="{{ $seo['og_title'] ?? ($seo['title'] ?? $siteSettings['site_name']) }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['og_title'] ?? ($seo['title'] ?? $siteSettings['site_name']) }}">
    <meta name="twitter:description" content="{{ $seo['og_description'] ?? ($seo['description'] ?? '') }}">
    @if($socialImage)<meta name="twitter:image" content="{{ $socialImage }}">@endif
    @if(!empty($siteSettings['search_console_verification']))<meta name="google-site-verification" content="{{ $siteSettings['search_console_verification'] }}">@endif
    <script>window.dataLayer=window.dataLayer||[];</script>
    @if(!empty($siteSettings['ga_measurement_id']))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $siteSettings['ga_measurement_id'] }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config',@js($siteSettings['ga_measurement_id']),{'anonymize_ip':true});</script>
    @endif
    <style id="theme-palette">{!! $themePalette->css($siteSettings) !!}</style>
    <script>document.documentElement.classList.add('js')</script>
    @vite(['resources/css/app.css','resources/js/app.js'])
    @stack('head')
    @stack('dataLayer')
    @if(session('data_layer_events'))<script>@foreach(session('data_layer_events') as $event)window.dataLayer.push(@js($event));@endforeach</script>@endif
    @foreach(($seo['schemas'] ?? []) as $schema)
        @if($schema)<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP) !!}</script>@endif
    @endforeach
</head>
<body class="@yield('body-class')">
    <a href="#main-content" class="skip-link">انتقل إلى المحتوى</a>
    @include('partials.header')
    <main id="main-content">@yield('content')</main>
    @include('partials.footer')
    @php
        $currentWhatsapp = $siteSettings['whatsapp_url'];
        if (request()->routeIs('services.show') && isset($service) && $service instanceof \App\Models\Service) {
            $currentWhatsapp .= '?text='.rawurlencode('مرحبًا، أود الاستفسار عن خدمة '.$service->name.': '.route('services.show', $service->slug));
        }
    @endphp
    @include('partials.mobile-actions')
    @stack('scripts')
</body>
</html>
