@extends('layouts.app')
@section('content')
<x-page-hero eyebrow="أعمال موثقة" title="أعمال فلاتر وتحلية المياه بالرياض" description="لا تعرض هذه الصفحة إلا أعمال التركيب والصيانة المنشورة من لوحة التحكم والمرتبطة بصورها وخدمتها ومنطقتها." />
<section class="section-block"><div class="container-shell"><div class="projects-grid">@forelse($projects as $project)<x-project-card :project="$project" />@empty<div class="empty-state"><h2>لا توجد مشاريع منشورة بعد</h2><p>ستظهر المشاريع هنا بعد إضافة صور وتفاصيل تنفيذ حقيقية واعتمادها للنشر.</p></div>@endforelse</div><div class="mt-10">{{ $projects->links() }}</div></div></section>
@endsection
