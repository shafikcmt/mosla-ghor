<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaykariComboEnquiry extends Model
{
    protected $fillable = [
        'customer_id', 'vendor_id',
        'customer_name', 'customer_phone', 'customer_whatsapp',
        'delivery_location', 'business_type', 'message',
        'status', 'admin_note', 'vendor_note',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PaykariComboEnquiryItem::class, 'combo_enquiry_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(PaykariComboQuote::class, 'combo_enquiry_id');
    }

    public function latestQuote(): HasOne
    {
        return $this->hasOne(PaykariComboQuote::class, 'combo_enquiry_id')->latestOfMany();
    }

    public function approvedQuote(): HasOne
    {
        return $this->hasOne(PaykariComboQuote::class, 'combo_enquiry_id')
            ->where('admin_approved', true)
            ->where('status', 'approved')
            ->latestOfMany();
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
            'shop'       => 'শপ / দোকান',
            'restaurant' => 'রেস্তোরাঁ',
            'dealer'     => 'ডিলার',
            'retailer'   => 'রিটেইলার',
            'other'      => 'অন্যান্য',
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

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending'   => 'bg-yellow-100 text-yellow-800',
            'quoted'    => 'bg-blue-100 text-blue-800',
            'accepted'  => 'bg-green-100 text-green-800',
            'completed' => 'bg-emerald-100 text-emerald-800',
            'rejected'  => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-600',
            default     => 'bg-gray-100 text-gray-600',
        };
    }

    // Returns only safe fields visible to vendor — NO customer contact
    public function toVendorArray(): array
    {
        return [
            'id'               => $this->id,
            'delivery_location'=> $this->delivery_location,
            'business_type'    => $this->businessTypeLabel(),
            'message'          => $this->message,
            'status'           => $this->statusLabel(),
            'created_at'       => $this->created_at,
        ];
    }
}
