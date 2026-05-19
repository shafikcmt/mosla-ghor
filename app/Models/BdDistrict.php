<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BdDistrict extends Model
{
    protected $fillable = ['source_id', 'division_id', 'name', 'bn_name', 'lat', 'lon', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function division(): BelongsTo
    {
        return $this->belongsTo(BdDivision::class, 'division_id');
    }

    public function upazilas(): HasMany
    {
        return $this->hasMany(BdUpazila::class, 'district_id');
    }
}
