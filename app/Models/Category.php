<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name_bn',
        'name_en',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'parent_id'  => 'integer',
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** Display name: prefer Bangla, fall back to English. */
    public function getDisplayNameAttribute(): string
    {
        return $this->name_bn ?: (string) $this->name_en;
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
