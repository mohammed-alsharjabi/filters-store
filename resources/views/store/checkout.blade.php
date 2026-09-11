@extends('layouts.app')
@section('body-class', 'checkout-page')
@push('dataLayer')<script>window.dataLayer.push(@js($dataLayerEvent));</script>@endpush
@section('content')
<x-page-hero eyebrow="خطوة أخيرة" title="إتمام الطلب" description="أدخل بيانات التواصل ثم اختر إرسال الطلب كاملًا إلى واتساب أو تسجيله داخل المتجر للتحويل البنكي." />
<section class="checkout-section section-block"><div class="container-shell checkout-layout">
    <form class="checkout-form" method="POST" action="{{ route('checkout.submit') }}" data-checkout-form>@csrf
        <div class="checkout-form-head"><h2>بيانات العميل</h2><p>لا تحتاج إلى إنشاء حساب.</p></div>
        @error('cart')<div class="notice-error full-field">{{ $message }}</div>@enderror
        <label><span>الاسم الكامل</span><input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="120">@error('name')<small>{{ $message }}</small>@enderror</label>
        <label><span>رقم الجوال</span><input type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" dir="ltr" required placeholder="05xxxxxxxx">@error('phone')<small>{{ $message }}</small>@enderror</label>
        <label><span>الحي</span><input type="text" name="area" value="{{ old('area') }}" autocomplete="address-level3" required maxlength="120">@error('area')<small>{{ $message }}</small>@enderror</label>
        <label><span>العنوان التفصيلي — اختياري</span><input type="text" name="address" value="{{ old('address') }}" autocomplete="street-address" maxlength="500">@error('address')<small>{{ $message }}</small>@enderror</label>
        <label class="full-field"><span>ملاحظات — اختياري</span><textarea name="notes" rows="4" maxlength="2000">{{ old('notes') }}</textarea>@error('notes')<small>{{ $message }}</small>@enderror</label>
        <label class="checkout-terms full-field"><input type="checkbox" name="terms" value="1" @checked(old('terms')) required><span>أوافق على <a href="{{ route('terms') }}" target="_blank">الشروط والأحكام</a> وأفهم أن الدفع بالتحويل البنكي فقط.</span>@error('terms')<small>{{ $message }}</small>@enderror</label>
        <input type="text" name="website" tabindex="-1" autocomplete="off" class="honeypot" aria-hidden="true">
        <div class="checkout-actions full-field"><button class="button product-whatsapp-button" type="submit" name="action" value="whatsapp">إرسال الطلب إلى واتساب</button><button class="button button-primary" type="submit" name="action" value="store">تسجيل الطلب في المتجر</button></div>
    </form>
    <aside class="checkout-summary"><h2>تفاصيل الطلب</h2><ul>@foreach($items as $item)<li><span>{{ $item['product']->name }} <small>× {{ $item['quantity'] }}</small></span><b>{{ number_format($item['line_cents']/100, 2) }} ر.س</b></li>@endforeach</ul><div><span>الإجمالي</span><strong>{{ number_format($subtotalCents/100, 2) }} ر.س</strong></div><section class="bank-method"><b>طريقة الدفع</b><strong>تحويل بنكي فقط</strong><p>بعد تسجيل الطلب سيظهر رقم الطلب وبيانات التحويل، ويمكنك رفع صورة السند أو ملف PDF من صفحة تأكيد الطلب.</p></section></aside>
</div></section>
@endsection
