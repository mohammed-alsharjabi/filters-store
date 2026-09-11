<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceImage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_id', 'original_name', 'file_name', 'source_folder', 'original_path',
        'optimized_path', 'content_hash', 'title', 'alt_text', 'caption',
        'original_width', 'original_height', 'original_file_size', 'width', 'height',
        'mime_type', 'file_size', 'variants', 'quality_score', 'quality_status',
        'sort_order', 'is_cover', 'processing_status', 'processing_notes',
    ];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'is_cover' => 'boolean',
            'quality_score' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function getPathAttribute(): ?string
    {
        return $this->optimized_path;
    }

    public function getQualityLabelAttribute(): string
    {
        return match ($this->quality_status) {
            'excellent' => 'ممتازة',
            'good' => 'جيدة',
            'needs_improvement' => 'محسّنة مع ملاحظات',
            'weak' => 'ضعيفة وتحتاج بديلًا',
            default => 'بانتظار الفحص',
        };
    }

    public function variant(string $role, string $format = 'webp'): ?array
    {
        return collect($this->variants[$format] ?? [])->firstWhere('role', $role);
    }
}
