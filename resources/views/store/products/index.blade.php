@extends('layouts.app')
@section('body-class', 'store-page')
@section('content')
<section class="store-catalog">
    <div class="store-shell">
        <header class="store-catalog-header">
            <nav class="page-breadcrumbs" aria-label="مسار الصفحة"><a href="{{ route('home') }}">الرئيسية</a><span>/</span><b>المنتجات</b></nav>
            <div><div><span class="store-kicker">المتجر</span><h1>منتجات فلاتر وتحلية المياه</h1><p>اختر المنتج المناسب واطلب السعر والتوفر مباشرة عبر واتساب.</p></div><a class="store-cart-shortcut" href="{{ route('cart.show') }}" aria-label="فتح سلة المشتريات"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h2l1.7 9.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 8H7M10 20h.01M18 20h.01" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><span>السلة</span></a></div>
        </header>
        @if($categories->isNotEmpty())
            <nav class="product-category-nav" aria-label="تصنيفات المنتجات"><a class="active" href="{{ route('products.index') }}">كل المنتجات</a>@foreach($categories as $category)<a href="{{ route('products.category', $category->slug) }}">{{ $category->name }} <small>{{ $category->products_count }}</small></a>@endforeach</nav>
        @endif
        @if(session('success'))<div class="store-notice" role="status">{{ session('success') }} <a href="{{ route('cart.show') }}">عرض السلة</a></div>@endif
        @error('cart')<div class="notice-error">{{ $message }}</div>@enderror
        @if($products->isEmpty())
            <div class="empty-state store-empty"><h2>لا توجد منتجات منشورة بعد</h2><p>ستظهر المنتجات هنا بعد اعتماد أسمائها وصورها من لوحة التحكم.</p><a class="button button-outline" href="{{ route('services.index') }}">تصفح الخدمات</a></div>
        @else
            <div class="product-grid">@foreach($products as $product)<x-product-card :product="$product" />@endforeach</div>
            <div class="mt-8">{{ $products->links() }}</div>
        @endif
    </div>
</section>
@endsection
