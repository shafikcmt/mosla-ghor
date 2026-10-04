<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\WebsiteSetting;
use App\Services\StockService;
use App\Support\VendorSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Vendor stock screen — a simple "stock in / stock out / count" ledger in the
 * spirit of khata-style apps. Every change goes through {@see StockService} so
 * history stays complete. Stock-only vendors (Vendor::isStockOnly) land here
 * and see nothing else.
 */
class StockController extends Controller
{
    /** Bangla labels for Product::UNITS. */
    public const UNIT_LABELS = [
        'kg'     => 'কেজি',
        'gram'   => 'গ্রাম',
        'pcs'    => 'পিস',
        'bag'    => 'বস্তা',
        'carton' => 'কার্টন',
        'packet' => 'প্যাকেট',
    ];

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
        // An admin-assigned stock-only vendor always keeps stock access.
        if (! $vendor->isStockOnly() && ! VendorSettings::vendorCanManageStock()) {
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

    /** Stock-only vendors can always add stock items; others follow the global toggle. */
    private function canQuickAdd($vendor): bool
    {
        return $vendor->isStockOnly() || VendorSettings::vendorCanAddProduct();
    }

    public static function unitLabel(?string $unit): string
    {
        return self::UNIT_LABELS[$unit ?: 'kg'] ?? ($unit ?: 'কেজি');
    }

    /** Compact payload used by the Alpine screen (and returned after each change). */
    private function card(Product $p): array
    {
        return [
            'id'        => $p->id,
            'name'      => $p->name_bn ?: $p->name_en,
            'sku'       => $p->sku,
            'unit'      => self::unitLabel($p->stockUnit()),
            'onhand'    => round($p->onHand(), 3),
            'threshold' => round((float) $p->low_stock_threshold, 3),
            'status'    => $p->stockStatus(),
            'price'     => (float) ($p->purchase_price ?? $p->selling_price ?? 0),
            'whole'     => ! $p->isUnitManaged(),
        ];
    }

    public function index(Request $request)
    {
        $vendor = $this->guard();

        $products = $vendor->products()->orderBy('name_bn')->get();

        $todayIn  = $vendor->stockMovements()->whereDate('created_at', today())->where('quantity', '>', 0)->count();
        $todayOut = $vendor->stockMovements()->whereDate('created_at', today())->where('quantity', '<', 0)->count();

        $summary = [
            'total'       => $products->count(),
            'low'         => $products->filter->isLowStock()->count(),
            'out'         => $products->filter(fn ($p) => $p->stockStatus() === 'out_of_stock')->count(),
            'stock_value' => $products->sum(fn ($p) => $p->onHand() * (float) ($p->purchase_price ?? $p->selling_price ?? 0)),
            'today_in'    => $todayIn,
            'today_out'   => $todayOut,
        ];

        $recent = $vendor->stockMovements()->with('product')->latest()->limit(8)->get();

        return view('vendor.stock.index', [
            'vendor'      => $vendor,
            'cards'       => $products->map(fn ($p) => $this->card($p))->values(),
            'summary'     => $summary,
            'recent'      => $recent,
            'canQuickAdd' => $this->canQuickAdd($vendor),
            'units'       => self::UNIT_LABELS,
            'filter'      => $request->query('status', 'all'),
        ]);
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

        if ($data['mode'] !== 'set' && $qty <= 0) {
            return $this->respond($request, false, 'পরিমাণ ০-এর বেশি দিন।');
        }

        try {
            match ($data['mode']) {
                'add'    => $this->stock->add($product, $qty, $opts),
                'reduce' => $this->stock->reduce($product, $qty, $opts),
                'set'    => $this->stock->adjust($product, $qty, $opts),
            };
        } catch (\RuntimeException $e) {
            return $this->respond($request, false, $e->getMessage());
        }

        $message = match ($data['mode']) {
            'add'    => 'স্টক ইন হয়েছে।',
            'reduce' => 'স্টক আউট হয়েছে।',
            'set'    => 'স্টক গণনা আপডেট হয়েছে।',
        };

        return $this->respond($request, true, $message, $product->fresh());
    }

    /** Update the low-stock alert level for one item. */
    public function threshold(Request $request)
    {
        $this->guard();

        $data = $request->validate([
            'product_id'          => 'required|integer|exists:products,id',
            'low_stock_threshold' => 'required|numeric|min:0',
        ]);

        $product = $this->ownProduct((int) $data['product_id']);
        $product->update(['low_stock_threshold' => $data['low_stock_threshold']]);

        return $this->respond($request, true, 'কম-স্টক সতর্কতা সেট হয়েছে।', $product->fresh());
    }

    /**
     * Quick-add a stock item (name + unit + opening stock). Items created here
     * are inventory-only: inactive and hidden from the storefront.
     */
    public function quickAdd(Request $request)
    {
        $vendor = $this->guard();
        if (! $this->canQuickAdd($vendor)) {
            abort(403, 'পণ্য যোগ করার অনুমতি নেই।');
        }

        $data = $request->validate([
            'name'                => 'required|string|max:150',
            'unit'                => ['required', Rule::in(Product::UNITS)],
            'opening_stock'       => 'nullable|numeric|min:0',
            'purchase_price'      => 'nullable|numeric|min:0',
            'selling_price'       => 'nullable|numeric|min:0',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'sku'                 => 'nullable|string|max:100',
        ], [
            'name.required' => 'পণ্যের নাম দিন।',
        ]);

        $autoApprove = $vendor->isStockOnly()
            || $vendor->product_auto_approve
            || filter_var(WebsiteSetting::get('vendor_product_auto_approve', '0'), FILTER_VALIDATE_BOOLEAN);

        $product = Product::create([
            'vendor_id'           => $vendor->id,
            'approval_status'     => $autoApprove ? 'approved' : 'pending',
            'name_bn'             => $data['name'],
            'slug'                => $this->uniqueSlug($data['name']),
            'sku'                 => $data['sku'] ?? null,
            'unit'                => $data['unit'],
            'retail_price_1kg'    => (float) ($data['selling_price'] ?? 0),
            'purchase_price'      => $data['purchase_price'] ?? null,
            'selling_price'       => $data['selling_price'] ?? null,
            'stock'               => 0,
            'stock_qty'           => 0,
            'low_stock_threshold' => $data['low_stock_threshold'] ?? 0,
            'is_active'           => false,
            'show_in_retail'      => false,
            'show_in_wholesale'   => false,
        ]);

        $opening = (float) ($data['opening_stock'] ?? 0);
        if ($opening > 0) {
            $this->stock->add($product, $opening, ['note' => 'শুরুর স্টক', 'created_by' => Auth::id()]);
        }

        return $this->respond($request, true, '"' . $product->name_bn . '" যোগ হয়েছে।', $product->fresh());
    }

    public function history(Request $request)
    {
        $vendor = $this->guard();

        $query = $vendor->stockMovements()->with(['product', 'creator'])->latest();

        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->product_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $movements = $query->paginate(30)->withQueryString();
        $products  = $vendor->products()->orderBy('name_bn')->get(['id', 'name_bn', 'name_en']);

        return view('vendor.stock.history', compact('vendor', 'movements', 'products'));
    }

    private function respond(Request $request, bool $ok, string $message, ?Product $product = null)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'ok'      => $ok,
                'message' => $message,
                'product' => $product ? $this->card($product) : null,
            ], $ok ? 200 : 422);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'item';
        $base = Str::limit($base, 60, '');

        do {
            $slug = $base . '-' . strtolower(Str::random(6));
        } while (Product::where('slug', $slug)->exists());

        return $slug;
    }
}
