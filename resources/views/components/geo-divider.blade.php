@props(['variant' => 'chevron'])

<div {{ $attributes->class(['geo-divider', 'geo-divider-'.$variant]) }} aria-hidden="true">
    <span class="geo-divider-back"></span>
    <span class="geo-divider-front"></span>
</div>
