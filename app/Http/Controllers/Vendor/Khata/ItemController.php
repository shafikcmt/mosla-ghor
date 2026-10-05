<?php

namespace App\Http\Controllers\Vendor\Khata;

use App\Models\Khata\KhataItem;
use App\Models\Khata\KhataTransactionItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends KhataBaseController
{
    public function index(Request $request)
    {
        $vendor = $this->vendor();
        $items  = KhataItem::where('vendor_id', $vendor->id)
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->q.'%'))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->stock === 'low', fn ($q) => $q->whereNotNull('low_stock_alert')->whereColumn('stock', '<=', 'low_stock_alert'))
            ->when($request->stock === 'out', fn ($q) => $q->where('stock', '<=', 0))
            ->orderBy('name')->get();

        $sales = KhataTransactionItem::query()
            ->join('khata_transactions', 'khata_transactions.id', '=', 'khata_transaction_items.transaction_id')
            ->where('khata_transactions.vendor_id', $vendor->id)
            ->whereIn('khata_transactions.type', ['sale', 'purchase'])
            ->selectRaw('item_id, khata_transactions.type, SUM(khata_transaction_items.total) as amount')
            ->groupBy('item_id', 'khata_transactions.type')->get()
            ->groupBy('item_id');

        return view('vendor.khata.items.index', [
            'vendor'     => $vendor,
            'items'      => $items,
            'sales'      => $sales,
            'categories' => KhataItem::where('vendor_id', $vendor->id)->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'stockValue' => $items->sum(fn ($i) => $i->stockValue()),
        ]);
    }

    public function create()
    {
        $vendor = $this->vendor();

        return view('vendor.khata.items.form', [
            'vendor'     => $vendor,
            'item'       => new KhataItem(['unit' => 'kg']),
            'categories' => KhataItem::where('vendor_id', $vendor->id)->whereNotNull('category')->distinct()->pluck('category'),
        ]);
    }

    public function store(Request $request)
    {
        $vendor = $this->vendor();
        $data   = $this->validated($request);

        $item = KhataItem::create($data + ['vendor_id' => $vendor->id, 'stock' => 0]);
        $opening = (float) $request->input('opening_stock', 0);
        if ($opening > 0) {
            $this->book->recordWithItems($vendor, 'stock_in', ['note' => 'প্রারম্ভিক স্টক'], [
                ['item_id' => $item->id, 'quantity' => $opening, 'price' => (float) $item->purchase_price],
            ]);
        }

        if ($request->boolean('add_another')) {
            return redirect()->route('vendor.khata.items.create')->with('success', "\"{$item->name}\" যোগ হয়েছে — পরেরটি দিন।");
        }

        return redirect()->route('vendor.khata.items.show', $item)->with('success', 'আইটেম যোগ হয়েছে।');
    }

    public function show(KhataItem $item)
    {
        $this->ownItem($item);
        $lines = $item->lines()->with('transaction.party')->latest('id')->paginate(25);
        $stats = $item->lines()->join('khata_transactions', 'khata_transactions.id', '=', 'khata_transaction_items.transaction_id')
            ->selectRaw("SUM(CASE WHEN khata_transactions.type = 'sale' THEN khata_transaction_items.total ELSE 0 END) as sold,
                SUM(CASE WHEN khata_transactions.type = 'sale' THEN quantity ELSE 0 END) as sold_qty,
                SUM(CASE WHEN khata_transactions.type = 'purchase' THEN khata_transaction_items.total ELSE 0 END) as bought")
            ->first();

        return view('vendor.khata.items.show', ['vendor' => $this->vendor(), 'item' => $item, 'lines' => $lines, 'stats' => $stats]);
    }

    public function edit(KhataItem $item)
    {
        $this->ownItem($item);
        $vendor = $this->vendor();

        return view('vendor.khata.items.form', [
            'vendor'     => $vendor,
            'item'       => $item,
            'categories' => KhataItem::where('vendor_id', $vendor->id)->whereNotNull('category')->distinct()->pluck('category'),
        ]);
    }

    public function update(Request $request, KhataItem $item)
    {
        $this->ownItem($item);
        $item->update($this->validated($request, $item));

        return redirect()->route('vendor.khata.items.show', $item)->with('success', 'আইটেম আপডেট হয়েছে।');
    }

    public function destroy(KhataItem $item)
    {
        $this->ownItem($item);
        if ($item->lines()->whereHas('transaction', fn ($q) => $q->whereIn('type', ['sale', 'purchase']))->exists()) {
            $item->update(['is_active' => false]);

            return redirect()->route('vendor.khata.items.index')->with('success', "\"{$item->name}\"-এর বিক্রি/ক্রয় আছে, তাই লুকিয়ে রাখা হলো (হিসাব ঠিক থাকবে)।");
        }
        $item->lines()->each(fn ($l) => $l->transaction && $l->transaction->lines()->count() === 1 ? $l->transaction->delete() : $l->delete());
        $item->delete();

        return redirect()->route('vendor.khata.items.index')->with('success', 'আইটেম মুছে ফেলা হয়েছে।');
    }

    /** স্টক যোগ / স্টক কমান. */
    public function adjust(Request $request, KhataItem $item)
    {
        $this->ownItem($item);
        $data = $request->validate([
            'direction' => ['required', 'in:in,out'],
            'quantity'  => ['required', 'numeric', 'min:0.001'],
            'price'     => ['nullable', 'numeric', 'min:0'],
            'date'      => ['nullable', 'date'],
            'note'      => ['nullable', 'string', 'max:300'],
        ], ['quantity.required' => 'পরিমাণ দিন।']);

        $this->book->recordWithItems($this->vendor(), $data['direction'] === 'in' ? 'stock_in' : 'stock_out', [
            'date' => $data['date'] ?? null, 'note' => $data['note'] ?? null,
        ], [[
            'item_id'  => $item->id,
            'quantity' => $data['quantity'],
            'price'    => $data['price'] ?? (float) $item->purchase_price,
        ]]);

        return back()->with('success', $data['direction'] === 'in' ? 'স্টক যোগ হয়েছে।' : 'স্টক কমানো হয়েছে।');
    }

    private function validated(Request $request, ?KhataItem $item = null): array
    {
        $v = $request->validate([
            'name'            => ['required', 'string', 'max:150',
                Rule::unique('khata_items', 'name')->where('vendor_id', $this->vendor()->id)->ignore($item?->id)],
            'category'        => ['nullable', 'string', 'max:80'],
            'unit'            => ['required', 'string', 'max:20'],
            'sale_price'      => ['nullable', 'numeric', 'min:0'],
            'purchase_price'  => ['nullable', 'numeric', 'min:0'],
            'low_stock_alert' => ['nullable', 'numeric', 'min:0'],
            'note'            => ['nullable', 'string', 'max:500'],
            'is_active'       => ['nullable', 'boolean'],
        ], [
            'name.required' => 'আইটেমের নাম দিন।',
            'name.unique'   => 'এই নামে আইটেম আগেই আছে।',
        ]);
        $v['sale_price']     = $v['sale_price'] ?? 0;
        $v['purchase_price'] = $v['purchase_price'] ?? 0;
        $v['is_active']      = true;

        return $v;
    }
}
