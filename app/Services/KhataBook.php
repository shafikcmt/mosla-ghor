<?php

namespace App\Services;

use App\Models\Khata\KhataItem;
use App\Models\Khata\KhataParty;
use App\Models\Khata\KhataTransaction;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * দোকানের খাতা bookkeeping: every sale / purchase / payment / expense / stock
 * adjustment is one KhataTransaction; item stock moves with it and is reversed
 * when the transaction is edited or deleted. Party balances are derived (never stored).
 */
class KhataBook
{
    /** Stock direction per transaction type. */
    private const STOCK_SIGN = ['sale' => -1, 'purchase' => 1, 'stock_in' => 1, 'stock_out' => -1];

    public function nextNumber(Vendor $vendor, string $type): int
    {
        $series = in_array($type, ['payment_in', 'payment_out'], true) ? ['payment_in', 'payment_out'] : [$type];

        return (int) KhataTransaction::where('vendor_id', $vendor->id)->whereIn('type', $series)->max('number') + 1;
    }

    /**
     * Sale / purchase / stock in-out with item lines.
     *
     * @param  array<int, array{item_id:int, quantity:float, price:float}>  $lines
     */
    public function recordWithItems(Vendor $vendor, string $type, array $data, array $lines): KhataTransaction
    {
        return DB::transaction(function () use ($vendor, $type, $data, $lines) {
            [$rows, $subtotal] = $this->buildLines($vendor, $type, $lines);

            $tx = KhataTransaction::create($this->amounts($type, $data, $subtotal) + [
                'vendor_id'  => $vendor->id,
                'type'       => $type,
                'number'     => in_array($type, ['sale', 'purchase'], true) ? ($data['number'] ?? $this->nextNumber($vendor, $type)) : null,
                'created_by' => Auth::id(),
            ]);
            $this->applyLines($tx, $rows);

            return $tx;
        });
    }

    /**
     * Edit a sale / purchase: put the old lines' stock back, then apply the new
     * lines — all in one database transaction, so stock is never half-updated.
     * Number, creator and share link stay the same.
     *
     * @param  array<int, array{item_id:int, quantity:float, price:float}>  $lines
     */
    public function updateWithItems(KhataTransaction $tx, array $data, array $lines): KhataTransaction
    {
        return DB::transaction(function () use ($tx, $data, $lines) {
            $tx = KhataTransaction::lockForUpdate()->with('vendor')->findOrFail($tx->id);
            // A sale keeps the cost it was booked at, so editing does not rewrite past profit.
            $oldCosts = $tx->lines->whereNotNull('item_id')->pluck('cost', 'item_id')->all();
            $this->reverseStock($tx);
            $tx->lines()->delete();

            [$rows, $subtotal] = $this->buildLines($tx->vendor, $tx->type, $lines, $oldCosts);
            $tx->update($this->amounts($tx->type, $data, $subtotal));
            $this->applyLines($tx, $rows);

            return $tx->fresh('lines');
        });
    }

    /** Lock items and price each line. Returns [[item, line attributes], subtotal]. */
    private function buildLines(Vendor $vendor, string $type, array $lines, array $oldCosts = []): array
    {
        $subtotal = 0.0;
        $rows     = [];
        foreach ($lines as $line) {
            $item  = KhataItem::where('vendor_id', $vendor->id)->lockForUpdate()->findOrFail($line['item_id']);
            $qty   = round((float) $line['quantity'], 3);
            $price = round((float) $line['price'], 2);
            $total = round($qty * $price, 2);
            $subtotal += $total;
            $rows[] = [$item, [
                'item_id'  => $item->id,
                'name'     => $item->name,
                'unit'     => $item->unit,
                // Weight of one unit for this line (a bosta may differ per purchase); falls back to the item's.
                'pack_kg'  => KhataItem::hasPackWeight($item->unit)
                    ? ((float) ($line['pack_kg'] ?? 0) > 0 ? round((float) $line['pack_kg'], 3) : ($item->pack_kg !== null ? (float) $item->pack_kg : null))
                    : null,
                'quantity' => $qty,
                'price'    => $price,
                'cost'     => $type === 'purchase' ? $price : (float) ($oldCosts[$item->id] ?? $item->purchase_price),
                'total'    => $total,
            ]];
        }

        return [$rows, $subtotal];
    }

    /** Header amounts for a sale / purchase / stock entry. */
    private function amounts(string $type, array $data, float $subtotal): array
    {
        $trade    = in_array($type, ['sale', 'purchase'], true);
        $discount = min(max(0, (float) ($data['discount'] ?? 0)), $subtotal);
        $extra    = max(0, (float) ($data['extra_charge'] ?? 0));
        $total    = $trade ? round($subtotal - $discount + $extra, 2) : round($subtotal, 2);
        $paid     = $trade ? min(max(0, (float) ($data['paid'] ?? 0)), $total) : 0;

        return [
            'party_id'     => $data['party_id'] ?? null,
            'date'         => $data['date'] ?? now()->toDateString(),
            'subtotal'     => $subtotal,
            'discount'     => $discount,
            'extra_label'  => $extra > 0 ? ($data['extra_label'] ?? 'অতিরিক্ত চার্জ') : null,
            'extra_charge' => $extra,
            'total'        => $total,
            'paid'         => $paid,
            'payment_mode' => $paid <= 0 && $trade ? 'credit' : ($data['payment_mode'] ?? 'cash'),
            'note'         => $data['note'] ?? null,
        ];
    }

