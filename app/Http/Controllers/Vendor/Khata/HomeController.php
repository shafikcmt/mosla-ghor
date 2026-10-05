<?php

namespace App\Http\Controllers\Vendor\Khata;

use App\Models\Khata\KhataItem;
use App\Models\Khata\KhataParty;
use App\Models\Khata\KhataTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends KhataBaseController
{
    public function home()
    {
        $vendor = $this->vendor();
        $month  = $this->book->summary($vendor, now()->startOfMonth(), now()->endOfMonth());
        $today  = $this->book->summary($vendor, now()->startOfDay(), now()->endOfDay());

        return view('vendor.khata.home', [
            'vendor'  => $vendor,
            'dues'    => $this->book->dueTotals($vendor),
            'month'   => $month,
            'today'   => $today,
            'cash'    => $this->book->cashBalance($vendor),
            'recent'  => KhataTransaction::where('vendor_id', $vendor->id)->with('party')->latest('date')->latest('id')->limit(8)->get(),
            'lowStock'=> KhataItem::where('vendor_id', $vendor->id)->whereNotNull('low_stock_alert')
                ->whereColumn('stock', '<=', 'low_stock_alert')->orderBy('stock')->limit(5)->get(),
            'counts'  => [
                'items'   => KhataItem::where('vendor_id', $vendor->id)->count(),
                'parties' => KhataParty::where('vendor_id', $vendor->id)->count(),
            ],
        ]);
    }

    /** লেনদেন — every transaction, filterable. */
    public function transactions(Request $request)
    {
        $vendor = $this->vendor();
        [$from, $to, $period] = $this->period($request, 'month');

        $q = KhataTransaction::where('vendor_id', $vendor->id)->with('party')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('q'), function ($q) use ($request) {
                $s = $request->q;
                $q->where(fn ($w) => $w->where('note', 'like', "%{$s}%")->orWhere('number', $s)
                    ->orWhereHas('party', fn ($p) => $p->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")));
            })
            ->latest('date')->latest('id');

        return view('vendor.khata.transactions', [
            'vendor'       => $vendor,
            'transactions' => $q->paginate(30)->withQueryString(),
            'period'       => $period, 'from' => $from, 'to' => $to,
        ]);
    }

    /** রিপোর্ট — daily / weekly / monthly / yearly / custom. */
    public function report(Request $request)
    {
        $vendor = $this->vendor();
        [$from, $to, $period] = $this->period($request, 'month');
        $summary = $this->book->summary($vendor, $from, $to);

        // Breakdown rows: per day (≤ 62 days) else per month.
        $byMonth = $from->diffInDays($to) > 62;
        $driver  = DB::getDriverName();
        $bucket  = $byMonth
            ? ($driver === 'sqlite' ? "strftime('%Y-%m', date)" : "DATE_FORMAT(date, '%Y-%m')")
            : ($driver === 'sqlite' ? "strftime('%Y-%m-%d', date)" : 'DATE(date)');
        $rows = KhataTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->selectRaw("{$bucket} as bucket,
                SUM(CASE WHEN type = 'sale' THEN total ELSE 0 END) as sales,
                SUM(CASE WHEN type = 'purchase' THEN total ELSE 0 END) as purchase,
                SUM(CASE WHEN type = 'expense' THEN total ELSE 0 END) as expense,
                SUM(CASE WHEN type IN ('sale','payment_in') THEN paid ELSE 0 END) as cash_in,
                SUM(CASE WHEN type IN ('purchase','payment_out','expense') THEN paid ELSE 0 END) as cash_out")
            ->groupBy('bucket')->orderBy('bucket')->get();

        $topItems = DB::table('khata_transaction_items')
            ->join('khata_transactions', 'khata_transactions.id', '=', 'khata_transaction_items.transaction_id')
            ->where('khata_transactions.vendor_id', $vendor->id)->where('khata_transactions.type', 'sale')
            ->whereBetween('khata_transactions.date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->selectRaw('khata_transaction_items.name, khata_transaction_items.unit, SUM(quantity) as qty, SUM(khata_transaction_items.total) as amount,
                SUM(khata_transaction_items.total - quantity * cost) as profit')
            ->groupBy('khata_transaction_items.name', 'khata_transaction_items.unit')
            ->orderByDesc('amount')->limit(10)->get();

        $expenses = KhataTransaction::where('vendor_id', $vendor->id)->where('type', 'expense')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->selectRaw("COALESCE(category, 'অন্যান্য') as category, SUM(total) as amount")
            ->groupBy('category')->orderByDesc('amount')->get();

        $parties = KhataParty::where('vendor_id', $vendor->id)->withBalance()->get()
            ->filter(fn ($p) => abs($p->balance()) >= 0.01)->sortByDesc(fn ($p) => abs($p->balance()))->values();

        return view('vendor.khata.report', compact('vendor', 'summary', 'rows', 'byMonth', 'topItems', 'expenses', 'parties', 'period', 'from', 'to') + [
            'stockValue' => KhataItem::where('vendor_id', $vendor->id)->where('stock', '>', 0)->get()->sum(fn ($i) => $i->stockValue()),
        ]);
    }

    public function settings()
    {
        return view('vendor.khata.settings', ['vendor' => $this->vendor()]);
    }

    public function saveSettings(Request $request)
    {
        $vendor = $this->vendor();
        $data = $request->validate([
            'terms'        => ['nullable', 'string', 'max:500'],
            'show_balance' => ['nullable', 'boolean'],
            'address'      => ['nullable', 'string', 'max:500'],
        ]);

        $vendor->update([
            'address'        => $data['address'] ?? $vendor->address,
            'khata_settings' => array_merge($vendor->khata_settings ?? [], [
                'terms'        => $data['terms'] ?? '',
                'show_balance' => $request->boolean('show_balance'),
            ]),
        ]);

        return back()->with('success', 'সেটিং সংরক্ষণ হয়েছে।');
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    protected function period(Request $request, string $default): array
    {
        $p = $request->input('period', $default);

        return match ($p) {
            'today'     => [now()->startOfDay(), now()->endOfDay(), $p],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay(), $p],
            'week'      => [now()->startOfWeek(Carbon::SATURDAY), now()->endOfWeek(Carbon::FRIDAY), $p],
            'year'      => [now()->startOfYear(), now()->endOfYear(), $p],
            'last_month'=> [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth(), $p],
            'custom'    => [
                Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay(),
                Carbon::parse($request->input('to', now()->toDateString()))->endOfDay(),
                $p,
            ],
            default     => [now()->startOfMonth(), now()->endOfMonth(), 'month'],
        };
    }
}
