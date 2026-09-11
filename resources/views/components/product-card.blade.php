@props(['product'])

<article class="product-card">
    <a class="product-card-media" href="{{ route('products.show', $product->slug) }}" aria-label="عرض {{ $product->name }}">
        <span class="image-shell" data-image-shell>
            <img src="{{ $product->imageUrl() }}" alt="{{ $product->featured_image_alt ?: $product->name }}" width="1200" height="1200" loading="lazy" decoding="async">
        </span>
        @if($product->compare_at_price)<span class="product-sale-badge">عرض</span>@endif
    </a>
    <div class="product-card-body">
        <small>{{ $product->category->name }}</small>
        <h2><a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a></h2>
        @if($product->tags->isNotEmpty())<div class="product-tags" aria-label="وسوم المنتج">@foreach($product->tags as $tag)<span>{{ $tag->name }}</span>@endforeach</div>@endif
        <div class="product-card-price"><strong>{{ number_format((float) $product->price, 2) }} <small>ر.س</small></strong>@if($product->compare_at_price)<del>{{ number_format((float) $product->compare_at_price, 2) }} ر.س</del>@endif</div>
        <span @class(['stock-state', 'is-available' => $product->isAvailable(), 'is-unavailable' => ! $product->isAvailable()])>{{ $product->isAvailable() ? 'متوفر للطلب' : 'غير متوفر حاليًا' }}</span>
        <div class="product-card-actions">
            @if($product->isAvailable())
                <a class="button product-whatsapp-button" href="{{ route('products.whatsapp', $product->slug) }}">الطلب واتساب</a>
                <form method="POST" action="{{ route('cart.add', $product->slug) }}">@csrf<input type="hidden" name="quantity" value="1"><button class="button button-primary" type="submit">أضف للسلة</button></form>
            @else
                <button class="button button-outline" type="button" disabled>الطلب واتساب</button>
                <button class="button button-primary" type="button" disabled>أضف للسلة</button>
            @endif
        </div>
    </div>
</article>
