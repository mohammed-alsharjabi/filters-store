@extends('layouts.app')
@section('body-class', 'cart-page')
@section('content')
<x-page-hero eyebrow="المتجر" title="سلة المشتريات" description="راجع المنتجات والكميات. تُحسب الأسعار والإجمالي من قاعدة البيانات عند إكمال الطلب." />
<section class="store-cart section-block"><div class="container-shell">
    @if(session('success'))<div class="store-notice" role="status">{{ session('success') }}</div>@endif
    @error('cart')<div class="notice-error">{{ $message }}</div>@enderror
    @if($items->isEmpty())
        <div class="empty-state"><h2>سلتك فارغة</h2><p>أضف منتجًا متوفرًا ثم عد لإكمال الطلب.</p><a class="button button-primary" href="{{ route('products.index') }}">تصفح المنتجات</a></div>
    @else
        <div class="cart-layout">
            <form class="cart-lines" method="POST" action="{{ route('cart.update') }}">@csrf @method('PATCH')
                @foreach($items as $item)@php($product=$item['product'])<article class="cart-line">
                    <a class="cart-line-image" href="{{ route('products.show', $product->slug) }}"><img src="{{ $product->imageUrl() }}" alt="{{ $product->featured_image_alt ?: $product->name }}" width="220" height="220" loading="lazy"></a>
                    <div class="cart-line-copy"><small>{{ $product->category->name }}</small><h2><a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a></h2><strong>{{ number_format($item['unit_cents']/100, 2) }} ر.س</strong></div>
                    <label class="cart-quantity"><span>الكمية</span><input type="number" name="quantities[{{ $product->id }}]" value="{{ $item['quantity'] }}" min="0" max="{{ $product->availableQuantity() }}" inputmode="numeric">@error('quantities.'.$product->id)<small>{{ $message }}</small>@enderror</label>
                    <b class="cart-line-total">{{ number_format($item['line_cents']/100, 2) }} ر.س</b>
                    <button class="cart-remove" type="submit" form="remove-product-{{ $product->id }}">إزالة</button>
                </article>@endforeach
                <button class="button button-outline" type="submit">تحديث السلة</button>
            </form>
            @foreach($items as $item)<form id="remove-product-{{ $item['product']->id }}" method="POST" action="{{ route('cart.remove', $item['product']->id) }}" hidden>@csrf @method('DELETE')</form>@endforeach
            <aside class="cart-summary"><h2>ملخص الطلب</h2><div><span>المجموع</span><strong>{{ number_format($subtotalCents/100, 2) }} ر.س</strong></div><p>لا تُضاف رسوم أو أسعار مخفية. الدفع بالتحويل البنكي فقط بعد تسجيل الطلب.</p><a class="button button-primary" href="{{ route('checkout.show') }}">إكمال الطلب</a><a class="text-link" href="{{ route('products.index') }}">متابعة التسوق</a></aside>
        </div>
    @endif
</div></section>
@endsection
