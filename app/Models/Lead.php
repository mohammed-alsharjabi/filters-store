<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    public const STATUSES = ['new', 'contacted', 'inspection', 'quoted', 'agreed', 'closed'];

    public const STATUS_LABELS = [
        'new' => 'جديد',
        'contacted' => 'تم التواصل',
        'inspection' => 'معاينة',
        'quoted' => 'عرض سعر',
        'agreed' => 'تم الاتفاق',
        'closed' => 'مغلق',
    ];

    protected $fillable = ['service_id', 'type', 'name', 'phone', 'area', 'area_size', 'preferred_contact', 'message', 'source_url', 'whatsapp_message', 'status', 'metadata', 'ip_hash'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'area_size' => 'decimal:2'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(LeadImage::class);
    }
}
