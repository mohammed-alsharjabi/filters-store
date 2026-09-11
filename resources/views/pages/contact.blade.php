@extends('layouts.app')

@section('body-class', 'contact-page-body')

@section('content')
<section class="contact-hero">
    <div class="container-shell contact-hero-inner">
        <div class="contact-hero-copy" data-reveal>
            <nav class="contact-breadcrumbs" aria-label="مسار الصفحة">
                <a href="{{ route('home') }}">الرئيسية</a>
                <span aria-hidden="true">/</span>
                <b aria-current="page">تواصل معنا</b>
            </nav>
            <p class="contact-kicker">نخدم جميع مناطق الرياض</p>
            <h1>أرسل صورة الجهاز ونوع الخدمة</h1>
            <p>شارك المعلومات المتوفرة لديك، وسنراجع طلب التركيب أو الصيانة ونكمل التفاصيل عبر واتساب أو الاتصال.</p>
            <div class="contact-hero-points" aria-label="مميزات التواصل">
                <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4 10-10" fill="none" stroke="currentColor" stroke-width="2"/></svg>نموذج مختصر</span>
                <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/></svg>لا يستغرق دقيقة</span>
                <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 10V8a6 6 0 0 1 12 0v2M5 10h14v10H5Z" fill="none" stroke="currentColor" stroke-width="2"/></svg>بياناتك محفوظة</span>
            </div>
        </div>

        <div class="contact-hero-direct" data-reveal>
            <p>تفضّل التواصل مباشرة؟</p>
            <a class="contact-direct-card contact-direct-call" href="{{ $siteSettings['phone_tel'] }}">
                <span class="contact-direct-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.3 3.8 9.6 8c.3.6.2 1.2-.3 1.6l-1.4 1.1a15 15 0 0 0 5.4 5.4l1.1-1.4c.4-.5 1.1-.6 1.6-.3l4.2 2.3c.6.3.9 1 .7 1.7l-.6 2.1c-.2.8-.9 1.3-1.7 1.4C9.8 22.1 1.9 14.2 2.1 5.4c0-.8.6-1.5 1.4-1.7l2.1-.6c.7-.2 1.4.1 1.7.7Z" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></span>
                <span><small>اتصال مباشر</small><strong dir="ltr">{{ $siteSettings['phone_display'] }}</strong></span>
                <svg class="contact-card-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            </a>
            <a class="contact-direct-card contact-direct-whatsapp" href="{{ $siteSettings['whatsapp_url'] }}?text={{ rawurlencode('السلام عليكم، أود الاستفسار عن خدمات فلاتر وتحلية المياه بالرياض: '.route('contact')) }}" target="_blank" rel="noopener">
                <span class="contact-direct-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.6a8.5 8.5 0 0 1-12.6 7.5L3 20.4l1.3-4.7A8.5 8.5 0 1 1 20.5 11.6Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8.3 7.3c.3-.4.7-.3.9.1l1 2c.2.4.1.7-.2 1l-.6.5c.7 1.4 1.8 2.5 3.2 3.2l.5-.6c.3-.3.6-.4 1-.2l2 1c.4.2.5.6.2.9-.5.8-1.3 1.2-2.2 1.1-3.4-.5-6.1-3.2-6.6-6.6-.1-.9.2-1.8.8-2.4Z" fill="currentColor"/></svg></span>
                <span><small>محادثة واتساب</small><strong>ابدأ المحادثة الآن</strong></span>
                <svg class="contact-card-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            </a>
        </div>
    </div>
</section>

<section class="contact-request-section" aria-labelledby="contact-form-title">
    <div class="container-shell contact-request-layout">
        <div class="contact-form-card" data-reveal>
            <div class="contact-form-heading">
                <span class="contact-heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM4 8l8 6 8-6" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></span>
                <div>
                    <p>طلب تواصل أو معاينة</p>
                    <h2 id="contact-form-title">أرسل التفاصيل المتوفرة</h2>
                    <span><b>*</b> رقم الجوال فقط مطلوب، وكل ما عداه اختياري.</span>
                </div>
            </div>
            <x-lead-form :services="$services" type="contact" submission-channel="whatsapp" />
        </div>

        <aside class="contact-info-card" aria-label="معلومات التواصل" data-reveal>
            <p class="contact-kicker">معلومات سريعة</p>
            <h2>نحن أقرب لموقعك</h2>
            <p>أرسل صورة الفلتر أو المحطة ومكان المشكلة إن أمكن؛ تساعدنا على فهم الطلب قبل التواصل.</p>
            <dl class="contact-info-list">
                <div><dt>منطقة الخدمة</dt><dd>{{ $siteSettings['primary_service_area'] ?? 'حي المونسية وجميع أحياء الرياض' }}</dd></div>
                <div><dt>طريقة المتابعة</dt><dd>واتساب أو اتصال حسب اختيارك</dd></div>
                <div><dt>رقم التواصل</dt><dd><a href="{{ $siteSettings['phone_tel'] }}" dir="ltr">{{ $siteSettings['phone_display'] }}</a></dd></div>
            </dl>
            <div class="contact-next-steps">
                <strong>ماذا يحدث بعد الإرسال؟</strong>
                <ol>
                    <li><span>1</span>يُحفظ طلبك لمتابعته من الفريق.</li>
                    <li><span>2</span>تفتح رسالة واتساب مرتبة بالتفاصيل.</li>
                    <li><span>3</span>نكمل معك تفاصيل الجهاز وموعد الخدمة.</li>
                </ol>
            </div>
        </aside>
    </div>
</section>
@endsection
