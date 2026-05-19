<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courier extends Model
{
    protected $fillable = [
        'name', 'slug', 'status', 'api_enabled', 'api_key', 'api_secret',
        'base_url', 'is_default', 'notes',
    ];

    protected $casts = [
        'api_enabled' => 'boolean',
        'is_default'  => 'boolean',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(DeliveryRate::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->where('status', 'active')->first();
    }

    public static function active()
    {
        return static::where('status', 'active');
    }
}
