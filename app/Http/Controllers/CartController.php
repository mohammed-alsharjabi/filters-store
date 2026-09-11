<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Models\Product;
use App\Support\Seo;
use App\Support\StoreCart;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    public function show(StoreCart $cart): View
    {
        $items = $cart->items();
        $subtotalCents = $items->sum('line_cents');
        $seo = Seo::noindex(Seo::page('سلة المشتريات', 'راجع المنتجات والكميات قبل إكمال طلبك.'));

        return view('store.cart', compact('items', 'subtotalCents', 'seo'));
    }

    public function add(AddToCartRequest $request, string $slug, StoreCart $cart): RedirectResponse
    {
        $product = Product::purchasable()->where('slug', $slug)->firstOrFail();
        $cart->add($product, (int) $request->validated('quantity'));
        $event = ['event' => 'add_to_cart', 'ecommerce' => [
            'currency' => 'SAR',
            'value' => (float) $product->price * (int) $request->validated('quantity'),
            'items' => [[
                'item_id' => $product->sku ?: (string) $product->id,
                'item_name' => $product->name,
                'item_brand' => $product->brand,
                'price' => (float) $product->price,
                'quantity' => (int) $request->validated('quantity'),
            ]],
        ]];

        return back()->with('success', 'أُضيف المنتج إلى السلة.')->with('data_layer_events', [$event]);
    }

    public function update(UpdateCartRequest $request, StoreCart $cart): RedirectResponse
    {
        $cart->update($request->validated('quantities'));

        return back()->with('success', 'حُدّثت كميات السلة.');
    }

    public function remove(int $productId, StoreCart $cart): RedirectResponse
    {
        $cart->remove($productId);

        return back()->with('success', 'أُزيل المنتج من السلة.');
    }
}
