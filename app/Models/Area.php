<?php

namespace App\Models;

use App\Models\Concerns\HasArabicSlug;
use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Area extends Model
{
    use HasArabicSlug, HasSeo;

    protected $fillable = ['name', 'slug', 'excerpt', 'content', 'featured_image', 'featured_image_alt', 'featured_image_caption', 'is_active', 'status', 'is_primary', 'published_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_primary' => 'boolean', 'published_at' => 'datetime'];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function faqs(): MorphToMany
    {
        return $this->morphToMany(Faq::class, 'faqable');
    }

    public function routePrefix(): string
    {
        return 'المناطق';
    }

    public function scopePublished($query)
    {
        return $query->where('is_active', true)->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
