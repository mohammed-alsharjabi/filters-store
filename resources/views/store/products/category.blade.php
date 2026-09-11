@extends('layouts.app')
@section('body-class', 'store-page')
@section('content')
<section class="store-catalog"><div class="store-shell">
    <header class="store-catalog-header">
        <nav class="page-breadcrumbs" aria-label="مسار الصفحة"><a href="{{ route('home') }}">الرئيسية</a><span>/</span><a href="{{ route('products.index') }}">المنتجات</a><span>/</span><b>{{ $category->name }}</b></nav>
        <div><div><span class="store-kicker">تصنيف المنتجات</span><h1>{{ $category->name }}</h1><p>{{ $category->description ?: 'المنتجات المتوفرة حاليًا ضمن هذا التصنيف.' }}</p></div><a class="store-cart-shortcut" href="{{ route('cart.show') }}" aria-label="فتح سلة المشتريات"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h2l1.7 9.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 8H7M10 20h.01M18 20h.01" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><span>السلة</span></a></div>
    </header>
    @if(session('success'))<div class="store-notice" role="status">{{ session('success') }} <a href="{{ route('cart.show') }}">عرض السلة</a></div>@endif
    @if($products->isEmpty())<div class="empty-state"><h2>لا توجد منتجات متاحة في هذا التصنيف</h2><a class="button button-outline" href="{{ route('products.index') }}">كل المنتجات</a></div>@else<div class="product-grid">@foreach($products as $product)<x-product-card :product="$product" />@endforeach</div><div class="mt-8">{{ $products->links() }}</div>@endif
</div></section>
@endsection
