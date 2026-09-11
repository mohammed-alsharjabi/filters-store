<?php

namespace App\Models;

use App\Models\Concerns\HasArabicSlug;
use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    use HasArabicSlug, HasSeo;

    protected $fillable = ['name', 'slug', 'excerpt', 'description', 'featured_image', 'featured_image_alt', 'featured_image_caption', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function routePrefix(): string
    {
        return 'الخدمات/تصنيف';
    }
}
