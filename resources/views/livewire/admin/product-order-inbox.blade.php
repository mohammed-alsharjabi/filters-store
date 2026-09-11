<div>
    <div class="admin-actions order-admin-filters">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="رقم الطلب أو العميل أو الجوال">
        <select wire:model.live="status"><option value="">كل الحالات</option>@foreach(\App\Models\Order::STATUS_LABELS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
    </div>
    @error('status')<div class="notice-error">{{ $message }}</div>@enderror
    <div class="admin-orders-list">
        @forelse($orders as $order)
            <article class="admin-order-card">
                <header><div><strong dir="ltr">{{ $order->order_number }}</strong><small>{{ $order->created_at->format('Y-m-d H:i') }}</small></div><b>{{ number_format((float) $order->total, 2) }} ر.س</b></header>
                <div class="admin-order-customer"><span>{{ $order->customer_name }}</span><a href="tel:{{ $order->customer_phone }}" dir="ltr">{{ $order->customer_phone }}</a><span>{{ $order->area }}</span>@if($order->address)<span>{{ $order->address }}</span>@endif</div>
                <ul>@foreach($order->items as $item)<li><span>{{ $item->product_name }} × {{ $item->quantity }}</span><b>{{ number_format((float) $item->line_total, 2) }} ر.س</b></li>@endforeach</ul>
                @if($order->notes)<p class="admin-order-notes">{{ $order->notes }}</p>@endif
                <footer><span>الدفع: تحويل بنكي</span>@if($order->receipt_path)<a class="admin-receipt-link" href="{{ route('admin.orders.receipt', $order) }}">تنزيل سند التحويل</a>@else<span class="admin-receipt-pending">لم يُرفع السند بعد</span>@endif<select aria-label="حالة الطلب {{ $order->order_number }}" wire:change="updateStatus({{ $order->id }}, $event.target.value)" @disabled($order->status === 'cancelled')>@foreach(\App\Models\Order::STATUS_LABELS as $value => $label)<option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>@endforeach</select></footer>
            </article>
        @empty
            <div class="empty-state">لا توجد طلبات منتجات حتى الآن.</div>
        @endforelse
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
</div>
