<?php

namespace App\Models\Khata;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Balance sign: + = পাওনা (party owes the shop) · − = বকেয়া (shop owes party).
 */
class KhataParty extends Model
{
    protected $fillable = ['vendor_id', 'name', 'phone', 'address', 'type', 'opening_balance', 'opening_date', 'note'];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opening_date'    => 'date',
    ];

    public const TYPES = ['customer' => 'গ্রাহক', 'supplier' => 'সরবরাহকারী'];

    public function transactions(): HasMany
    {
        return $this->hasMany(KhataTransaction::class, 'party_id');
    }

    /** SQL for the signed ledger movement of one transaction row. */
    public static function movementSql(string $t = 'khata_transactions'): string
    {
        return "CASE {$t}.type
            WHEN 'sale' THEN {$t}.total - {$t}.paid
            WHEN 'payment_in' THEN -{$t}.paid
            WHEN 'purchase' THEN -({$t}.total - {$t}.paid)
            WHEN 'payment_out' THEN {$t}.paid
            ELSE 0 END";
    }

    /** Adds a `balance` column (opening + all movements). */
    public function scopeWithBalance(Builder $q): Builder
    {
        $sub = DB::table('khata_transactions')
            ->selectRaw('COALESCE(SUM('.self::movementSql().'), 0)')
            ->whereColumn('khata_transactions.party_id', 'khata_parties.id');

        return $q->select('khata_parties.*')->selectSub($sub, 'movement');
    }

    public function balance(): float
    {
        $movement = $this->getAttribute('movement');
        if ($movement === null) {
            $movement = (float) $this->transactions()->selectRaw('COALESCE(SUM('.self::movementSql().'), 0) as m')->value('m');
        }

        return round((float) $this->opening_balance + (float) $movement, 2);
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/u', trim($this->name)) ?: [''];

        return mb_strtoupper(mb_substr($words[0], 0, 1).(isset($words[1]) ? mb_substr($words[1], 0, 1) : ''));
    }
}
