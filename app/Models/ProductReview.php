<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'order_item_id', 'product_id',
        'customer_name', 'customer_contact', 'title',
        'rating', 'comment', 'image', 'is_approved',
    ];

    protected $casts = [
        'rating'      => 'integer',
        'is_approved' => 'boolean',
    ];

    public function user(): BelongsTo      { return $this->belongsTo(User::class); }
    public function order(): BelongsTo     { return $this->belongsTo(Order::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class, 'order_item_id'); }
    public function product(): BelongsTo   { return $this->belongsTo(Product::class); }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /** Public display name, falling back to a friendly default. */
    public function getDisplayNameAttribute(): string
    {
        return $this->customer_name
            ?: ($this->user?->name ?: 'একজন ক্রেতা');
    }
}
