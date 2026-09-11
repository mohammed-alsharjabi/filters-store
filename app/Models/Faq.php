<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Faq extends Model
{
    protected $fillable = ['question', 'answer', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function services(): MorphToMany
    {
        return $this->morphedByMany(Service::class, 'faqable');
    }

    public function articles(): MorphToMany
    {
        return $this->morphedByMany(Article::class, 'faqable');
    }

    public function areas(): MorphToMany
    {
        return $this->morphedByMany(Area::class, 'faqable');
    }
}
