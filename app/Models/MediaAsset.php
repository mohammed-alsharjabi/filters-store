<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaAsset extends Model
{
    protected $fillable = [
        'source_key', 'name', 'path', 'alt_text', 'caption', 'usage_notes', 'mime_type',
        'width', 'height', 'file_size', 'content_hash', 'variants', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'width' => 'integer',
            'height' => 'integer',
            'file_size' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot(['context', 'sort_order'])
            ->orderByPivot('sort_order');
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_media_asset')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getOptimizedPathAttribute(): string
    {
        return $this->path;
    }

    public function variant(string $role, string $format = 'webp'): ?array
    {
        return collect($this->variants[$format] ?? [])->firstWhere('role', $role);
    }

    public function imageUrl(): string
    {
        return asset('storage/'.ltrim($this->path, '/'));
    }
}
