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
            ->with(['activePrices' => fn($q) => $q->orderBy('quantity_gram')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $request->validate([
            'name'              => 'required|string|max:150',
            'slug'              => ['required', 'string', 'max:150', Rule::unique('combos', 'slug')->ignore($ignoreId)],
            'short_description' => 'nullable|string|max:300',
            'badge_text'        => 'nullable|string|max:60',
            'sell_price'        => 'required|numeric|min:1',
            'sort_order'        => 'nullable|integer|min:0',
        ]);

        return [
            'name'              => $request->name,
            'slug'              => $request->slug,
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

            $quantityGram = (int) ($itemData['quantity_gram'] ?? 0);
            if ($quantityGram <= 0) {
                continue;
            }

            $productPrice = ProductPrice::where('product_id', (int) $productId)
                ->where('quantity_gram', $quantityGram)
                ->where('is_active', true)
                ->first();

            if (! $productPrice) {
                continue;
            }

            $newItems[] = [
                'product_id'    => (int) $productId,
                'quantity_gram' => $quantityGram,
                'unit_price'    => (float) $productPrice->final_price,
                'line_total'    => (float) $productPrice->final_price,
            ];
        }

        $combo->items()->delete();

        if (! empty($newItems)) {
            $combo->items()->createMany($newItems);
        }
    }
}
