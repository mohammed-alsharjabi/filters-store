@extends('layouts.app')
@section('content')
<x-page-hero eyebrow="طلب منظم" title="احجز تركيب أو صيانة" description="أرسل بيانات التواصل والحي ونوع الجهاز أو الخدمة. لا يتضمن الإرسال تأكيدًا تلقائيًا للسعر أو الموعد." />
<section class="section-block"><div class="container-shell form-layout"><div><x-lead-form :services="$services" type="quote" :default-message="$estimateMessage" /></div><aside class="contact-panel"><p class="eyebrow eyebrow-light">تواصل مباشر</p><h2>تفضل الاتصال أو واتساب؟</h2><p>استخدم القناة الأنسب لإرسال صورة الجهاز وشرح الخدمة المطلوبة داخل الرياض.</p><a href="{{ $siteSettings['phone_tel'] }}"><small>اتصال</small><strong dir="ltr">{{ $siteSettings['phone_display'] }}</strong></a><a href="{{ $siteSettings['whatsapp_url'] }}" target="_blank" rel="noopener"><small>واتساب</small><strong>فتح المحادثة</strong></a><p class="panel-address">{{ $siteSettings['address'] }}</p></aside></div></section>
@endsection
