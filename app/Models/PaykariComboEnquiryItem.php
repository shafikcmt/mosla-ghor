<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaykariComboEnquiryItem extends Model
{
    protected $fillable = [
        'combo_enquiry_id', 'product_id', 'product_name', 'quantity_kg',
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
    ];

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(PaykariComboEnquiry::class, 'combo_enquiry_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
