<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name', 'mobile_number', 'alternative_number', 'email',
        'last_division_name', 'last_district_name', 'last_upazila_name', 'last_union_name',
        'last_full_address', 'total_orders', 'total_spent', 'last_order_at',
        'accepts_marketing', 'is_active', 'notes',
    ];

    protected $casts = [
        'total_spent'       => 'decimal:2',
        'last_order_at'     => 'datetime',
        'accepts_marketing' => 'boolean',
        'is_active'         => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
