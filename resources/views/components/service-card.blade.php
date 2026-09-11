@props(['service'])
@php($cardImage = $service->relationLoaded('images') ? ($service->images->firstWhere('is_cover', true) ?: $service->images->first()) : null)
<article class="content-card service-card">
    @if($service->featured_image || $cardImage)<a class="card-image" href="{{ route('services.show', $service->slug) }}"><x-service-cover :service="$service" :image="$cardImage" sizes="(max-width: 780px) 100vw, 25vw" /></a>@endif
    <div class="card-copy"><p class="card-kicker">{{ $service->category?->name }}</p><h3><a href="{{ route('services.show', $service->slug) }}">{{ $service->name }}</a></h3><p>{{ $service->excerpt }}</p><a class="text-link" href="{{ route('services.show', $service->slug) }}">تفاصيل الخدمة <span aria-hidden="true">←</span></a></div>
</article>
