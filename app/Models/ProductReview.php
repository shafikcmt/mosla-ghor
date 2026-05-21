<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'order_item_id', 'product_id',
        'rating', 'comment', 'is_approved',
    ];

    protected $casts = [
        'rating'      => 'integer',
        'is_approved' => 'boolean',
    ];

    public function user(): BelongsTo      { return $this->belongsTo(User::class); }
    public function order(): BelongsTo     { return $this->belongsTo(Order::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class, 'order_item_id'); }
    public function product(): BelongsTo   { return $this->belongsTo(Product::class); }
}
