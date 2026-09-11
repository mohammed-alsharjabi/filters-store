<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMetadata extends Model
{
    protected $table = 'seo_metadata';

    protected $fillable = ['route_name', 'meta_title', 'meta_description', 'focus_keyword', 'related_terms', 'internal_links', 'canonical_url', 'robots', 'og_title', 'og_description', 'og_image', 'schema_type', 'schema_extra'];

    protected function casts(): array
    {
        return ['schema_extra' => 'array'];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
