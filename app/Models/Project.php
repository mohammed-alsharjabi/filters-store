<?php

namespace App\Models;

use App\Models\Concerns\HasArabicSlug;
use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasArabicSlug, HasSeo;

    protected $fillable = ['service_id', 'area_id', 'title', 'slug', 'excerpt', 'description', 'video_url', 'video_title', 'video_thumbnail', 'video_duration_seconds', 'completed_at', 'is_featured', 'is_published', 'status', 'published_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'date', 'is_featured' => 'boolean', 'is_published' => 'boolean', 'published_at' => 'datetime'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('sort_order');
    }

    public function routePrefix(): string
    {
        return 'المشاريع';
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
