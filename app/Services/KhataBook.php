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
 * when the transaction is deleted. Party balances are derived (never stored).
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
                    'quantity' => $qty,
                    'price'    => $price,
                    'cost'     => $type === 'purchase' ? $price : (float) $item->purchase_price,
                    'total'    => $total,
                ]];
            }

            $discount = min(max(0, (float) ($data['discount'] ?? 0)), $subtotal);
            $extra    = max(0, (float) ($data['extra_charge'] ?? 0));
            $total    = in_array($type, ['sale', 'purchase'], true) ? round($subtotal - $discount + $extra, 2) : round($subtotal, 2);
            $paid     = in_array($type, ['sale', 'purchase'], true) ? min(max(0, (float) ($data['paid'] ?? 0)), $total) : 0;

            $tx = KhataTransaction::create([
                'vendor_id'    => $vendor->id,
                'party_id'     => $data['party_id'] ?? null,
                'type'         => $type,
                'number'       => in_array($type, ['sale', 'purchase'], true) ? ($data['number'] ?? $this->nextNumber($vendor, $type)) : null,
                'date'         => $data['date'] ?? now()->toDateString(),
                'subtotal'     => $subtotal,
                'discount'     => $discount,
                'extra_label'  => $extra > 0 ? ($data['extra_label'] ?? 'অতিরিক্ত চার্জ') : null,
                'extra_charge' => $extra,
                'total'        => $total,
                'paid'         => $paid,
                'payment_mode' => $paid <= 0 && in_array($type, ['sale', 'purchase'], true) ? 'credit' : ($data['payment_mode'] ?? 'cash'),
                'note'         => $data['note'] ?? null,
                'created_by'   => Auth::id(),
            ]);

            $sign = self::STOCK_SIGN[$type] ?? 0;
            foreach ($rows as [$item, $attrs]) {
                $tx->lines()->create($attrs);
                $item->stock = round((float) $item->stock + $sign * $attrs['quantity'], 3);
                if ($type === 'purchase' && $attrs['price'] > 0) {
                    $item->purchase_price = $attrs['price']; // latest buying price
                }
                $item->save();
            }

            return $tx;
        });
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
            $sign = self::STOCK_SIGN[$tx->type] ?? 0;
            if ($sign) {
                foreach ($tx->lines as $line) {
                    $item = $line->item_id ? KhataItem::lockForUpdate()->find($line->item_id) : null;
                    if ($item) {
                        $item->update(['stock' => round((float) $item->stock - $sign * (float) $line->quantity, 3)]);
                    }
                }
            }
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
