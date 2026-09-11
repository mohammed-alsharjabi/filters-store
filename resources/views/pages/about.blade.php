@extends('layouts.app')
@section('content')
<x-page-hero eyebrow="خدمة فلاتر وتحلية داخل الرياض" title="من نحن" description="نركب ونصون فلاتر المياه وأجهزة ومحطات التحلية، ونبدأ كل طلب بفهم الجهاز والموقع والمشكلة." />

<section class="about-story">
    <div class="container-shell">
        <figure class="about-story-image">
            <img src="{{ asset('storage/'.($siteSettings['about_image'] ?? config('site.about_image'))) }}" alt="جهاز تحلية مياه منزلي متعدد المراحل" width="1254" height="1254" loading="eager" decoding="async">
            <figcaption><strong>الرياض</strong><span>تركيب وصيانة فلاتر ومحطات تحلية المياه</span></figcaption>
        </figure>

        <div class="about-story-grid">
            <article class="about-story-copy">
                <p class="eyebrow">تفاصيل واضحة قبل بدء الخدمة</p>
                <h2>نختار الحل بعد فهم المياه والجهاز والموقع</h2>
                <p>نعمل في تركيب وصيانة فلاتر المياه المنزلية وأجهزة ومحطات التحلية، إلى جانب فلاتر الخزانات والشاور وأنظمة الضباب والرذاذ. ننطلق من حي المونسية ونستقبل الطلبات من مختلف أحياء الرياض.</p>
                <p>في التركيب نراجع مصدر المياه والمساحة والتوصيلات، وفي الصيانة نطلب صورة الجهاز ووصف المشكلة قبل استبدال أي قطعة. بعد الإنجاز نفحص التسريب والتدفق ونوضح للعميل ما يحتاج متابعته.</p>
            </article>

            <aside class="about-contact-card">
                <span>تواصل مباشرة</span>
                <h2>أرسل صورة الجهاز واحجز الموعد</h2>
                <a href="{{ $siteSettings['phone_tel'] }}" dir="ltr">{{ $siteSettings['phone_display'] }}</a>
                <p>اتصل أو أرسل صورة الفلتر ومكان التركيب ووصف المشكلة عبر واتساب لنراجع الطلب قبل الزيارة.</p>
                <div><a class="button button-primary" href="{{ route('quote') }}">احجز الآن</a><a class="button button-outline" href="{{ $siteSettings['whatsapp_url'] }}" target="_blank" rel="noopener">واتساب</a></div>
            </aside>
        </div>

        <ol class="about-principles" aria-label="طريقة عملنا">
            <li><span>01</span><div><h3>نفهم الطلب</h3><p>نراجع نوع المياه والجهاز والعطل أو مكان التركيب.</p></div></li>
            <li><span>02</span><div><h3>نوضح المطلوب</h3><p>نحدد الخدمة والقطع والأعمال اللازمة قبل التنفيذ.</p></div></li>
            <li><span>03</span><div><h3>ننفذ ونختبر</h3><p>نركب أو نصون الجهاز ثم نفحص التسريب والتدفق.</p></div></li>
        </ol>
    </div>
</section>
@endsection
