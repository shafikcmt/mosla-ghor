<?php

namespace App\Models\Khata;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KhataTransactionItem extends Model
{
    protected $fillable = ['transaction_id', 'item_id', 'name', 'unit', 'pack_kg', 'quantity', 'price', 'cost', 'total'];

    protected $casts = [
        'quantity' => 'decimal:3',
        'pack_kg'  => 'decimal:3',
        'price'    => 'decimal:2',
        'cost'     => 'decimal:2',
        'total'    => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(KhataTransaction::class, 'transaction_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(KhataItem::class, 'item_id');
    }

    /** "1 কার্টন (20 কেজি)" / "27 কেজি 700 গ্রাম". */
    public function qtyLabel(): string
    {
        return KhataItem::formatQtyWithWeight($this->quantity, $this->unit, $this->pack_kg);
    }
}
