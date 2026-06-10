<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class WholesaleQuote extends Model
{
    protected $fillable = [
        'enquiry_id', 'vendor_id', 'customer_id',
        'unit_price', 'quantity', 'quantity_unit', 'subtotal',
        'delivery_charge', 'advance_required', 'advance_percentage',
        'delivery_time', 'payment_options',
        'note', 'valid_until', 'validity_days', 'status',
        'admin_note', 'order_id',
    ];

    protected $casts = [
        'unit_price'          => 'decimal:2',
        'quantity'            => 'decimal:2',
        'subtotal'            => 'decimal:2',
        'delivery_charge'     => 'decimal:2',
        'advance_required'    => 'decimal:2',
        'advance_percentage'  => 'decimal:2',
        'validity_days'       => 'integer',
        'payment_options'     => 'array',
        'valid_until'         => 'date',
    ];

    /** Quote statuses a customer is allowed to see / act on. */
    public const CUSTOMER_VISIBLE_STATUSES = ['sent_to_customer', 'accepted', 'converted_to_order'];

    /**
     * Validate + create a quote from a submit request (shared by vendor & admin).
     * No admin-approval gate: the quote is immediately visible to the customer.
     *
     * @param int|null $vendorId  null for admin-submitted quotes with no assigned vendor
     */
    public static function createFromRequest(Request $request, WholesaleEnquiry $enquiry, ?int $vendorId): self
    {
        $validated = $request->validate([
            'unit_price'         => ['required', 'numeric', 'min:0'],
            'quantity'           => ['required', 'numeric', 'min:0.5'],
            'quantity_unit'      => ['required', 'string', 'in:kg,gram,ton,piece,bag,carton,packet'],
            'delivery_charge'    => ['required', 'numeric', 'min:0'],
            'advance_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'delivery_time'      => ['nullable', 'string', 'max:100'],
            'validity_days'      => ['nullable', 'integer', 'min:1', 'max:365'],
            'payment_options'    => ['nullable', 'array'],
            'payment_options.*'  => ['in:online,manual,cod,partial'],
            'note'               => ['nullable', 'string', 'max:1000'],
        ]);

        $subtotal     = round($validated['unit_price'] * $validated['quantity'], 2);
        $total        = $subtotal + (float) $validated['delivery_charge'];
        $advancePct   = (float) ($validated['advance_percentage'] ?? 0);
        $advanceAmt   = $advancePct > 0 ? round($total * $advancePct / 100, 2) : 0;
        $validityDays = (int) ($validated['validity_days'] ?? 7);

        return self::create([
            'enquiry_id'         => $enquiry->id,
            'vendor_id'          => $vendorId,
            'customer_id'        => $enquiry->customer_id,
            'unit_price'         => $validated['unit_price'],
            'quantity'           => $validated['quantity'],
            'quantity_unit'      => $validated['quantity_unit'],
            'subtotal'           => $subtotal,
            'delivery_charge'    => $validated['delivery_charge'],
            'advance_percentage' => $advancePct ?: null,
            'advance_required'   => $advanceAmt,
            'delivery_time'      => $validated['delivery_time'] ?? null,
            'payment_options'    => $validated['payment_options'] ?? [],
            'note'               => $validated['note'] ?? null,
            'validity_days'      => $validityDays,
            'valid_until'        => now()->addDays($validityDays)->toDateString(),
            'status'             => 'sent_to_customer',
        ]);
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(WholesaleEnquiry::class, 'enquiry_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function grandTotal(): float
    {
        return (float) $this->subtotal + (float) $this->delivery_charge;
    }

    public static function statuses(): array
    {
        return [
            'sent_to_customer'    => 'কোটেশন পাঠানো হয়েছে',
            'accepted'            => 'গ্রহণ করা হয়েছে',
            'rejected'            => 'প্রত্যাখ্যাত',
            'expired'             => 'মেয়াদ শেষ',
            'converted_to_order'  => 'অর্ডার হয়েছে',
        ];
    }

    public function isVisibleToCustomer(): bool
    {
        return in_array($this->status, self::CUSTOMER_VISIBLE_STATUSES, true);
    }

    /** Advance amount: prefer explicit amount, else derive from percentage of the grand total. */
    public function advanceAmount(): float
    {
        if ((float) $this->advance_required > 0) {
            return (float) $this->advance_required;
        }
        if ((float) $this->advance_percentage > 0) {
            return round($this->grandTotal() * (float) $this->advance_percentage / 100, 2);
        }
        return 0.0;
    }

    public function statusLabel(): string
    {
        return static::statuses()[$this->status] ?? $this->status;
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }
}
