<?php

namespace App\Support;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private readonly StoreCart $cart) {}

    public function createOrder(array $customer, string $ip): Order
    {
        return DB::transaction(function () use ($customer, $ip): Order {
            $lines = $this->validatedLines(lock: true);
            $totalCents = $lines->sum('line_cents');
            $order = Order::query()->create([
                'public_token' => (string) Str::uuid(),
                'order_number' => $this->nextOrderNumber(),
                'channel' => 'store',
                'status' => 'pending_transfer',
                'customer_name' => $customer['name'],
                'customer_phone' => $customer['phone'],
                'area' => $customer['area'],
                'address' => $customer['address'] ?? null,
                'notes' => $customer['notes'] ?? null,
                'subtotal' => $this->decimal($totalCents),
                'total' => $this->decimal($totalCents),
                'currency' => 'SAR',
                'payment_method' => 'bank_transfer',
                'source_url' => route('checkout.show'),
                'ip_hash' => hash('sha256', $ip.'|'.config('app.key')),
                'metadata' => ['pricing_source' => 'database', 'cart_version' => 1],
            ]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'image_path' => $product->featured_image,
                    'unit_price' => $this->decimal($line['unit_cents']),
                    'quantity' => $line['quantity'],
                    'line_total' => $this->decimal($line['line_cents']),
                ]);

                if ($product->track_stock) {
                    $product->update(['stock_quantity' => max(0, $product->stock_quantity - $line['quantity'])]);
                    $product->refresh();
                    InventoryMovement::query()->create([
                        'product_id' => $product->id,
                        'order_id' => $order->id,
                        'type' => 'sale',
                        'quantity_change' => -$line['quantity'],
                        'balance_after' => $product->stock_quantity,
                        'reason' => 'طلب '.$order->order_number,
                    ]);
                }
            }

            return $order->load('items');
        }, 3);
    }

    public function whatsappMessage(array $customer): string
    {
        $lines = $this->validatedLines();
        $settings = app(SettingsRepository::class)->public();
        $message = collect([
            '*طلب منتجات جديد من موقع '.$settings['site_name'].'*',
            'الاسم: '.$customer['name'],
            'الجوال: '.$customer['phone'],
            'الحي: '.$customer['area'],
            filled($customer['address'] ?? null) ? 'العنوان: '.$customer['address'] : null,
            '',
            '*المنتجات:*',
        ])->filter(fn ($line) => $line !== null);

        foreach ($lines as $index => $line) {
            $product = $line['product'];
            $message->push(($index + 1).'. '.$product->name.' × '.$line['quantity'].' — '.$this->cart->formatCents($line['line_cents']).' ر.س');
        }

        $message->push('');
        $message->push('*الإجمالي: '.$this->cart->formatCents($lines->sum('line_cents')).' ر.س*');
        $message->push('طريقة الدفع: تحويل بنكي');
        if (filled($customer['notes'] ?? null)) {
            $message->push('ملاحظات: '.$customer['notes']);
        }
        $message->push('رابط المتجر: '.route('products.index'));

        return $message->implode("\n");
    }

    public function whatsappUrl(string $message): string
    {
        $phone = preg_replace('/\D+/', '', (string) app(SettingsRepository::class)->public()['phone_e164']);

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    public function analyticsItems(Collection $lines): array
    {
        return $lines->map(fn (array $line): array => [
            'item_id' => $line['product']->sku ?: (string) $line['product']->id,
            'item_name' => $line['product']->name,
            'item_brand' => $line['product']->brand,
            'item_category' => $line['product']->category?->name,
            'price' => $line['unit_cents'] / 100,
            'quantity' => $line['quantity'],
        ])->values()->all();
    }

    public function validatedLines(bool $lock = false): Collection
    {
        $raw = $this->cart->raw();
        if ($raw === []) {
            throw ValidationException::withMessages(['cart' => 'السلة فارغة. أضف منتجًا قبل إكمال الطلب.']);
        }

        $query = Product::published()->with('category')->whereIn('id', array_keys($raw));
        if ($lock) {
            $query->lockForUpdate();
        }
        $products = $query->get()->keyBy('id');
        if ($products->count() !== count($raw)) {
            throw ValidationException::withMessages(['cart' => 'تغيّر توفر أحد المنتجات. راجع السلة ثم حاول مرة أخرى.']);
        }

        return collect($raw)->map(function (int $quantity, int $productId) use ($products): array {
            /** @var Product $product */
            $product = $products->get($productId);
            if (! $product->isAvailable() || ($product->track_stock && ! $product->allow_backorder && $quantity > $product->stock_quantity)) {
                throw ValidationException::withMessages(['cart' => 'الكمية المطلوبة من '.$product->name.' غير متوفرة حاليًا.']);
            }
            $unitCents = $this->cart->moneyToCents($product->price);

            return ['product' => $product, 'quantity' => $quantity, 'unit_cents' => $unitCents, 'line_cents' => $unitCents * $quantity];
        })->values();
    }

    private function nextOrderNumber(): string
    {
        do {
            $number = 'FS-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }

    private function decimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
