@props(['product'])

<article class="product-card">
    <a class="product-card-media" href="{{ route('products.show', $product->slug) }}" aria-label="عرض {{ $product->name }}">
        <span class="image-shell" data-image-shell>
            <img src="{{ $product->imageUrl() }}" alt="{{ $product->featured_image_alt ?: $product->name }}" width="1200" height="1200" loading="lazy" decoding="async">
        </span>
        @if($product->compare_at_price)<span class="product-sale-badge">عرض</span>@endif
    </a>
    <div class="product-card-body">
        <a class="product-card-category" href="{{ route('products.category', $product->category->slug) }}">{{ $product->category->name }}</a>
        <h2><a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a></h2>
        <div class="product-card-price">@if($product->price)<strong>{{ number_format((float) $product->price, 2) }} <small>ر.س</small></strong>@if($product->compare_at_price)<del>{{ number_format((float) $product->compare_at_price, 2) }} ر.س</del>@endif @else <strong class="price-on-request">السعر عند الطلب</strong>@endif</div>
        <div class="product-card-actions">
            <a class="button product-whatsapp-button" href="{{ route('products.whatsapp', $product->slug) }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.6a8.5 8.5 0 0 1-12.6 7.5L3 20.4l1.3-4.7A8.5 8.5 0 1 1 20.5 11.6Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8.3 7.3c.3-.4.7-.3.9.1l1 2c.2.4.1.7-.2 1l-.6.5c.7 1.4 1.8 2.5 3.2 3.2l.5-.6c.3-.3.6-.4 1-.2l2 1c.4.2.5.6.2.9-.5.8-1.3 1.2-2.2 1.1-3.4-.5-6.1-3.2-6.6-6.6-.1-.9.2-1.8.8-2.4Z" fill="currentColor"/></svg>
                <span>الطلب واتساب</span>
            </a>
            @if($product->isPurchasable())
                <form method="POST" action="{{ route('cart.add', $product->slug) }}">@csrf<input type="hidden" name="quantity" value="1"><button class="button button-primary" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h2l1.7 9.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 8H7M10 20h.01M18 20h.01" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><span>أضف للسلة</span></button></form>
            @else
                <button class="button button-primary" type="button" disabled title="يُفعّل بعد اعتماد السعر والتوفر"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h2l1.7 9.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 8H7M10 20h.01M18 20h.01" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><span>أضف للسلة</span></button>
            @endif
        </div>
    </div>
</article>
