<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadImage extends Model
{
    protected $fillable = ['lead_id', 'path', 'original_name', 'mime_type', 'size'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
