<?php

namespace App\Livewire\Admin;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ProductOrderInbox extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $status = '';

    public function updateStatus(int $orderId, string $status): void
    {
        $this->authorize('manage-content');
        validator(['status' => $status], ['status' => ['required', Rule::in(array_keys(Order::STATUS_LABELS))]])->validate();

        DB::transaction(function () use ($orderId, $status): void {
            $order = Order::query()->lockForUpdate()->with('items')->findOrFail($orderId);
            if ($order->status === 'cancelled' && $status !== 'cancelled') {
                $this->addError('status', 'لا يُعاد فتح الطلب الملغي تلقائيًا لحماية دقة المخزون. أنشئ طلبًا جديدًا بدلًا منه.');

                return;
            }
            if ($status === 'cancelled' && $order->status !== 'cancelled') {
                foreach ($order->items as $item) {
                    if (! $item->product_id) {
                        continue;
                    }
                    $product = Product::withTrashed()->lockForUpdate()->find($item->product_id);
                    if (! $product || ! $product->track_stock) {
                        continue;
                    }
                    $product->increment('stock_quantity', $item->quantity);
                    $product->refresh();
                    InventoryMovement::query()->create([
                        'product_id' => $product->id,
                        'order_id' => $order->id,
                        'type' => 'cancellation_restore',
                        'quantity_change' => $item->quantity,
                        'balance_after' => $product->stock_quantity,
                        'reason' => 'إلغاء الطلب '.$order->order_number,
                    ]);
                }
            }
            $order->update(['status' => $status]);
        }, 3);

        if (! $this->getErrorBag()->has('status')) {
            session()->flash('success', 'حُدّثت حالة الطلب.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('manage-content');
        $orders = Order::query()->with('items')
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->search, fn ($query) => $query->where(fn ($sub) => $sub
                ->where('order_number', 'like', '%'.$this->search.'%')
                ->orWhere('customer_name', 'like', '%'.$this->search.'%')
                ->orWhere('customer_phone', 'like', '%'.$this->search.'%')))
            ->latest()->paginate(20);

        return view('livewire.admin.product-order-inbox', compact('orders'))
            ->layout('components.layouts.admin', ['title' => 'طلبات المنتجات']);
    }
}
