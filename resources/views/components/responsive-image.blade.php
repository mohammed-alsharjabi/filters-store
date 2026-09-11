@props(['image','alt'=>null,'loading'=>'lazy','sizes'=>'(max-width: 700px) 100vw, 50vw','class'=>'','fetchpriority'=>'auto','variant'=>'gallery'])
@php
    $variants = $image->variants ?? [];
    $filter = function($items) use ($variant) {
        $items = collect($items);
        if (!$items->contains(fn($item) => isset($item['role']))) return $items->unique('width')->sortBy('width')->values();
        return $items->filter(fn($item) => $variant === 'cover' ? str_starts_with($item['role'] ?? '', 'cover_') : ($variant === 'thumbnail' ? ($item['role'] ?? '') === 'thumbnail' : in_array($item['role'] ?? '', ['gallery','medium','tablet','mobile'], true)))->unique('width')->sortBy('width')->values();
    };
    $webpItems = $filter($variants['webp'] ?? []);
    $avifItems = $filter($variants['avif'] ?? []);
    $jpegItems = $filter($variants['jpeg'] ?? []);
    $imageVersion = $image->updated_at?->timestamp ?? 1;
    $versionedAsset = fn($path) => asset('storage/'.$path).'?v='.$imageVersion;
    $srcset = fn($items) => $items->map(fn($item) => $versionedAsset($item['path']).' '.$item['width'].'w')->implode(', ');
    $webp = $srcset($webpItems);
    $avif = $srcset($avifItems);
    $jpeg = $srcset($jpegItems);
    $fallback = $jpegItems->last() ?: $webpItems->last();
    $width = $fallback['width'] ?? ($image->width ?: 1200);
    $height = $fallback['height'] ?? ($image->height ?: 800);
    $path = $fallback['path'] ?? $image->path;
@endphp
<span class="image-shell is-loading {{ $class }}" data-image-shell>
    <picture>
        @if($avif)<source type="image/avif" srcset="{{ $avif }}" sizes="{{ $sizes }}">@endif
        @if($webp)<source type="image/webp" srcset="{{ $webp }}" sizes="{{ $sizes }}">@endif
        <img src="{{ $versionedAsset($path) }}" @if($jpeg ?: $webp) srcset="{{ $jpeg ?: $webp }}" sizes="{{ $sizes }}" @endif alt="{{ $alt ?: ($image->alt_text ?: 'صورة خدمة فلاتر وتحلية المياه') }}" width="{{ $width }}" height="{{ $height }}" loading="{{ $loading }}" decoding="async" fetchpriority="{{ $fetchpriority }}">
    </picture>
    <span class="image-fallback" aria-hidden="true">تعذر تحميل الصورة</span>
</span>
