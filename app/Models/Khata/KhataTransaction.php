<?php

namespace App\Models\Khata;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class KhataTransaction extends Model
{
    protected $fillable = [
        'vendor_id', 'party_id', 'type', 'number', 'date', 'subtotal', 'discount',
        'extra_label', 'extra_charge', 'total', 'paid', 'payment_mode', 'category',
        'note', 'share_token', 'created_by',
    ];

    protected $casts = [
        'date'         => 'date',
        'subtotal'     => 'decimal:2',
        'discount'     => 'decimal:2',
        'extra_charge' => 'decimal:2',
        'total'        => 'decimal:2',
        'paid'         => 'decimal:2',
    ];

    public const TYPES = [
        'sale'        => 'বিক্রি',
        'purchase'    => 'ক্রয়',
        'payment_in'  => 'পেমেন্ট ইন',
        'payment_out' => 'পেমেন্ট আউট',
        'expense'     => 'খরচ',
        'stock_in'    => 'স্টক যোগ',
        'stock_out'   => 'স্টক কমানো',
    ];

    public const MODES = ['cash' => 'নগদ', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'ব্যাংক', 'credit' => 'বাকি / ক্রেডিট'];

    /** Types with an invoice-style line table. */
    public const ITEM_TYPES = ['sale', 'purchase', 'stock_in', 'stock_out'];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(KhataParty::class, 'party_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(KhataTransactionItem::class, 'transaction_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function modeLabel(): string
    {
        return self::MODES[$this->payment_mode] ?? $this->payment_mode;
    }

    /** Still owed on a sale / purchase. */
    public function due(): float
    {
        return in_array($this->type, ['sale', 'purchase'], true) ? round((float) $this->total - (float) $this->paid, 2) : 0.0;
    }

    public function statusLabel(): ?string
    {
        if (! in_array($this->type, ['sale', 'purchase'], true)) {
            return null;
        }
        if ($this->due() <= 0) {
            return 'পরিশোধিত';
        }

        return (float) $this->paid > 0 ? 'আংশিক পরিশোধ' : 'পরিশোধ হয়নি';
    }

    public function ensureShareToken(): string
    {
        if (! $this->share_token) {
            $this->forceFill(['share_token' => Str::random(32)])->save();
        }

        return $this->share_token;
    }

    public function publicUrl(): string
    {
        return route('khata.voucher.public', $this->ensureShareToken());
    }
}
