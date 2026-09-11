<?php

namespace App\Models;

use App\Models\Concerns\HasArabicSlug;
use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Material extends Model
{
    use HasArabicSlug, HasSeo;

    protected $fillable = ['name', 'slug', 'excerpt', 'description', 'featured_image', 'featured_image_alt', 'featured_image_caption', 'price_from', 'price_to', 'price_unit', 'price_note', 'is_price_published', 'is_active'];

    protected function casts(): array
    {
        return ['is_price_published' => 'boolean', 'is_active' => 'boolean', 'price_from' => 'decimal:2', 'price_to' => 'decimal:2'];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function routePrefix(): string
    {
        return 'الأسعار';
    }

    protected function shouldCreateSlugRedirect(): bool
    {
        return false;
    }
}
