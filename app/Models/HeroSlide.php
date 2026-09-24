<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    protected $fillable = ['title', 'subtitle', 'eyebrow', 'image_path', 'primary_label', 'primary_url',
        'secondary_label', 'secondary_url', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];
}