    /** Save the lines and move each item's stock. */
    private function applyLines(KhataTransaction $tx, array $rows): void
    {
        $sign = self::STOCK_SIGN[$tx->type] ?? 0;
        foreach ($rows as [$item, $attrs]) {
            $tx->lines()->create($attrs);
            $item->stock = round((float) $item->stock + $sign * $attrs['quantity'], 3);
            if ($tx->type === 'purchase' && $attrs['price'] > 0) {
                $item->purchase_price = $attrs['price']; // latest buying price
            }
            if ($item->pack_kg === null && $attrs['pack_kg']) {
                $item->pack_kg = $attrs['pack_kg']; // first weight given becomes the item default
            }
            $item->save();
        }
    }

    /** Undo the stock movement of a transaction's current lines. */
    private function reverseStock(KhataTransaction $tx): void
    {
        $sign = self::STOCK_SIGN[$tx->type] ?? 0;
        if (! $sign) {
            return;
        }
        foreach ($tx->lines as $line) {
            $item = $line->item_id ? KhataItem::lockForUpdate()->find($line->item_id) : null;
            if ($item) {
                $item->update(['stock' => round((float) $item->stock - $sign * (float) $line->quantity, 3)]);
            }
        }
    }

    /** payment_in / payment_out / expense — a single amount. */
    public function recordAmount(Vendor $vendor, string $type, array $data): KhataTransaction
    {
        $amount = round(max(0, (float) $data['amount']), 2);

        return KhataTransaction::create([
            'vendor_id'    => $vendor->id,
            'party_id'     => $data['party_id'] ?? null,
            'type'         => $type,
            'number'       => $type === 'expense' ? null : $this->nextNumber($vendor, $type),
            'date'         => $data['date'] ?? now()->toDateString(),
            'subtotal'     => $amount,
            'total'        => $amount,
            'paid'         => $amount,
            'payment_mode' => $data['payment_mode'] ?? 'cash',
            'category'     => $data['category'] ?? null,
            'note'         => $data['note'] ?? null,
            'created_by'   => Auth::id(),
        ]);
    }

    /** Delete and put stock back. */
    public function delete(KhataTransaction $tx): void
    {
        DB::transaction(function () use ($tx) {
            $this->reverseStock($tx);
            $tx->lines()->delete();
            $tx->delete();
        });
    }

    /** Receivable (পাওনা) and payable (বকেয়া) across all parties. */
    public function dueTotals(Vendor $vendor): array
    {
        $receivable = 0.0;
        $payable    = 0.0;
        foreach (KhataParty::where('vendor_id', $vendor->id)->withBalance()->get() as $party) {
            $b = $party->balance();
            $b > 0 ? $receivable += $b : $payable += -$b;
        }

        return ['receivable' => round($receivable, 2), 'payable' => round($payable, 2)];
    }

    /** Figures for a period (inclusive dates). */
    public function summary(Vendor $vendor, Carbon $from, Carbon $to): array
    {
        $base = fn () => KhataTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString().' 23:59:59']);
        $sum = fn (string $type, string $col = 'total') => (float) $base()->where('type', $type)->sum($col);

        $cogs = (float) DB::table('khata_transaction_items')
            ->join('khata_transactions', 'khata_transactions.id', '=', 'khata_transaction_items.transaction_id')
            ->where('khata_transactions.vendor_id', $vendor->id)
            ->where('khata_transactions.type', 'sale')
            ->whereBetween('khata_transactions.date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->sum(DB::raw('khata_transaction_items.quantity * khata_transaction_items.cost'));

        $sales    = $sum('sale');
        $purchase = $sum('purchase');
        $expense  = $sum('expense');
        // Sales value net of invoice discount / extra charge for profit: use line revenue.
        $saleItems = $sum('sale', 'subtotal') - $sum('sale', 'discount');
        $gross     = round($saleItems - $cogs, 2);

        $cashIn  = $sum('sale', 'paid') + $sum('payment_in', 'paid');
        $cashOut = $sum('purchase', 'paid') + $sum('payment_out', 'paid') + $sum('expense', 'paid');

        return [
            'sales'        => round($sales, 2),
            'sales_count'  => $base()->where('type', 'sale')->count(),
            'purchase'     => round($purchase, 2),
            'expense'      => round($expense, 2),
            'payment_in'   => round($sum('payment_in', 'paid'), 2),
            'payment_out'  => round($sum('payment_out', 'paid'), 2),
            'cogs'         => round($cogs, 2),
            'gross_profit' => $gross,
            'net_profit'   => round($gross - $expense, 2),
            'cash_in'      => round($cashIn, 2),
            'cash_out'     => round($cashOut, 2),
            'sale_due'     => round($sales - $sum('sale', 'paid'), 2),
        ];
    }

    /** Cash & bank balance since the beginning. */
    public function cashBalance(Vendor $vendor): float
    {
        $s = $this->summary($vendor, Carbon::create(2000, 1, 1), now()->addYear());

        return round($s['cash_in'] - $s['cash_out'], 2);
    }
}
