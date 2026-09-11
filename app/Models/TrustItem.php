<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrustItem extends Model
{
    protected $fillable = ['label', 'value', 'description', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
