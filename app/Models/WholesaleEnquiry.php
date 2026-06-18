<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WholesaleEnquiry extends Model
{
    protected $fillable = [
        'customer_id', 'product_id', 'product_variant_id', 'vendor_id',
        'quantity_kg', 'quantity_unit', 'delivery_location', 'business_type', 'message',
        'customer_name', 'customer_phone', 'customer_whatsapp',
        'product_name', 'variant_name', 'status', 'vendor_note', 'admin_note',
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

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /** Product name with the selected variant appended, e.g. "জিরা — ইরানি জিরা". */
    public function productLabel(): string
    {
        return $this->variant_name
            ? $this->product_name . ' — ' . $this->variant_name
            : (string) $this->product_name;
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(WholesaleQuote::class, 'enquiry_id');
    }

    public function latestQuote(): HasOne
    {
        return $this->hasOne(WholesaleQuote::class, 'enquiry_id')->latestOfMany();
    }

    /** Latest quote the customer is allowed to see (no admin-approval gate). */
    public function customerVisibleQuote(): HasOne
    {
        return $this->hasOne(WholesaleQuote::class, 'enquiry_id')
            ->whereIn('status', WholesaleQuote::CUSTOMER_VISIBLE_STATUSES)
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

    /**
     * Count chat messages not yet read by the given role ('customer'|'vendor'|'admin').
     * Counts only messages sent by someone other than that role.
     */
    public function unreadFor(string $role): int
    {
        $column = match ($role) {
            'customer' => 'is_read_by_customer',
            'vendor'   => 'is_read_by_vendor',
            'admin'    => 'is_read_by_admin',
            default    => null,
        };
        if (! $column) {
            return 0;
        }

        return $this->chatMessages()
            ->where('sender_type', '!=', $role)
            ->where($column, false)
            ->count();
    }

    // Returns only safe fields — customer contact is excluded
    public function toVendorArray(): array
    {
        return [
            'id'               => $this->id,
            'product_name'     => $this->productLabel(),
            'variant_name'     => $this->variant_name,
            'quantity_kg'      => $this->quantity_kg,
            'delivery_location'=> $this->delivery_location,
            'business_type'    => $this->businessTypeLabel(),
            'message'          => $this->message,
            'status'           => $this->statusLabel(),
            'created_at'       => $this->created_at,
        ];
    }
}
