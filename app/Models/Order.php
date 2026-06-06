<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_name', 'mobile_number', 'alternative_number',
        'full_address', 'district', 'area', 'delivery_area',
        'delivery_zone_id', 'delivery_location_id', 'delivery_zone_name', 'delivery_location_name',
        'order_note', 'order_type', 'subtotal', 'packaging_cost', 'delivery_charge', 'grand_total',
        'payment_method', 'sender_number', 'transaction_id', 'paid_amount',
        'payment_status', 'order_status', 'combo_id', 'payment_screenshot',
        'bd_division_id', 'bd_district_id', 'bd_upazila_id', 'bd_union_id',
        'division_name', 'district_name', 'upazila_name', 'union_name',
        // Courier fields
        'selected_courier_id', 'suggested_courier_id', 'delivery_rate_id',
        'delivery_zone_type', 'weight_gram', 'courier_cost', 'cod_charge',
        'courier_status', 'tracking_id', 'consignment_id', 'courier_note',
        'sent_to_courier_at', 'delivered_at', 'returned_at',
        'delivery_charge_overridden', 'courier_cost_overridden', 'zone_overridden',
        'pickup_point_id', 'parcel_created_by', 'parcel_created_by_user_id',
        'stock_deducted_at', 'stock_restored_at',
        // CRM
        'customer_id', 'accepts_marketing',
        // Cancellation
        'cancellation_reason', 'cancelled_by', 'cancelled_at',
        // Wholesale
        'enquiry_id',
        // Vendor POS / local-business workflow
        'order_source', 'created_by_vendor_id', 'vendor_customer_id',
        'discount_amount', 'partial_paid_amount', 'due_amount',
        'invoice_token', 'payment_link_token', 'reorder_token',
        'whatsapp_sent_at', 'customer_confirmed_at', 'invoice_disabled_at',
    ];

    protected $casts = [
        'subtotal'                   => 'decimal:2',
        'packaging_cost'             => 'decimal:2',
        'delivery_charge'            => 'decimal:2',
        'grand_total'                => 'decimal:2',
        'paid_amount'                => 'decimal:2',
        'courier_cost'               => 'decimal:2',
        'cod_charge'                 => 'decimal:2',
        'weight_gram'                => 'integer',
        'delivery_charge_overridden' => 'boolean',
        'courier_cost_overridden'    => 'boolean',
        'zone_overridden'            => 'boolean',
        'sent_to_courier_at'         => 'datetime',
        'delivered_at'               => 'datetime',
        'returned_at'                => 'datetime',
        'stock_deducted_at'          => 'datetime',
        'stock_restored_at'          => 'datetime',
        'accepts_marketing'          => 'boolean',
        'cancelled_at'               => 'datetime',
        'discount_amount'            => 'decimal:2',
        'partial_paid_amount'        => 'decimal:2',
        'due_amount'                 => 'decimal:2',
        'whatsapp_sent_at'           => 'datetime',
        'customer_confirmed_at'      => 'datetime',
        'invoice_disabled_at'        => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function selectedCourier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'selected_courier_id');
    }

    public function suggestedCourier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'suggested_courier_id');
    }

    public function pickupPoint(): BelongsTo
    {
        return $this->belongsTo(VendorPickupPoint::class, 'pickup_point_id');
    }

    /**
     * True when this order already has a courier parcel (API or manual), so a
     * second create must be blocked.
     */
    public function hasParcel(): bool
    {
        return ! empty($this->consignment_id)
            || ! empty($this->tracking_id)
            || ! empty($this->sent_to_courier_at);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    public function deliveryRate(): BelongsTo
    {
        return $this->belongsTo(DeliveryRate::class, 'delivery_rate_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function returnRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function supportTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function wholesaleEnquiry(): BelongsTo
    {
        return $this->belongsTo(WholesaleEnquiry::class, 'enquiry_id');
    }

    public function commissionLedger(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WholesaleCommissionLedger::class);
    }

    // ── Vendor POS / local-business workflow ─────────────────────────────────

    public function createdByVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'created_by_vendor_id');
    }

    public function vendorCustomer(): BelongsTo
    {
        return $this->belongsTo(VendorCustomer::class, 'vendor_customer_id');
    }

    public function isVendorCreated(): bool
    {
        return $this->order_source === 'vendor_created_order'
            || ! empty($this->created_by_vendor_id);
    }

    /** Generate the secure public tokens once (idempotent). */
    public function ensureTokens(): void
    {
        $dirty = false;
        foreach (['invoice_token', 'payment_link_token', 'reorder_token'] as $col) {
            if (empty($this->{$col})) {
                $this->{$col} = Str::random(40);
                $dirty = true;
            }
        }
        if ($dirty) {
            $this->save();
        }
    }

    public function invoiceUrl(): ?string
    {
        return $this->invoice_token ? url('/invoice/' . $this->invoice_token) : null;
    }

    public function reorderUrl(): ?string
    {
        return $this->reorder_token ? url('/invoice/' . $this->invoice_token . '/reorder') : null;
    }

    public function paymentUrl(): ?string
    {
        return $this->payment_link_token ? url('/invoice/' . $this->invoice_token . '/pay') : null;
    }

    /** Invoice link is usable: token set, not admin-disabled, not expired. */
    public function isInvoiceActive(): bool
    {
        if (empty($this->invoice_token) || $this->invoice_disabled_at) {
            return false;
        }
        $days = (int) WebsiteSetting::get('invoice_token_expiry_days', 0);
        if ($days > 0 && $this->created_at && $this->created_at->copy()->addDays($days)->isPast()) {
            return false;
        }
        return true;
    }
}
