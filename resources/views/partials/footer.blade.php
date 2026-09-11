@php
    $footerCategories = collect($navServiceCategories ?? [])->filter(fn ($category) => ! empty($category['services']));
@endphp
<footer class="site-footer">
    <div class="container-shell footer-grid">
        <section class="footer-about" aria-labelledby="footer-brand-title">
            <a href="{{ route('home') }}" class="brand footer-brand" aria-label="{{ $siteSettings['site_name'] }} - الرئيسية">
                <x-site-logo class="footer-brand-logo" :alt="$siteSettings['site_name']" />
                <strong id="footer-brand-title" class="sr-only">{{ $siteSettings['site_name'] }}</strong>
            </a>
            <p>تركيب وصيانة فلاتر المياه وأجهزة ومحطات التحلية وأنظمة الضباب والرذاذ داخل الرياض.</p>
            <a class="footer-quote" href="{{ route('quote') }}">احجز خدمتك الآن <span aria-hidden="true">←</span></a>
        </section>

        <nav class="footer-column footer-service-groups" aria-label="تصنيفات خدماتنا">
            <h2>خدماتنا</h2>
            @forelse($footerCategories as $category)
                <a href="{{ route('services.category', $category['slug']) }}">{{ $category['name'] }} <small>{{ count($category['services']) }}</small></a>
            @empty
                <a href="{{ route('services.index') }}">جميع الخدمات</a>
            @endforelse
        </nav>

        <nav class="footer-column" aria-label="روابط مهمة">
            <h2>روابط مهمة</h2>
            <a href="{{ route('services.index') }}">الخدمات</a>
            <a href="{{ route('products.index') }}">المنتجات</a>
            <a href="{{ route('cart.show') }}">سلة المشتريات</a>
            <a href="{{ route('guide.index') }}">المقالات</a>
            <a href="{{ route('about') }}">من نحن</a>
            <a href="{{ route('contact') }}">تواصل معنا</a>
        </nav>

        <section class="footer-contact" aria-labelledby="footer-contact-title">
            <h2 id="footer-contact-title">تواصل معنا</h2>
            <p><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s6-5.5 6-11a6 6 0 1 0-12 0c0 5.5 6 11 6 11Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="10" r="2" fill="currentColor"/></svg><span>{{ $siteSettings['primary_service_area'] ?? $siteSettings['city'] }}</span></p>
            <a href="{{ $siteSettings['phone_tel'] }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.3 3.8 9.6 8c.3.6.2 1.2-.3 1.6l-1.4 1.1a15 15 0 0 0 5.4 5.4l1.1-1.4c.4-.5 1.1-.6 1.6-.3l4.2 2.3c.6.3.9 1 .7 1.7l-.6 2.1c-.2.8-.9 1.3-1.7 1.4C9.8 22.1 1.9 14.2 2.1 5.4c0-.8.6-1.5 1.4-1.7l2.1-.6c.7-.2 1.4.1 1.7.7Z" fill="none" stroke="currentColor" stroke-width="1.8"/></svg><span dir="ltr">{{ $siteSettings['phone_display'] }}</span></a>
            <a href="{{ $siteSettings['whatsapp_url'] }}" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.6a8.5 8.5 0 0 1-12.6 7.5L3 20.4l1.3-4.7A8.5 8.5 0 1 1 20.5 11.6Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8.3 7.3c.3-.4.7-.3.9.1l1 2c.2.4.1.7-.2 1l-.6.5c.7 1.4 1.8 2.5 3.2 3.2l.5-.6c.3-.3.6-.4 1-.2l2 1c.4.2.5.6.2.9-.5.8-1.3 1.2-2.2 1.1-3.4-.5-6.1-3.2-6.6-6.6-.1-.9.2-1.8.8-2.4Z" fill="currentColor"/></svg><span>محادثة واتساب</span></a>
        </section>
    </div>

    <div class="footer-meta-wrap">
        <div class="container-shell footer-meta">
            <p>© {{ now()->year }} {{ $siteSettings['site_name'] }}. جميع الحقوق محفوظة.</p>
            <nav aria-label="روابط قانونية"><a href="{{ route('privacy') }}">الخصوصية</a><a href="{{ route('terms') }}">الشروط</a></nav>
        </div>
    </div>
</footer>
