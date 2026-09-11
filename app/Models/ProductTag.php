<?php

namespace App\Models;

use App\Models\Concerns\HasArabicSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductTag extends Model
{
    use HasArabicSlug;

    protected $fillable = ['name', 'slug', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_tag');
    }

    public function routePrefix(): string
    {
        return 'وسوم-المنتجات';
    }

    protected function shouldCreateSlugRedirect(): bool
    {
        return false;
    }
}
