<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComboController extends Controller
{
    public function index()
    {
        $combos = Combo::withCount('items')->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.combos.index', compact('combos'));
    }

    public function create()
    {
        $products    = $this->activeProducts();
        $currentItems = [];

        return view('admin.combos.create', compact('products', 'currentItems'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $combo = Combo::create($data);
        $this->syncItems($request, $combo);

        return redirect()->route('admin.combos.index')
            ->with('success', 'কম্বো তৈরি হয়েছে।');
    }

    public function edit(Combo $combo)
    {
        $products     = $this->activeProducts();
        $currentItems = $combo->items->keyBy('product_id');

        return view('admin.combos.edit', compact('combo', 'products', 'currentItems'));
    }

    public function update(Request $request, Combo $combo)
    {
        $data = $this->validatedData($request, $combo->id);
        $combo->update($data);
        $this->syncItems($request, $combo);

        return redirect()->route('admin.combos.index')
            ->with('success', 'কম্বো আপডেট হয়েছে।');
    }

    public function destroy(Combo $combo)
    {
        $combo->delete();

        return redirect()->route('admin.combos.index')
            ->with('success', 'কম্বো মুছে ফেলা হয়েছে।');
    }

    public function toggle(Combo $combo)
    {
        $combo->update(['is_active' => ! $combo->is_active]);

        return back()->with('success', $combo->is_active ? 'কম্বো সক্রিয় হয়েছে।' : 'কম্বো নিষ্ক্রিয় হয়েছে।');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function activeProducts()
    {
        return Product::active()
            ->with(['activePrices' => fn($q) => $q->with('variant')->orderBy('sell_type')->orderBy('quantity_gram')])
            ->orderBy('sort_order')
            ->orderBy('id')
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

    private function syncItems(Request $request, Combo $combo): void
    {
        $requestItems = $request->input('items', []);
        $newItems     = [];

        foreach ($requestItems as $productId => $itemData) {
            if (empty($itemData['include'])) {
                continue;
            }

            $priceId = (int) ($itemData['price_id'] ?? 0);
            if ($priceId <= 0) {
                continue;
            }

            $productPrice = ProductPrice::where('id', $priceId)
                ->where('product_id', (int) $productId)
                ->where('sell_type', $combo->sell_type)
                ->where('is_active', true)
                ->first();

            if (! $productPrice) {
                continue;
            }

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
