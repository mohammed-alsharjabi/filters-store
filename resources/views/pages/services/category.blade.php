@extends('layouts.app')
@section('content')
<x-page-hero eyebrow="تصنيف خدمة" :title="$category->name.' في الرياض'" :description="$category->excerpt" />
<section class="section-block"><div class="container-shell">@if($category->description)<div class="prose-content mb-10">{!! nl2br(e($category->description)) !!}</div>@endif<div class="cards-grid">@forelse($services as $service)<x-service-card :service="$service" />@empty<p class="empty-state">لا توجد خدمات منشورة في هذا التصنيف.</p>@endforelse</div><div class="mt-10">{{ $services->links() }}</div></div></section>
@endsection
