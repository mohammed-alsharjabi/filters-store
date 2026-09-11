@props([
    'article',
    'image' => null,
    'alt' => null,
    'loading' => 'lazy',
    'fetchpriority' => 'auto',
    'variant' => 'thumbnail',
    'sizes' => '(max-width: 780px) 100vw, 33vw',
    'class' => '',
])

@if($article->featured_image)
    <span class="image-shell article-curated-image {{ $class }}" data-image-shell>
        <picture>
            <img
                src="{{ asset('storage/'.$article->featured_image) }}?v={{ $article->updated_at?->timestamp ?? 1 }}"
                alt="{{ $alt ?: ($article->featured_image_alt ?: $article->title) }}"
                width="1254"
                height="1254"
                loading="{{ $loading }}"
                decoding="async"
                fetchpriority="{{ $fetchpriority }}"
            >
        </picture>
        <span class="image-fallback" aria-hidden="true">تعذر تحميل الصورة</span>
    </span>
@elseif($image)
    <x-responsive-image :image="$image" :alt="$alt ?: ($image->alt_text ?: $article->title)" :variant="$variant" :loading="$loading" :fetchpriority="$fetchpriority" :sizes="$sizes" :class="$class" />
@endif
