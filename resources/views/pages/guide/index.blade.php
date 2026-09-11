@extends('layouts.app')
@section('content')
<x-page-hero eyebrow="محتوى إرشادي" title="مقالات فلاتر وتحلية المياه" description="معلومات تساعدك على اختيار الفلتر وفهم مراحل التحلية والصيانة والأعطال الشائعة قبل الحجز." />
<section class="section-block"><div class="container-shell"><div class="category-chips">@foreach($categories as $category)<span>{{ $category->name }} <b>{{ $category->articles_count }}</b></span>@endforeach</div><div class="articles-grid mt-10">@forelse($articles as $article)<x-article-card :article="$article" />@empty<p class="empty-state">لا توجد مقالات منشورة.</p>@endforelse</div><div class="mt-10">{{ $articles->links() }}</div></div></section>
@endsection
