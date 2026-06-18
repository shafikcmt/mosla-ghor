<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaykariComboEnquiryItem extends Model
{
    protected $fillable = [
        'combo_enquiry_id', 'product_id', 'product_variant_id',
        'product_name', 'variant_name', 'quantity_kg', 'quantity_unit',
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

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** Product name with the selected variant appended, e.g. "জিরা — ইরানি জিরা". */
    public function productLabel(): string
    {
        return $this->variant_name
            ? $this->product_name . ' — ' . $this->variant_name
            : (string) $this->product_name;
    }
}
