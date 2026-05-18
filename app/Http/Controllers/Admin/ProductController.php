<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('sort_order')->orderBy('id')->paginate(20);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.create');
    }

    public function store(Request $request)
    {
        $data    = $this->productData($request);
        $product = Product::create($data);
        $product->syncPrices();

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'পণ্য তৈরি হয়েছে। সব প্যাকের দাম স্বয়ংক্রিয়ভাবে সেট হয়েছে।');
    }

    public function show(Product $product)
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function edit(Product $product)
    {
        $prices = $product->prices()->get();

        return view('admin.products.edit', compact('product', 'prices'));
    }

    public function update(Request $request, Product $product)
    {
        $data         = $this->productData($request, $product->id);
        $priceChanged = (float) $product->retail_price_1kg !== (float) $data['retail_price_1kg'];

        $product->update($data);

        if ($priceChanged) {
            $product->syncPrices();
        }

        $this->savePriceOverrides($request, $product);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'পণ্য আপডেট হয়েছে।');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'পণ্য মুছে ফেলা হয়েছে।');
    }

    private function productData(Request $request, ?int $ignoreId = null): array
    {
        $request->validate([
            'name_bn'             => 'required|string|max:255',
            'name_en'             => 'nullable|string|max:255',
            'slug'                => ['required', 'string', 'max:255',
                                      Rule::unique('products', 'slug')->ignore($ignoreId)],
            'main_image'          => 'nullable|string|max:500',
            'video_url'           => 'nullable|string|max:500',
            'short_description'   => 'nullable|string|max:500',
            'description'         => 'nullable|string',
            'retail_price_1kg'    => 'required|numeric|min:0.01',
            'wholesale_price_1kg' => 'nullable|numeric|min:0',
            'stock'               => 'required|integer|min:0',
        ]);

        return [
            'name_bn'             => $request->name_bn,
            'name_en'             => $request->name_en,
            'slug'                => $request->slug,
            'main_image'          => $request->main_image ?: null,
            'video_url'           => $request->video_url ?: null,
            'short_description'   => $request->short_description ?: null,
            'description'         => $request->description ?: null,
            'retail_price_1kg'    => $request->retail_price_1kg,
            'wholesale_price_1kg' => $request->wholesale_price_1kg ?: null,
            'stock'               => $request->stock,
            'is_active'           => $request->boolean('is_active'),
        ];
    }

    private function savePriceOverrides(Request $request, Product $product): void
    {
        if (! $request->has('prices')) {
            return;
        }

        foreach ($request->input('prices') as $priceId => $data) {
            $row = ProductPrice::where('id', $priceId)
                ->where('product_id', $product->id)
                ->first();

            if (! $row) {
                continue;
            }

            $manualPrice = (isset($data['manual_price']) && $data['manual_price'] !== '')
                ? (float) $data['manual_price']
                : null;

            $row->is_manual_override = ! empty($data['is_manual_override']) && $manualPrice !== null;
            $row->manual_price       = $manualPrice;
            $row->final_price        = $row->is_manual_override ? $manualPrice : $row->auto_price;
            $row->is_active          = ! empty($data['is_active']);
            $row->save();
        }
    }
}
