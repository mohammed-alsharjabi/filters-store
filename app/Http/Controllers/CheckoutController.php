<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Support\CheckoutService;
use App\Support\Seo;
use App\Support\SettingsRepository;
use App\Support\StoreCart;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CheckoutController extends Controller
{
    public function show(StoreCart $cart, CheckoutService $checkout): View|RedirectResponse
    {
        $items = $cart->items();
        if ($items->isEmpty()) {
            return redirect()->route('cart.show')->withErrors(['cart' => 'السلة فارغة.']);
        }
        $subtotalCents = $items->sum('line_cents');
        $dataLayerEvent = ['event' => 'begin_checkout', 'ecommerce' => [
            'currency' => 'SAR', 'value' => $subtotalCents / 100, 'items' => $checkout->analyticsItems($items),
        ]];
        $seo = Seo::noindex(Seo::page('إتمام الطلب', 'أدخل بيانات التواصل واختر إرسال الطلب عبر واتساب أو تسجيله للدفع بالتحويل البنكي.'));

        return view('store.checkout', compact('items', 'subtotalCents', 'dataLayerEvent', 'seo'));
    }

    public function submit(CheckoutOrderRequest $request, StoreCart $cart, CheckoutService $checkout): View|RedirectResponse
    {
        $customer = $request->safe()->only(['name', 'phone', 'area', 'address', 'notes']);
        if ($request->validated('action') === 'whatsapp') {
            $lines = $checkout->validatedLines();
            $message = $checkout->whatsappMessage($customer);
            $whatsappUrl = $checkout->whatsappUrl($message);
            $dataLayerEvent = ['event' => 'whatsapp_order', 'ecommerce' => [
                'currency' => 'SAR', 'value' => $lines->sum('line_cents') / 100, 'items' => $checkout->analyticsItems($lines),
            ]];
            $seo = Seo::noindex(Seo::page('إرسال الطلب إلى واتساب', 'جارٍ تجهيز طلب المنتجات وإرساله إلى واتساب.'));

            return view('store.whatsapp-redirect', compact('whatsappUrl', 'dataLayerEvent', 'seo'));
        }

        $order = $checkout->createOrder($customer, (string) $request->ip());
        $cart->clear();
        $event = ['event' => 'purchase', 'ecommerce' => [
            'transaction_id' => $order->order_number,
            'currency' => $order->currency,
            'value' => (float) $order->total,
            'items' => $order->items->map(fn ($item): array => [
                'item_id' => $item->sku ?: (string) $item->product_id,
                'item_name' => $item->product_name,
                'price' => (float) $item->unit_price,
                'quantity' => $item->quantity,
            ])->all(),
        ]];

        return redirect()->route('checkout.success', $order->public_token)->with('purchase_event', $event);
    }

    public function success(string $token, SettingsRepository $settings): View
    {
        $order = Order::query()->where('public_token', $token)->with('items')->firstOrFail();
        $dataLayerEvent = session()->pull('purchase_event');
        $bank = collect($settings->public())->only(['bank_name', 'bank_account_name', 'bank_account_number', 'bank_iban', 'bank_transfer_instructions']);
        $seo = Seo::noindex(Seo::page('تم تسجيل الطلب '.$order->order_number, 'تم تسجيل طلب المنتجات بنجاح وهو بانتظار التحويل البنكي.'));

        return view('store.order-success', compact('order', 'bank', 'dataLayerEvent', 'seo'));
    }

    public function productWhatsapp(string $slug, CheckoutService $checkout): View
    {
        $product = Product::published()->where('slug', $slug)->with('category')->firstOrFail();
        $message = implode("\n", [
            '*طلب منتج من موقع فلاتر وتحلية المياه بالرياض*',
            'المنتج: '.$product->name,
            'السعر: '.($product->price ? number_format((float) $product->price, 2).' ر.س' : 'عند الطلب'),
            'رابط المنتج: '.route('products.show', $product->slug),
        ]);
        $whatsappUrl = $checkout->whatsappUrl($message);
        $dataLayerEvent = ['event' => 'whatsapp_order', 'ecommerce' => [
            'currency' => 'SAR', 'value' => (float) ($product->price ?? 0), 'items' => [[
                'item_id' => $product->sku ?: (string) $product->id,
                'item_name' => $product->name,
                'item_brand' => $product->brand,
                'item_category' => $product->category?->name,
                'price' => (float) ($product->price ?? 0),
                'quantity' => 1,
            ]],
        ]];
        $seo = Seo::noindex(Seo::page('طلب '.$product->name.' عبر واتساب', 'جارٍ فتح واتساب لإرسال طلب المنتج.'));

        return view('store.whatsapp-redirect', compact('whatsappUrl', 'dataLayerEvent', 'seo'));
    }
}
