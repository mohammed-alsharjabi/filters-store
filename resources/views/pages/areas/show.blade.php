@extends('layouts.app')
@section('content')
<x-page-hero eyebrow="منطقة خدمة" :title="'فلاتر وتحلية المياه في '.$area->name" :description="$area->excerpt" />
<section class="section-block"><div class="container-narrow prose-content"><h2>طلب خدمة في {{ $area->name }}</h2>{!! nl2br(e($area->content)) !!}<div class="inline-cta mt-10"><div><h3>احجز خدمة في {{ $area->name }}</h3><p>أرسل نوع الجهاز والخدمة المطلوبة وطريقة التواصل المفضلة.</p></div><a class="button button-primary" href="{{ route('quote',['area'=>$area->name]) }}">إرسال طلب</a></div></div></section>
<section class="section-block bg-stone-100"><div class="container-shell"><x-section-heading eyebrow="مرتبطة بالمنطقة" title="مشاريع منشورة في {{ $area->name }}" /> <div class="projects-grid mt-9">@forelse($projects as $project)<x-project-card :project="$project" />@empty<div class="empty-state"><p>لا توجد مشاريع موثقة منشورة لهذه المنطقة حتى الآن.</p></div>@endforelse</div><div class="mt-10">{{ $projects->links() }}</div></div></section>
<x-faqs :faqs="$area->faqs" />
@endsection
