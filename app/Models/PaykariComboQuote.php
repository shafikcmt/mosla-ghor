<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaykariComboQuote extends Model
{
    protected $fillable = [
        'combo_enquiry_id', 'vendor_id',
        'items', 'delivery_charge', 'advance_required', 'advance_amount',
        'payment_options', 'note', 'valid_until',
        'status', 'admin_approved', 'admin_note', 'customer_response',
    ];

    protected $casts = [
        'items'           => 'array',
        'payment_options' => 'array',
        'delivery_charge' => 'decimal:2',
        'advance_amount'  => 'decimal:2',
        'admin_approved'  => 'boolean',
        'advance_required'=> 'boolean',
        'valid_until'     => 'date',
    ];

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(PaykariComboEnquiry::class, 'combo_enquiry_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function grandTotal(): float
    {
        $itemTotal = collect($this->items)->sum(fn($i) => (float) ($i['subtotal'] ?? 0));
        return $itemTotal + (float) $this->delivery_charge;
    }

    public function itemTotal(): float
    {
        return collect($this->items)->sum(fn($i) => (float) ($i['subtotal'] ?? 0));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'  => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default    => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending'  => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default    => 'bg-gray-100 text-gray-600',
        };
    }
}
