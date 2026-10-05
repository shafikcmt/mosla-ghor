<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'user_id', 'shop_name', 'slug', 'owner_name', 'phone', 'email',
        'address', 'district', 'city', 'trade_license', 'nid',
        'logo', 'banner', 'business_type', 'kyc_document',
        'payment_info', 'commission_type', 'commission_value',
        'product_auto_approve', 'status', 'is_active', 'admin_note',
        'approved_at', 'approved_by', 'suspended_at',
        'panel_mode', 'khata_settings',
    ];

    public const PANEL_MODES = [
        'full'      => 'ই-কমার্স (সম্পূর্ণ প্যানেল)',
        'inventory' => 'শুধু স্টক ও হিসাব (দোকানের খাতা)',
    ];

    protected $casts = [
        'payment_info'         => 'array',
        'khata_settings'       => 'array',
        'commission_value'     => 'decimal:2',
        'product_auto_approve' => 'boolean',
        'is_active'            => 'boolean',
        'approved_at'          => 'datetime',
        'suspended_at'         => 'datetime',
    ];

    /** Business types offered in the admin create/edit forms. */
    public const BUSINESS_TYPES = ['Shop', 'Restaurant', 'Dealer', 'Wholesaler', 'Retailer', 'Other'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function marketingSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(VendorMarketingSetting::class);
    }

    public function combos(): HasMany
    {
        return $this->hasMany(Combo::class);
    }

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class);
    }

    public function pickupPoints(): HasMany
    {
        return $this->hasMany(VendorPickupPoint::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(VendorCustomer::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(VendorStockMovement::class);
    }

    /** Orders this vendor created on a customer's behalf (POS). */
    public function createdOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by_vendor_id');
    }

    public function defaultPickupPoint(): ?VendorPickupPoint
    {
        return VendorPickupPoint::defaultFor($this->id);
    }

    public function wholesaleEnquiries(): HasMany
    {
        return $this->hasMany(WholesaleEnquiry::class);
    }

    public function commissionLedger(): HasMany
    {
        return $this->hasMany(WholesaleCommissionLedger::class);
    }

    /** Inventory-only shop: sees just দোকানের খাতা (no e-commerce features). */
    public function isInventoryOnly(): bool
    {
        return $this->panel_mode === 'inventory';
    }

    /** Voucher / book settings with defaults. */
    public function khataSetting(string $key, mixed $default = null): mixed
    {
        $defaults = [
            'terms'       => "ধন্যবাদ আপনাকে\nআমাদের সাথে থাকার জন্য",
            'show_balance'=> true,
        ];

        return ($this->khata_settings ?? [])[$key] ?? $defaults[$key] ?? $default;
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved' && $this->is_active;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function effectiveCommissionType(): string
    {
        return $this->commission_type
            ?? WebsiteSetting::get('default_commission_type', 'percentage');
    }

    public function effectiveCommissionValue(): float
    {
        if ($this->commission_value !== null) {
            return (float) $this->commission_value;
        }
        return (float) WebsiteSetting::get('default_commission_value', 0);
    }

    public function calculateCommission(float $subtotal): float
    {
        $type  = $this->effectiveCommissionType();
        $value = $this->effectiveCommissionValue();

        if ($type === 'percentage') {
            return round($subtotal * $value / 100, 2);
        }

        return round($value, 2);
    }

    public function calculateWholesaleCommission(float $subtotal): array
    {
        $setting = WholesaleCommissionSetting::resolveFor($this->id, 'wholesale');
        $amount  = $setting->calculate($subtotal);

        return [
            'commission_type'            => $setting->commission_type,
            'commission_value_snapshot'  => (float) $setting->commission_value,
            'commission_amount'          => $amount,
            'vendor_earning'             => round($subtotal - $amount, 2),
        ];
    }
}
