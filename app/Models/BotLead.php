<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A lead pushed by the social bot (Messenger, comment, WhatsApp …) for the team to follow up. */
class BotLead extends Model
{
    public const SOURCES = [
        'messenger'        => 'Messenger',
        'facebook_comment' => 'Facebook কমেন্ট',
        'whatsapp'         => 'WhatsApp',
        'instagram'        => 'Instagram',
        'other'            => 'অন্যান্য',
    ];

    public const STATUSES = [
        'new'       => 'নতুন',
        'contacted' => 'যোগাযোগ হয়েছে',
        'converted' => 'অর্ডার হয়েছে',
        'closed'    => 'বন্ধ',
    ];

    protected $fillable = [
        'bot_api_token_id', 'source', 'external_ref', 'page_name', 'customer_name', 'phone',
        'message', 'product_id', 'quantity', 'address', 'status', 'admin_note',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(BotApiToken::class, 'bot_api_token_id');
    }
}
