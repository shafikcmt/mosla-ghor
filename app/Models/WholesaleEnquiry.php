<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WholesaleEnquiry extends Model
{
    protected $fillable = [
        'customer_id', 'product_id', 'vendor_id',
        'quantity_kg', 'quantity_unit', 'delivery_location', 'business_type', 'message',
        'customer_name', 'customer_phone', 'customer_whatsapp',
        'product_name', 'status', 'vendor_note', 'admin_note',
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(WholesaleQuote::class, 'enquiry_id');
    }

    public function latestQuote(): HasOne
    {
        return $this->hasOne(WholesaleQuote::class, 'enquiry_id')->latestOfMany();
    }

    public function approvedQuote(): HasOne
    {
        return $this->hasOne(WholesaleQuote::class, 'enquiry_id')
            ->where('admin_approved', true)
            ->where('status', 'pending')
            ->latestOfMany();
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(WholesaleChatMessage::class, 'enquiry_id')->orderBy('created_at');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'enquiry_id');
    }

    public static function statuses(): array
    {
        return [
            'pending'   => 'অপেক্ষায়',
            'quoted'    => 'কোটেশন পাঠানো হয়েছে',
            'accepted'  => 'গ্রহণ করা হয়েছে',
            'completed' => 'সম্পন্ন',
            'rejected'  => 'প্রত্যাখ্যাত',
            'cancelled' => 'বাতিল',
        ];
    }

    public static function businessTypes(): array
    {
        return [
            'shop'      => 'শপ / দোকান',
            'restaurant'=> 'রেস্তোরাঁ',
            'dealer'    => 'ডিলার',
            'retailer'  => 'রিটেইলার',
            'other'     => 'অন্যান্য',
        ];
    }

    public function statusLabel(): string
    {
        return static::statuses()[$this->status] ?? $this->status;
    }

    public function businessTypeLabel(): string
    {
        return static::businessTypes()[$this->business_type] ?? $this->business_type;
    }

    // Returns only safe fields — customer contact is excluded
    public function toVendorArray(): array
    {
        return [
            'id'               => $this->id,
            'product_name'     => $this->product_name,
            'quantity_kg'      => $this->quantity_kg,
            'delivery_location'=> $this->delivery_location,
            'business_type'    => $this->businessTypeLabel(),
            'message'          => $this->message,
            'status'           => $this->statusLabel(),
            'created_at'       => $this->created_at,
        ];
    }
}
