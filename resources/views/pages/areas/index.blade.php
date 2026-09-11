@extends('layouts.app')
@section('content')
<x-page-hero eyebrow="داخل مدينة الرياض" title="مناطق الخدمة" description="اختر نطاقك للاطلاع على المحتوى والمشاريع الحقيقية المرتبطة به عند توفرها." />
<section class="section-block"><div class="container-shell"><div class="area-cards">@forelse($areas as $area)<article><span aria-hidden="true">{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><h2><a href="{{ route('areas.show',$area->slug) }}">{{ $area->name }}</a></h2><p>{{ $area->excerpt }}</p><small>{{ $area->projects_count ? $area->projects_count.' مشروع منشور' : 'لا توجد مشاريع منشورة بعد' }}</small><a class="text-link" href="{{ route('areas.show',$area->slug) }}">تفاصيل المنطقة ←</a></article>@empty<p class="empty-state">لا توجد مناطق منشورة.</p>@endforelse</div><div class="mt-10">{{ $areas->links() }}</div></div></section>
@endsection
