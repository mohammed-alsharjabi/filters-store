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
<section class="product-detail">
    <div class="product-detail-shell">
        <nav class="page-breadcrumbs product-detail-breadcrumbs" aria-label="مسار الصفحة"><a href="{{ route('home') }}">الرئيسية</a><span>/</span><a href="{{ route('products.index') }}">المنتجات</a><span>/</span><a href="{{ route('products.category', $product->category->slug) }}">{{ $product->category->name }}</a><span>/</span><b>{{ $product->name }}</b></nav>

        @if(session('success'))<div class="store-notice" role="status">{{ session('success') }} <a href="{{ route('cart.show') }}">عرض السلة</a></div>@endif
        @error('cart')<div class="notice-error">{{ $message }}</div>@enderror

        <div class="product-detail-grid">
            <figure class="product-detail-media">
                <span class="image-shell" data-image-shell><img src="{{ $product->imageUrl() }}" alt="{{ $product->featured_image_alt ?: $product->name }}" width="1400" height="1400" loading="eager" decoding="async" fetchpriority="high"></span>
                @if($product->featured_image_caption)<figcaption>{{ $product->featured_image_caption }}</figcaption>@endif
            </figure>

            <article class="product-detail-copy">
                <div class="product-status-row">
                    <a class="product-category-link" href="{{ route('products.category', $product->category->slug) }}">{{ $product->category->name }}</a>
                    <span @class(['stock-state', 'is-available' => $product->isPurchasable() || ! $product->price, 'is-unavailable' => $product->price && ! $product->isAvailable()])>{{ ! $product->price ? 'التوفر يؤكد عند التواصل' : ($product->isAvailable() ? 'متوفر للطلب' : 'نفد المخزون') }}</span>
                </div>

                <h1>{{ $product->name }}</h1>
                @if($product->brand)<p class="product-brand">العلامة التجارية <strong>{{ $product->brand }}</strong></p>@endif

                <div class="product-detail-price">
                    @if($product->price)
                        <strong>{{ number_format((float) $product->price, 2) }} <small>ر.س</small></strong>
                        @if($product->compare_at_price)<del>{{ number_format((float) $product->compare_at_price, 2) }} ر.س</del>@endif
                    @else
                        <strong class="price-on-request">السعر عند الطلب</strong>
                    @endif
                </div>

                @if($product->excerpt)<p class="product-lede">{{ $product->excerpt }}</p>@endif

                @if($product->tags->isNotEmpty())
                    <div class="product-tags product-feature-tags" aria-label="مواصفات المنتج">@foreach($product->tags as $tag)<span>{{ $tag->name }}</span>@endforeach</div>
                @endif

                <div class="product-assurance-row" aria-label="معلومات الطلب">
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.6a8.5 8.5 0 0 1-12.6 7.5L3 20.4l1.3-4.7A8.5 8.5 0 1 1 20.5 11.6Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M8.3 7.3c.3-.4.7-.3.9.1l1 2c.2.4.1.7-.2 1l-.6.5c.7 1.4 1.8 2.5 3.2 3.2l.5-.6c.3-.3.6-.4 1-.2l2 1c.4.2.5.6.2.9-.5.8-1.3 1.2-2.2 1.1-3.4-.5-6.1-3.2-6.6-6.6-.1-.9.2-1.8.8-2.4Z" fill="currentColor"/></svg>طلب مباشر</span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M3 10h18M7 15h3" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>تحويل بنكي</span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="9" r="2.2" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>داخل الرياض</span>
                </div>

                <div class="product-info-disclosures">
                    <details class="product-info-disclosure" open>
                        <summary><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6V3Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M14 3v4h4M9 12h6M9 16h6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>الوصف</span><b aria-hidden="true">⌄</b></summary>
                        <div><p>{{ $product->description ?: $product->excerpt }}</p></div>
                    </details>
                    <details class="product-info-disclosure">
                        <summary><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M7 3v4M17 3v4M5 7v13h14V7" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M8 11h3M13 11h3M8 15h3M13 15h3" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>معلومات المنتج</span><b aria-hidden="true">⌄</b></summary>
                        <div><dl class="product-facts">@if($product->sku)<div><dt>رمز المنتج</dt><dd dir="ltr">{{ $product->sku }}</dd></div>@endif @if($product->brand)<div><dt>العلامة التجارية</dt><dd>{{ $product->brand }}</dd></div>@endif<div><dt>التصنيف</dt><dd>{{ $product->category->name }}</dd></div><div><dt>الحالة</dt><dd>{{ $product->condition === 'new' ? 'جديد' : $product->condition }}</dd></div></dl></div>
                    </details>
                    <details class="product-info-disclosure">
                        <summary><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h11v10H3V6ZM14 9h4l3 3v4h-7V9Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="7" cy="18" r="2" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="18" cy="18" r="2" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>الطلب والدفع</span><b aria-hidden="true">⌄</b></summary>
                        <div><p>يُراجع الطلب والتوفر أولًا، ويكون الدفع بالتحويل البنكي فقط بعد تأكيد التفاصيل مع العميل.</p></div>
                    </details>
                </div>
            </article>
        </div>
    </div>
