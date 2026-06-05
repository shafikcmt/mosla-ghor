<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WholesaleCommissionLedger extends Model
{
    /**
     * Migration creates the singular `wholesale_commission_ledger`, but Eloquent
     * would otherwise pluralise the class to `wholesale_commission_ledgers`.
     * Pin the real table name so local SQLite and live MySQL both resolve it.
     */
    protected $table = 'wholesale_commission_ledger';

    protected $fillable = [
        'vendor_id', 'customer_id', 'enquiry_id', 'quote_id', 'order_id',
        'order_type', 'subtotal', 'commission_type', 'commission_value_snapshot',
        'commission_amount', 'vendor_earning',
        'cod_collected_by', 'settlement_status', 'settled_at', 'settled_by',
        'admin_note',
    ];

    protected $casts = [
        'subtotal'                  => 'decimal:2',
        'commission_value_snapshot' => 'decimal:2',
        'commission_amount'         => 'decimal:2',
        'vendor_earning'            => 'decimal:2',
        'settled_at'                => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(WholesaleEnquiry::class, 'enquiry_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(WholesaleQuote::class, 'quote_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    public static function summaryForVendor(int $vendorId): array
    {
        $rows = static::where('vendor_id', $vendorId)->get();

        return [
            'total_sales'        => $rows->sum('subtotal'),
            'total_commission'   => $rows->sum('commission_amount'),
            'total_earning'      => $rows->sum('vendor_earning'),
            'settled_amount'     => $rows->where('settlement_status', 'settled')->sum('vendor_earning'),
            'pending_amount'     => $rows->where('settlement_status', 'pending')->sum('vendor_earning'),
        ];
    }
}
