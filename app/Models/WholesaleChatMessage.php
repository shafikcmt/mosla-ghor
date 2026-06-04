<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WholesaleChatMessage extends Model
{
    protected $fillable = [
        'enquiry_id', 'quote_id', 'sender_type', 'sender_id',
        'message', 'is_filtered', 'filter_reason',
        'is_read_by_customer', 'is_read_by_vendor', 'is_read_by_admin',
    ];

    protected $casts = [
        'is_filtered'          => 'boolean',
        'is_read_by_customer'  => 'boolean',
        'is_read_by_vendor'    => 'boolean',
        'is_read_by_admin'     => 'boolean',
    ];

    // Phone numbers: 01XXXXXXXXX, +88..., 880...
    private const PHONE_PATTERN = '/(?:\+?880|0)1[3-9]\d{8}/';

    // Email addresses
    private const EMAIL_PATTERN = '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/';

    // URLs and social media links
    private const URL_PATTERN = '/(?:https?:\/\/|www\.)[^\s]+|(?:facebook|fb|whatsapp|wa\.me|t\.me|telegram|instagram|tiktok)[^\s]*/i';

    // Bangla/English "bkash", "nagad", "rocket" payment mentions with numbers
    private const PAYMENT_PATTERN = '/(?:bkash|বিকাশ|nagad|নগদ|rocket|রকেট)[^\s]*/iu';

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(WholesaleEnquiry::class, 'enquiry_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(WholesaleQuote::class, 'quote_id');
    }

    // Detect contact-sharing attempts; returns reason string or null
    public static function detectContactSharing(string $message): ?string
    {
        if (preg_match(self::PHONE_PATTERN, $message)) {
            return 'phone_number';
        }
        if (preg_match(self::EMAIL_PATTERN, $message)) {
            return 'email_address';
        }
        if (preg_match(self::URL_PATTERN, $message)) {
            return 'external_link';
        }
        if (preg_match(self::PAYMENT_PATTERN, $message)) {
            return 'payment_info';
        }
        return null;
    }

    public function senderLabel(): string
    {
        return match ($this->sender_type) {
            'customer' => 'ক্রেতা',
            'vendor'   => 'সাপ্লায়ার',
            'admin'    => 'মসলামার্ট',
            default    => $this->sender_type,
        };
    }
}