</section>

@if($related->isNotEmpty())
<section class="related-products">
    <div class="store-shell">
        <header class="related-products-heading"><span>قد يناسبك أيضًا</span><h2>منتجات مرتبطة</h2></header>
        <div class="product-grid">@foreach($related as $item)<x-product-card :product="$item" />@endforeach</div>
    </div>
</section>
@endif

<aside class="product-purchase-dock" id="purchase-actions" aria-label="إجراءات طلب المنتج">
    <div class="product-purchase-dock-inner">
        @if($product->isPurchasable())
            <form class="product-dock-form" method="POST" action="{{ route('cart.add', $product->slug) }}">@csrf
                <label class="product-quantity-stepper" data-quantity-stepper><span class="sr-only">الكمية</span><button type="button" data-quantity-change="-1" aria-label="تقليل الكمية">−</button><input type="number" name="quantity" value="1" min="1" max="{{ $product->availableQuantity() }}" inputmode="numeric" aria-label="الكمية"><button type="button" data-quantity-change="1" aria-label="زيادة الكمية">+</button></label>
                <a class="product-dock-whatsapp" href="{{ route('products.whatsapp', $product->slug) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.6a8.5 8.5 0 0 1-12.6 7.5L3 20.4l1.3-4.7A8.5 8.5 0 1 1 20.5 11.6Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8.3 7.3c.3-.4.7-.3.9.1l1 2c.2.4.1.7-.2 1l-.6.5c.7 1.4 1.8 2.5 3.2 3.2l.5-.6c.3-.3.6-.4 1-.2l2 1c.4.2.5.6.2.9-.5.8-1.3 1.2-2.2 1.1-3.4-.5-6.1-3.2-6.6-6.6-.1-.9.2-1.8.8-2.4Z" fill="currentColor"/></svg><span>الطلب واتساب</span></a>
                <button class="product-dock-cart" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h2l1.7 9.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 8H7M10 20h.01M18 20h.01" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><span>أضف للسلة</span></button>
            </form>
        @else
            <div class="product-dock-form is-inquiry">
                <div class="product-dock-context"><strong>{{ $product->name }}</strong><small>السعر والتوفر عند التواصل</small></div>
                <a class="product-dock-whatsapp" href="{{ route('products.whatsapp', $product->slug) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.6a8.5 8.5 0 0 1-12.6 7.5L3 20.4l1.3-4.7A8.5 8.5 0 1 1 20.5 11.6Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8.3 7.3c.3-.4.7-.3.9.1l1 2c.2.4.1.7-.2 1l-.6.5c.7 1.4 1.8 2.5 3.2 3.2l.5-.6c.3-.3.6-.4 1-.2l2 1c.4.2.5.6.2.9-.5.8-1.3 1.2-2.2 1.1-3.4-.5-6.1-3.2-6.6-6.6-.1-.9.2-1.8.8-2.4Z" fill="currentColor"/></svg><span>اطلب السعر عبر واتساب</span></a>
                <button class="product-dock-cart" type="button" disabled title="يتاح بعد اعتماد السعر"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h2l1.7 9.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 8H7M10 20h.01M18 20h.01" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><span>أضف للسلة</span></button>
            </div>
        @endif
    </div>
</aside>
@endsection
