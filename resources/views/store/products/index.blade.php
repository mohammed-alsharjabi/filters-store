@extends('layouts.app')
@section('body-class', 'store-page')
@section('content')
<x-page-hero eyebrow="المتجر" title="منتجات فلاتر وتحلية المياه" description="تصفح المنتجات واطلب السعر والتوفر عبر واتساب. المنتجات التي تعتمد الإدارة أسعارها يمكن إضافتها مباشرة إلى السلة." />
<section class="store-catalog section-block">
    <div class="container-shell">
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
