<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ComboController extends Controller
{
    private function vendor()
    {
        return Auth::user()->vendor;
    }

    private function requireApproved(): void
    {
        if (! $this->vendor()?->isApproved()) {
            abort(403, 'অ্যাকাউন্ট অনুমোদিত হয়নি।');
        }
    }

    public function index()
    {
        $vendor = $this->vendor();
        $combos = $vendor->combos()->withCount('items')->orderBy('sort_order')->orderByDesc('id')->get();

        return view('vendor.combos.index', compact('vendor', 'combos'));
    }

    public function create()
    {
        $this->requireApproved();
        $vendor   = $this->vendor();
        $products = $this->vendorActiveProducts($vendor);

        return view('vendor.combos.create', compact('vendor', 'products'));
    }

    public function store(Request $request)
    {
        $this->requireApproved();
        $vendor = $this->vendor();

        $data          = $this->validatedData($request);
        $data['vendor_id'] = $vendor->id;
        $combo         = Combo::create($data);
        $this->syncItems($request, $combo, $vendor);

        return redirect()->route('vendor.combos.index')
            ->with('success', 'কম্বো তৈরি হয়েছে।');
    }

    public function edit(Combo $combo)
    {
        $this->authorizeCombo($combo);
        $vendor   = $this->vendor();
        $products = $this->vendorActiveProducts($vendor);

        return view('vendor.combos.edit', compact('vendor', 'combo', 'products'));
    }

    public function update(Request $request, Combo $combo)
    {
        $this->authorizeCombo($combo);
        $vendor = $this->vendor();

        $data = $this->validatedData($request, $combo->id);
        $combo->update($data);
        $this->syncItems($request, $combo, $vendor);

        return redirect()->route('vendor.combos.index')
            ->with('success', 'কম্বো আপডেট হয়েছে।');
    }

    public function destroy(Combo $combo)
    {
        $this->authorizeCombo($combo);
        $combo->delete();

        return redirect()->route('vendor.combos.index')
            ->with('success', 'কম্বো মুছে ফেলা হয়েছে।');
    }

    public function toggle(Combo $combo)
    {
        $this->authorizeCombo($combo);
        $combo->update(['is_active' => ! $combo->is_active]);

        return back()->with('success', $combo->is_active ? 'কম্বো সক্রিয় হয়েছে।' : 'কম্বো নিষ্ক্রিয় হয়েছে।');
    }

    private function authorizeCombo(Combo $combo): void
    {
        if ($combo->vendor_id !== $this->vendor()?->id) {
            abort(403, 'এই কম্বোতে আপনার অ্যাক্সেস নেই।');
        }
    }

    private function vendorActiveProducts($vendor)
    {
        return Product::where('vendor_id', $vendor->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->with(['activePrices' => fn($q) => $q->with('variant')->orderBy('sell_type')->orderBy('quantity_gram')])
            ->orderBy('sort_order')
            ->get();
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $request->validate([
            'name'              => 'required|string|max:150',
            'slug'              => ['required', 'string', 'max:150', Rule::unique('combos', 'slug')->ignore($ignoreId)],
            'sell_type'         => ['required', 'string', 'in:retail,wholesale'],
            'short_description' => 'nullable|string|max:300',
            'badge_text'        => 'nullable|string|max:60',
            'sell_price'        => 'required|numeric|min:1',
            'sort_order'        => 'nullable|integer|min:0',
        ]);

        return [
            'name'              => $request->name,
            'slug'              => $request->slug,
            'sell_type'         => $request->sell_type,
            'short_description' => $request->short_description ?: null,
            'badge_text'        => $request->badge_text ?: null,
            'sell_price'        => $request->sell_price,
            'is_active'         => $request->boolean('is_active'),
            'sort_order'        => $request->sort_order ?? 0,
        ];
    }

    private function syncItems(Request $request, Combo $combo, $vendor): void
    {
        $requestItems = $request->input('items', []);
        $newItems     = [];

        foreach ($requestItems as $productId => $itemData) {
            if (empty($itemData['include'])) continue;

            $priceId = (int) ($itemData['price_id'] ?? 0);
            if ($priceId <= 0) continue;

            // Only allow prices from vendor's own approved products
            $productPrice = ProductPrice::where('id', $priceId)
                ->where('product_id', (int) $productId)
                ->where('sell_type', $combo->sell_type)
                ->where('is_active', true)
                ->whereHas('product', fn($q) => $q->where('vendor_id', $vendor->id))
                ->first();

            if (! $productPrice) continue;

            $newItems[] = [
                'sell_type'          => $combo->sell_type,
                'product_id'         => (int) $productId,
                'product_variant_id' => $productPrice->product_variant_id,
                'product_price_id'   => $productPrice->id,
                'quantity_gram'      => (int) $productPrice->quantity_gram,
                'unit_price'         => (float) $productPrice->final_price,
                'line_total'         => (float) $productPrice->final_price,
            ];
        }

        $combo->items()->delete();
        if (! empty($newItems)) {
            $combo->items()->createMany($newItems);
        }
    }
}
