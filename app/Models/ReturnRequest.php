<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequest extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'order_item_id',
        'reason', 'details', 'image', 'status', 'admin_note',
    ];

    public function user(): BelongsTo      { return $this->belongsTo(User::class); }
    public function order(): BelongsTo     { return $this->belongsTo(Order::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class, 'order_item_id'); }

    public function statusLabel(): string
    {
        return match($this->status) {
            'approved'  => 'অনুমোদিত',
            'rejected'  => 'প্রত্যাখ্যাত',
            'completed' => 'সম্পন্ন',
            default     => 'অপেক্ষায়',
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'approved'  => 'bg-green-100 text-green-700',
            'rejected'  => 'bg-red-100 text-red-700',
            'completed' => 'bg-blue-100 text-blue-700',
            default     => 'bg-yellow-100 text-yellow-700',
        };
    }
}
