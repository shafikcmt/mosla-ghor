<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\StockService;
use App\Support\VendorSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    private function vendor()
    {
        return Auth::user()->vendor;
    }

    private function guard()
    {
        $vendor = $this->vendor();
        if (! $vendor?->isApproved()) {
            abort(403, 'অ্যাকাউন্ট অনুমোদিত হয়নি।');
        }
        if (! VendorSettings::vendorCanManageStock()) {
            abort(403, 'স্টক ম্যানেজমেন্ট বন্ধ আছে।');
        }
        return $vendor;
    }

    private function ownProduct(int $id): Product
    {
        $product = Product::findOrFail($id);
        if ($product->vendor_id !== $this->vendor()?->id) {
            abort(403, 'এই পণ্যে আপনার অ্যাক্সেস নেই।');
        }
        return $product;
    }

    public function index(Request $request)
    {
        $vendor = $this->guard();

        $query = $vendor->products()->orderBy('name_bn');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_bn', 'like', "%{$s}%")
                  ->orWhere('name_en', 'like', "%{$s}%")
                  ->orWhere('sku', 'like', "%{$s}%");
            });
        }

        if ($request->status === 'low') {
            $query->lowStock();
        } elseif ($request->status === 'out') {
            $query->outOfStock();
        }

        $products = $query->paginate(20)->withQueryString();

        // Summary over all of this vendor's products (small set).
        $all     = $vendor->products()->get();
        $summary = [
            'total'       => $all->count(),
            'low'         => $all->filter->isLowStock()->count(),
            'out'         => $all->filter(fn ($p) => $p->stockStatus() === 'out_of_stock')->count(),
            'stock_value' => $all->sum(fn ($p) => $p->onHand() * (float) ($p->purchase_price ?? $p->selling_price ?? 0)),
        ];

        return view('vendor.stock.index', compact('vendor', 'products', 'summary'));
    }

    public function adjust(Request $request)
    {
        $this->guard();

        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'mode'       => ['required', Rule::in(['add', 'reduce', 'set'])],
            'quantity'   => 'required|numeric|min:0',
            'note'       => 'nullable|string|max:500',
        ]);

        $product = $this->ownProduct((int) $data['product_id']);
        $opts    = ['note' => $data['note'] ?? null, 'created_by' => Auth::id()];
        $qty     = (float) $data['quantity'];

        try {
            match ($data['mode']) {
                'add'    => $this->stock->add($product, $qty, $opts),
                'reduce' => $this->stock->reduce($product, $qty, $opts),
                'set'    => $this->stock->adjust($product, $qty, $opts),
            };
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'স্টক আপডেট হয়েছে।');
    }

    public function history(Request $request)
    {
        $vendor = $this->guard();

        $query = $vendor->stockMovements()->with('product')->latest();

        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->product_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->paginate(30)->withQueryString();

        return view('vendor.stock.history', compact('vendor', 'movements'));
    }
}
