<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_LABELS = [
        'pending_transfer' => 'بانتظار التحويل',
        'payment_review' => 'مراجعة التحويل',
        'paid' => 'مدفوع',
        'processing' => 'قيد التجهيز',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
    ];

    protected $fillable = [
        'public_token', 'order_number', 'channel', 'status', 'customer_name', 'customer_phone',
        'area', 'address', 'notes', 'subtotal', 'total', 'currency', 'payment_method',
        'source_url', 'ip_hash', 'metadata',
    ];

    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'total' => 'decimal:2', 'metadata' => 'array'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
