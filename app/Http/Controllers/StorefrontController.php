<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class StorefrontController extends Controller
{
    public function index(): View
    {
        $products = Product::published()->with(['category', 'mediaAsset', 'tags' => fn ($query) => $query->where('is_active', true)])
            ->orderByDesc('is_featured')->orderBy('sort_order')->latest('published_at')->paginate(12);
        $categories = ProductCategory::query()->where('is_active', true)
            ->whereHas('products', fn ($query) => $query->published())
            ->orderBy('sort_order')->get();
        $seo = Seo::page('منتجات فلاتر وتحلية المياه بالرياض', 'تصفح منتجات فلاتر وتحلية المياه المتوفرة وأسعارها ومخزونها، وأضف المنتج للسلة أو اطلبه مباشرة عبر واتساب.', null, $this->crumbs(['المنتجات' => route('products.index')]));
        $seo = Seo::paginate($products->isEmpty() ? Seo::noindex($seo) : $seo, $products);

        return view('store.products.index', compact('products', 'categories', 'seo'));
    }

    public function category(string $slug): View
    {
        $category = ProductCategory::query()->where('slug', $slug)->where('is_active', true)->with('seo')->firstOrFail();
        $products = $category->products()->published()->with(['category', 'mediaAsset', 'tags' => fn ($query) => $query->where('is_active', true)])
            ->orderByDesc('is_featured')->orderBy('sort_order')->paginate(12);
        $seo = Seo::page($category->name.' | منتجات فلاتر المياه', $category->description ?: 'منتجات '.$category->name.' المتوفرة في متجر فلاتر وتحلية المياه بالرياض.', $category, $this->crumbs(['المنتجات' => route('products.index'), $category->name => url()->current()]));
        $seo = Seo::paginate($products->isEmpty() ? Seo::noindex($seo) : $seo, $products);

        return view('store.products.category', compact('category', 'products', 'seo'));
    }

    public function show(string $slug): View
    {
        $product = Product::published()->where('slug', $slug)->with(['category', 'mediaAsset', 'tags' => fn ($query) => $query->where('is_active', true), 'seo'])->firstOrFail();
        $related = Product::published()->whereKeyNot($product->id)->where('product_category_id', $product->product_category_id)
            ->with(['category', 'mediaAsset', 'tags' => fn ($query) => $query->where('is_active', true)])->orderByDesc('is_featured')->limit(4)->get();
        $seo = Seo::page($product->name.' | متجر فلاتر المياه', $product->excerpt ?: strip_tags((string) $product->description), $product, $this->crumbs([
            'المنتجات' => route('products.index'),
            $product->category->name => route('products.category', $product->category->slug),
            $product->name => url()->current(),
        ]));

        return view('store.products.show', compact('product', 'related', 'seo'));
    }

    private function crumbs(array $items): array
    {
        return [['name' => 'الرئيسية', 'url' => route('home')], ...collect($items)->map(fn ($url, $name) => ['name' => $name, 'url' => $url])->values()->all()];
    }
}
