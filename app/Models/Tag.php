<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name', 'normalized_key'];

    public static function cleanName(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    public static function keyFor(string $name): string
    {
        return hash('sha256', mb_strtolower(self::cleanName($name), 'UTF-8'));
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
