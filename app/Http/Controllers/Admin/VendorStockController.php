<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class VendorStockController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index(Request $request)
    {
        // All vendor-owned products (admin oversight). vendor_id not null.
        $query = Product::whereNotNull('vendor_id')->with('vendor')->orderBy('name_bn');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->vendor_id);
        }
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

        $products = $query->paginate(25)->withQueryString();
        $vendors  = Vendor::orderBy('shop_name')->get(['id', 'shop_name']);

        return view('admin.vendor-stock.index', compact('products', 'vendors'));
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'mode'       => ['required', Rule::in(['add', 'reduce', 'set'])],
            'quantity'   => 'required|numeric|min:0',
            'note'       => 'nullable|string|max:500',
        ]);

        $product = Product::findOrFail((int) $data['product_id']);
        $opts    = [
            'note'           => 'অ্যাডমিন: ' . ($data['note'] ?? ''),
            'created_by'     => Auth::id(),
            'allow_negative' => true, // admin override
        ];
        $qty = (float) $data['quantity'];

        match ($data['mode']) {
            'add'    => $this->stock->add($product, $qty, $opts),
            'reduce' => $this->stock->reduce($product, $qty, $opts),
            'set'    => $this->stock->adjust($product, $qty, $opts),
        };

        return back()->with('success', 'স্টক আপডেট হয়েছে।');
    }
}
