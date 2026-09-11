@extends('layouts.app')
@section('body-class', 'product-detail-page')
@push('head')
@if($product->price)<meta property="product:price:amount" content="{{ $product->price }}">
<meta property="product:price:currency" content="{{ $product->currency }}">
<meta property="product:availability" content="{{ $product->availabilityForFeed() }}">@endif
@endpush
@push('dataLayer')
<script>window.dataLayer.push(@js(['event' => 'view_item', 'ecommerce' => ['currency' => $product->currency, 'value' => (float) ($product->price ?? 0), 'items' => [['item_id' => $product->sku ?: (string) $product->id, 'item_name' => $product->name, 'item_brand' => $product->brand, 'item_category' => $product->category->name, 'price' => (float) ($product->price ?? 0), 'quantity' => 1]]]]));</script>
@endpush
@section('content')
<section class="product-detail section-block">
    <div class="container-shell">
        <nav class="page-breadcrumbs" aria-label="مسار الصفحة"><a href="{{ route('home') }}">الرئيسية</a><span>/</span><a href="{{ route('products.index') }}">المنتجات</a><span>/</span><a href="{{ route('products.category', $product->category->slug) }}">{{ $product->category->name }}</a><span>/</span><b>{{ $product->name }}</b></nav>
        @if(session('success'))<div class="store-notice" role="status">{{ session('success') }} <a href="{{ route('cart.show') }}">عرض السلة</a></div>@endif
        @error('cart')<div class="notice-error">{{ $message }}</div>@enderror
        <div class="product-detail-grid">
            <figure class="product-detail-media"><span class="image-shell" data-image-shell><img src="{{ $product->imageUrl() }}" alt="{{ $product->featured_image_alt ?: $product->name }}" width="1400" height="1400" loading="eager" decoding="async" fetchpriority="high"></span>@if($product->featured_image_caption)<figcaption>{{ $product->featured_image_caption }}</figcaption>@endif</figure>
            <article class="product-detail-copy">
                <a class="product-category-link" href="{{ route('products.category', $product->category->slug) }}">{{ $product->category->name }}</a>
                <h1>{{ $product->name }}</h1>
                @if($product->excerpt)<p class="product-lede">{{ $product->excerpt }}</p>@endif
                @if($product->tags->isNotEmpty())<div class="product-tags" aria-label="وسوم المنتج">@foreach($product->tags as $tag)<span>{{ $tag->name }}</span>@endforeach</div>@endif
                <div class="product-detail-price">@if($product->price)<strong>{{ number_format((float) $product->price, 2) }} <small>ر.س</small></strong>@if($product->compare_at_price)<del>{{ number_format((float) $product->compare_at_price, 2) }} ر.س</del>@endif @else <strong class="price-on-request">السعر عند الطلب</strong>@endif</div>
                <span @class(['stock-state', 'is-available' => $product->isPurchasable() || ! $product->price, 'is-unavailable' => $product->price && ! $product->isAvailable()])>{{ ! $product->price ? 'تواصل لتأكيد السعر والتوفر' : ($product->isAvailable() ? 'متوفر للطلب' : 'نفد المخزون') }}</span>
                @if($product->isPurchasable())
                    <form class="product-buy-box" method="POST" action="{{ route('cart.add', $product->slug) }}">@csrf<label><span>الكمية</span><input type="number" name="quantity" value="1" min="1" max="{{ $product->availableQuantity() }}" inputmode="numeric"></label><div><a class="button product-whatsapp-button" href="{{ route('products.whatsapp', $product->slug) }}">الطلب واتساب</a><button class="button button-primary" type="submit">أضف للسلة</button></div></form>
                @else
                    <div class="product-buy-box product-inquiry-box"><p>لم يعتمد السعر أو المخزون لهذا المنتج بعد.</p><div><a class="button product-whatsapp-button" href="{{ route('products.whatsapp', $product->slug) }}">اطلب السعر عبر واتساب</a><button class="button button-primary" type="button" disabled>أضف للسلة</button></div></div>
                @endif
                <dl class="product-facts">@if($product->sku)<div><dt>رمز المنتج</dt><dd dir="ltr">{{ $product->sku }}</dd></div>@endif @if($product->brand)<div><dt>العلامة التجارية</dt><dd>{{ $product->brand }}</dd></div>@endif<div><dt>الدفع</dt><dd>تحويل بنكي فقط للطلبات المسجلة</dd></div></dl>
            </article>
        </div>
        @if($product->description)<article class="product-description prose-content"><h2>تفاصيل المنتج</h2>{!! nl2br(e($product->description)) !!}</article>@endif
    </div>
</section>
@if($related->isNotEmpty())<section class="related-products section-block"><div class="container-shell"><x-section-heading title="منتجات مرتبطة" subtitle="منتجات أخرى من التصنيف نفسه" /><div class="product-grid">@foreach($related as $item)<x-product-card :product="$item" />@endforeach</div></div></section>@endif
@endsection
