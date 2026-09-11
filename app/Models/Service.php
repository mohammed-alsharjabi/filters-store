<?php

namespace App\Models;

use App\Models\Concerns\HasArabicSlug;
use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Service extends Model
{
    use HasArabicSlug, HasSeo;

    protected $fillable = ['service_category_id', 'parent_service_id', 'image_source_folder', 'name', 'slug', 'excerpt', 'content', 'types', 'use_cases', 'materials_details', 'advantages', 'disadvantages', 'price_factors', 'installation_steps', 'selection_tips', 'cta', 'featured_image', 'featured_image_alt', 'featured_image_caption', 'video_url', 'video_title', 'video_thumbnail', 'video_duration_seconds', 'price_from', 'price_to', 'price_unit', 'price_note', 'is_price_published', 'is_featured', 'is_popular', 'sort_order', 'is_active', 'status', 'published_at'];

    protected function casts(): array
    {
        return ['is_price_published' => 'boolean', 'is_featured' => 'boolean', 'is_popular' => 'boolean', 'is_active' => 'boolean', 'published_at' => 'datetime', 'price_from' => 'decimal:2', 'price_to' => 'decimal:2'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_service_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_service_id')->orderBy('sort_order')->orderBy('id');
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ServiceImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function processedImages(): HasMany
    {
        return $this->images()->where('processing_status', 'processed');
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }

    public function faqs(): MorphToMany
    {
        return $this->morphToMany(Faq::class, 'faqable');
    }

    public function routePrefix(): string
    {
        return 'الخدمات';
    }

    public function scopePublished($query)
    {
        return $query->where('is_active', true)->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
