<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'customer_name', 'customer_location', 'rating',
        'review_text', 'image', 'sort_order', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean', 'rating' => 'integer'];
}
