@extends('layouts.app')
@section('body-class', 'store-page')
@section('content')
<x-page-hero eyebrow="تصنيف المنتجات" :title="$category->name" :description="$category->description ?: 'المنتجات المتوفرة حاليًا ضمن هذا التصنيف.'" />
<section class="store-catalog section-block"><div class="container-shell">
    <nav class="page-breadcrumbs" aria-label="مسار الصفحة"><a href="{{ route('home') }}">الرئيسية</a><span>/</span><a href="{{ route('products.index') }}">المنتجات</a><span>/</span><b>{{ $category->name }}</b></nav>
    @if(session('success'))<div class="store-notice" role="status">{{ session('success') }} <a href="{{ route('cart.show') }}">عرض السلة</a></div>@endif
    @if($products->isEmpty())<div class="empty-state"><h2>لا توجد منتجات متاحة في هذا التصنيف</h2><a class="button button-outline" href="{{ route('products.index') }}">كل المنتجات</a></div>@else<div class="product-grid">@foreach($products as $product)<x-product-card :product="$product" />@endforeach</div><div class="mt-8">{{ $products->links() }}</div>@endif
</div></section>
@endsection
