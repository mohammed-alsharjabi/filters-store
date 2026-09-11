@extends('layouts.app')
@section('content')
<x-page-hero :eyebrow="$project->service->name.' · '.$project->area->name" :title="$project->title" :description="$project->excerpt" />
<section class="section-block"><div class="container-shell"><div class="project-meta"><div><small>الخدمة</small><a href="{{ route('services.show',$project->service->slug) }}">{{ $project->service->name }}</a></div><div><small>المنطقة</small><a href="{{ route('areas.show',$project->area->slug) }}">{{ $project->area->name }}</a></div>@if($project->completed_at)<div><small>تاريخ الإنجاز</small><strong>{{ $project->completed_at->translatedFormat('F Y') }}</strong></div>@endif</div>
<article class="prose-content container-narrow mt-12">{!! nl2br(e($project->description)) !!}</article>
@if($project->images->isNotEmpty())<div class="project-gallery mt-12">@foreach($project->images as $image)<figure><x-responsive-image :image="$image" :alt="$image->alt_text ?: $project->title" :loading="$loop->first ? 'eager' : 'lazy'" sizes="(max-width: 700px) 100vw, 50vw" /><figcaption>{{ $image->caption ?: $image->alt_text ?: $project->title }}</figcaption></figure>@endforeach</div>@endif
<div class="inline-cta mt-12"><div><h2>هل تحتاج خدمة مشابهة؟</h2><p>أرسل صورة الجهاز وتفاصيل الطلب داخل الرياض.</p></div><a class="button button-primary" href="{{ route('quote',['service'=>$project->service_id]) }}">احجز الآن</a></div></div></section>
@endsection
