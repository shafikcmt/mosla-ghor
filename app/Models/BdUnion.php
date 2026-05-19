<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BdUnion extends Model
{
    protected $fillable = ['source_id', 'upazila_id', 'name', 'bn_name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function upazila(): BelongsTo
    {
        return $this->belongsTo(BdUpazila::class, 'upazila_id');
    }
}
