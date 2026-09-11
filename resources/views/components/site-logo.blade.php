@props(['compact' => false, 'alt' => 'فلاتر وتحلية المياه بالرياض'])

@php
    $logoPath = 'brand/filters-store-logo.png';
    $logoFile = public_path($logoPath);
    $logoVersion = is_file($logoFile) ? filemtime($logoFile) : null;
@endphp

<img
    src="{{ asset($logoPath) }}{{ $logoVersion ? '?v='.$logoVersion : '' }}"
    alt="{{ $alt }}"
    width="2098"
    height="749"
    {{ $attributes->class($compact ? 'site-logo site-logo-compact' : 'site-logo') }}
>
