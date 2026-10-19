<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    protected $fillable = ['title', 'subtitle', 'eyebrow', 'image_path', 'display_mode', 'mobile_image_path', 'link_url', 'primary_label', 'primary_url',
        'secondary_label', 'secondary_url', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    /** Picture-only banner: the whole slide is one link. */
    public function isBanner(): bool
    {
        return $this->display_mode === 'image';
    }
}
