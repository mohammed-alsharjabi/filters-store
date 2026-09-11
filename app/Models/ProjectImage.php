<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectImage extends Model
{
    protected $fillable = ['project_id', 'path', 'variants', 'width', 'height', 'alt_text', 'caption', 'stage', 'sort_order', 'is_cover'];

    protected function casts(): array
    {
        return ['variants' => 'array', 'is_cover' => 'boolean'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
