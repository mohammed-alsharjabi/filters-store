@extends('layouts.app')
@section('body-class', 'order-success-page')
@if($dataLayerEvent)@push('dataLayer')<script>window.dataLayer.push(@js($dataLayerEvent));</script>@endpush @endif
@section('content')
<section class="order-success section-block"><div class="container-narrow">
    @if(session('success'))<div class="store-notice" role="status">{{ session('success') }}</div>@endif
    @error('receipt')<div class="notice-error" role="alert">{{ $message }}</div>@enderror
    <div class="order-success-mark" aria-hidden="true">✓</div>
    <p class="eyebrow">تم تسجيل الطلب</p><h1>شكرًا، استلمنا طلبك</h1><p>رقم الطلب <strong dir="ltr">{{ $order->order_number }}</strong>. احتفظ به عند التواصل أو إرسال إشعار التحويل.</p>
    <div class="order-success-grid">
        <section class="order-receipt"><h2>ملخص الطلب</h2><ul>@foreach($order->items as $item)<li><span>{{ $item->product_name }} × {{ $item->quantity }}</span><b>{{ number_format((float) $item->line_total, 2) }} ر.س</b></li>@endforeach</ul><div><span>الإجمالي</span><strong>{{ number_format((float) $order->total, 2) }} ر.س</strong></div></section>
        <section class="bank-details"><h2>التحويل البنكي</h2><p>{{ $bank->get('bank_transfer_instructions') }}</p>
            @if($bank->filter(fn($value, $key) => $key !== 'bank_transfer_instructions' && filled($value))->isNotEmpty())
                <dl>@if($bank->get('bank_name'))<div><dt>البنك</dt><dd>{{ $bank->get('bank_name') }}</dd></div>@endif @if($bank->get('bank_account_name'))<div><dt>اسم الحساب</dt><dd>{{ $bank->get('bank_account_name') }}</dd></div>@endif @if($bank->get('bank_account_number'))<div><dt>رقم الحساب</dt><dd dir="ltr">{{ $bank->get('bank_account_number') }}</dd></div>@endif @if($bank->get('bank_iban'))<div><dt>الآيبان</dt><dd dir="ltr">{{ $bank->get('bank_iban') }}</dd></div>@endif</dl>
            @else
                <div class="bank-pending">سيرسل فريق المتجر بيانات الحساب المعتمدة عند مراجعة الطلب.</div>
            @endif
            <form class="receipt-upload-form" method="POST" action="{{ route('checkout.receipt.store', $order->public_token) }}" enctype="multipart/form-data">
                @csrf
                <label for="receipt"><strong>{{ $order->receipt_path ? 'استبدال سند التحويل' : 'إرفاق سند التحويل' }}</strong><span>صورة JPG أو PNG أو WebP أو ملف PDF — بحد أقصى 5 ميجابايت.</span></label>
                <input id="receipt" type="file" name="receipt" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                <button class="button button-primary" type="submit">{{ $order->receipt_path ? 'رفع سند جديد' : 'رفع السند' }}</button>
                @if($order->receipt_uploaded_at)<small>تم استلام السند بتاريخ {{ $order->receipt_uploaded_at->format('Y-m-d H:i') }} وحالة الطلب الآن: {{ \App\Models\Order::STATUS_LABELS[$order->status] ?? $order->status }}.</small>@endif
            </form>
        </section>
    </div>
    <div class="order-success-actions"><a class="button product-whatsapp-button" href="{{ $siteSettings['whatsapp_url'].'?text='.rawurlencode('مرحبًا، أود متابعة طلب المنتجات رقم '.$order->order_number) }}" target="_blank" rel="noopener">متابعة عبر واتساب</a><a class="button button-outline" href="{{ route('products.index') }}">العودة للمنتجات</a></div>
</div></section>
@endsection
