<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StoreCart
{
    private const SESSION_KEY = 'store.cart.v1';

    public function raw(): array
    {
        return collect(session(self::SESSION_KEY, []))
            ->mapWithKeys(fn ($quantity, $id): array => [(int) $id => max(1, min(99, (int) $quantity))])
            ->all();
    }

    public function items(): Collection
    {
        $cart = $this->raw();
        if ($cart === []) {
            return collect();
        }

        $products = Product::purchasable()->with('category')->whereIn('id', array_keys($cart))->get()->keyBy('id');
        $items = collect($cart)->map(function (int $quantity, int $id) use ($products): ?array {
            $product = $products->get($id);
            if (! $product) {
                return null;
            }

            $quantity = min($quantity, $product->availableQuantity());
            if ($quantity < 1) {
                return null;
            }

            $unitCents = $this->moneyToCents($product->price);

            return [
                'product' => $product,
                'quantity' => $quantity,
                'unit_cents' => $unitCents,
                'line_cents' => $unitCents * $quantity,
            ];
        })->filter()->values();

        session([self::SESSION_KEY => $items->mapWithKeys(fn (array $item): array => [$item['product']->id => $item['quantity']])->all()]);

        return $items;
    }

    public function add(Product $product, int $quantity): void
    {
        if (! $product->isPurchasable()) {
            throw ValidationException::withMessages(['cart' => 'هذا المنتج غير متوفر حاليًا.']);
        }

        $cart = $this->raw();
        $quantity = min(99, ($cart[$product->id] ?? 0) + max(1, $quantity));
        if ($product->track_stock && ! $product->allow_backorder && $quantity > $product->stock_quantity) {
            throw ValidationException::withMessages(['cart' => 'الكمية المطلوبة أكبر من المخزون المتاح.']);
        }
        $cart[$product->id] = $quantity;
        session([self::SESSION_KEY => $cart]);
    }

    public function update(array $quantities): void
    {
        $cart = $this->raw();
        $products = Product::purchasable()->whereIn('id', array_keys($cart))->get()->keyBy('id');

        foreach ($cart as $id => $currentQuantity) {
            $quantity = (int) ($quantities[$id] ?? $currentQuantity);
            if ($quantity <= 0) {
                unset($cart[$id]);

                continue;
            }
            $product = $products->get($id);
            if (! $product || ! $product->isAvailable()) {
                unset($cart[$id]);

                continue;
            }
            if ($product->track_stock && ! $product->allow_backorder && $quantity > $product->stock_quantity) {
                throw ValidationException::withMessages(['quantities.'.$id => 'الحد المتاح من '.$product->name.' هو '.$product->stock_quantity.'.']);
            }
            $cart[$id] = min(99, $quantity);
        }

        session([self::SESSION_KEY => $cart]);
    }

    public function remove(int $productId): void
    {
        $cart = $this->raw();
        unset($cart[$productId]);
        session([self::SESSION_KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function subtotalCents(): int
    {
        return $this->items()->sum('line_cents');
    }

    public function moneyToCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', ',');
    }
}
