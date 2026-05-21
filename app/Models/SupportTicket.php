<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'subject', 'message',
        'status', 'admin_reply', 'replied_at',
    ];

    protected $casts = ['replied_at' => 'datetime'];

    public function user(): BelongsTo  { return $this->belongsTo(User::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }

    public function statusLabel(): string
    {
        return match($this->status) {
            'replied' => 'উত্তর দেওয়া হয়েছে',
            'closed'  => 'বন্ধ',
            default   => 'খোলা',
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'replied' => 'bg-blue-100 text-blue-700',
            'closed'  => 'bg-gray-100 text-gray-600',
            default   => 'bg-yellow-100 text-yellow-700',
        };
    }
}
