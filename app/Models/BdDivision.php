<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BdDivision extends Model
{
    protected $fillable = ['source_id', 'name', 'bn_name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function districts(): HasMany
    {
        return $this->hasMany(BdDistrict::class, 'division_id');
    }
}
