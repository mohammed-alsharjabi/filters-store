@php($seo=['title'=>'الصفحة غير موجودة | '.$siteSettings['site_name'],'description'=>'تعذر العثور على الصفحة المطلوبة.','robots'=>'noindex,follow','canonical'=>url()->current(),'schemas'=>[]])
@extends('layouts.app')
@section('content')
<section class="error-page"><div class="container-shell"><p class="error-code">404</p><h1>هذه الصفحة ليست هنا</h1><p>ربما تغيّر الرابط أو كُتب بطريقة غير صحيحة. يمكنك العودة إلى الرئيسية أو استعراض الخدمات.</p><div><a class="button button-primary" href="{{ route('home') }}">العودة للرئيسية</a><a class="button button-outline" href="{{ route('services.index') }}">عرض الخدمات</a></div></div></section>
@endsection
