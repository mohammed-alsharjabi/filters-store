@extends('layouts.app')
@section('body-class', 'whatsapp-redirect-page')
@push('dataLayer')
<script>window.dataLayer.push(@js($dataLayerEvent));window.setTimeout(()=>window.location.assign(@js($whatsappUrl)),350);</script>
@endpush
@section('content')
<section class="whatsapp-bridge section-block"><div class="container-narrow"><div class="whatsapp-bridge-icon" aria-hidden="true">✓</div><h1>طلبك جاهز للإرسال</h1><p>سيُفتح واتساب مع تفاصيل المنتجات والأسعار الحالية. راجع الرسالة ثم أرسلها.</p><a class="button product-whatsapp-button" href="{{ $whatsappUrl }}" rel="nofollow">فتح واتساب الآن</a><a class="text-link" href="{{ route('products.index') }}">العودة للمتجر</a></div></section>
@endsection
