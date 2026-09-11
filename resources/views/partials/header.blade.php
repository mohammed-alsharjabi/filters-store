@php
    $navigationCategories = collect($navServiceCategories)->filter(fn ($category) => ! empty($category['services']));
    $maintenanceCategory = $navigationCategories->first(fn ($category) => str_contains($category['name'], 'الصيانة'));
@endphp
<header class="site-header" data-header>
    <span class="header-geo" aria-hidden="true">
        <span class="header-geo-facet header-geo-facet-start"></span>
        <span class="header-geo-facet header-geo-facet-end"></span>
        <svg class="header-geo-weave" width="100%" height="100%" focusable="false">
            <defs>
                <pattern id="header-geo-weave-tile" width="120" height="86" patternUnits="userSpaceOnUse">
                    <path d="M-8 34 L52 -6 L112 34" />
                    <path d="M-8 78 L52 38 L112 78" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#header-geo-weave-tile)" />
        </svg>
        <svg class="header-geo-edge" width="100%" height="14" focusable="false">
            <defs>
                <pattern id="header-geo-edge-tile" width="120" height="14" patternUnits="userSpaceOnUse">
                    <path class="header-geo-edge-back" d="M0 -7 L60 4 L120 -7 L120 14 L0 14 Z" />
                    <path class="header-geo-edge-front" d="M0 0 L60 11 L120 0 L120 14 L0 14 Z" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#header-geo-edge-tile)" />
        </svg>
    </span>
    <div class="container-shell header-inner">
        <a href="{{ route('home') }}" class="brand" aria-label="{{ $siteSettings['site_name'] }} - الرئيسية">
            <x-site-logo class="header-brand-logo" :alt="$siteSettings['site_name']" fetchpriority="high" />
        </a>

        <nav class="desktop-nav" aria-label="التنقل الرئيسي">
            <a @class(['active' => request()->routeIs('home')]) href="{{ route('home') }}">الرئيسية</a>
            <div class="services-dropdown">
                <button type="button" aria-haspopup="true">خدماتنا <span aria-hidden="true">⌄</span></button>
                <div class="services-dropdown-panel">
                    @foreach($navigationCategories as $category)
                        <section>
                            <a class="dropdown-category" href="{{ route('services.category', $category['slug']) }}">{{ $category['name'] }}</a>
                            @foreach($category['services'] as $item)
                                <a href="{{ route('services.show', $item['slug']) }}">{{ $item['name'] }}</a>
                            @endforeach
                        </section>
                    @endforeach
                    <a class="dropdown-all" href="{{ route('services.index') }}">عرض جميع الخدمات ←</a>
                </div>
            </div>
            @if($maintenanceCategory)<a href="{{ route('services.category', $maintenanceCategory['slug']) }}">الصيانة</a>@endif
            <a @class(['active' => request()->routeIs('guide.*')]) href="{{ route('guide.index') }}">المقالات</a>
            <a @class(['active' => request()->routeIs('about')]) href="{{ route('about') }}">من نحن</a>
            <a @class(['active' => request()->routeIs('contact')]) href="{{ route('contact') }}">تواصل معنا</a>
        </nav>

        <a class="button button-primary header-cta" href="{{ route('quote') }}">احجز الآن</a>
        <div class="mobile-header-actions">
            <span class="mobile-header-location"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s6-5.5 6-11a6 6 0 1 0-12 0c0 5.5 6 11 6 11Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="10" r="2" fill="currentColor"/></svg>{{ $siteSettings['city'] }}</span>
            <a href="{{ $siteSettings['phone_tel'] }}" aria-label="اتصال"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.3 3.8 9.6 8c.3.6.2 1.2-.3 1.6l-1.4 1.1a15 15 0 0 0 5.4 5.4l1.1-1.4c.4-.5 1.1-.6 1.6-.3l4.2 2.3c.6.3.9 1 .7 1.7l-.6 2.1c-.2.8-.9 1.3-1.7 1.4C9.8 22.1 1.9 14.2 2.1 5.4c0-.8.6-1.5 1.4-1.7l2.1-.6c.7-.2 1.4.1 1.7.7Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="mobile-navigation" data-nav-toggle><span></span><span></span><span></span><b class="sr-only">فتح القائمة</b></button>
        </div>
    </div>
</header>

<div class="nav-backdrop" data-nav-backdrop hidden></div>
<aside id="mobile-navigation" class="mobile-drawer" aria-label="قائمة الجوال" aria-hidden="true" data-mobile-drawer>
    <div class="mobile-drawer-head"><a class="drawer-brand" href="{{ route('home') }}" aria-label="{{ $siteSettings['site_name'] }} - الرئيسية"><x-site-logo class="drawer-brand-logo" :alt="$siteSettings['site_name']" /></a><button type="button" data-nav-close aria-label="إغلاق القائمة">×</button></div>
    <nav>
        <a href="{{ route('home') }}">الرئيسية</a>
        <details><summary>خدماتنا</summary><div>@foreach($navigationCategories as $category)<strong>{{ $category['name'] }}</strong>@foreach($category['services'] as $item)<a href="{{ route('services.show', $item['slug']) }}">{{ $item['name'] }}</a>@endforeach @endforeach</div></details>
        @if($maintenanceCategory)<a href="{{ route('services.category', $maintenanceCategory['slug']) }}">الصيانة</a>@endif
        <a href="{{ route('guide.index') }}">المقالات</a>
        <a href="{{ route('about') }}">من نحن</a>
        <a href="{{ route('contact') }}">تواصل معنا</a>
    </nav>
    <div class="mobile-drawer-actions"><a class="button button-primary" href="{{ route('quote') }}">احجز الآن</a><a class="button button-outline" href="{{ $siteSettings['whatsapp_url'] }}" target="_blank" rel="noopener">واتساب</a></div>
</aside>
