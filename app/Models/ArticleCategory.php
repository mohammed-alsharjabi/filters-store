<?php

namespace App\Models;

use App\Models\Concerns\HasArabicSlug;
use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleCategory extends Model
{
    use HasArabicSlug, HasSeo;

    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function routePrefix(): string
    {
        return 'الدليل/تصنيف';
    }

    protected function shouldCreateSlugRedirect(): bool
    {
        return false;
    }
}
