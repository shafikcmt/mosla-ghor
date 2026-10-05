<?php

namespace App\Http\Controllers\Admin;

use App\Services\ProductEditor;
use App\Support\ProductMedia;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{


    public function index(Request $request)
    {
        $filters = $this->listFilters($request);

        $products = $this->filteredProducts($filters)
            ->with('vendor:id,shop_name')
            ->withCount('variants')
            ->orderBy('sort_order')->orderBy('id')
            ->paginate(20)
            ->withQueryString();
        $stats = $this->stats();
        $quickEdit = $products->getCollection()->mapWithKeys(fn ($p) => [$p->id => $this->quickEditData($p)]);
        $categories = $this->categoryOptions();
        $vendors = Vendor::orderBy('shop_name')->get(['id', 'shop_name']);

        return view('admin.products.index', compact('products', 'stats', 'quickEdit', 'filters', 'categories', 'vendors'));
    }

    /** Whitelisted list filters from the query string (unknown values are ignored). */
    private function listFilters(Request $request): array
    {
        $pick = fn (string $key, array $allowed) => in_array($request->query($key), $allowed, true) ? $request->query($key) : '';
        $id = fn (string $key) => ctype_digit((string) $request->query($key)) ? (int) $request->query($key) : null;

        return [
            'search'      => trim(mb_substr((string) $request->query('search', ''), 0, 100)),
            'owner'       => $pick('owner', ['platform', 'vendor']),
            'status'      => $pick('status', ['active', 'inactive']),
            'channel'     => $pick('channel', ['retail', 'wholesale']),
            'category_id' => $id('category_id'),
            'vendor_id'   => $id('vendor_id'),
        ];
    }

    /** Search + filters combined with AND. Ownership follows vendor_id (null = platform). */
    private function filteredProducts(array $f)
    {
        return Product::query()
            ->when($f['search'] !== '', function ($q) use ($f) {
                // Explicit ESCAPE so % and _ match literally on both SQLite and MySQL.
                $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $f['search']) . '%';
                // Each column group is its own nested (a OR b) so it ANDs with the relation constraint.
                $like = fn (array $columns) => function ($query) use ($columns, $pattern) {
                    foreach ($columns as $column) {
                        $query->orWhereRaw("{$column} LIKE ? ESCAPE '!'", [$pattern]);
                    }
                };
                $q->where(function ($w) use ($like) {
                    $w->where($like(['products.name_bn', 'products.name_en', 'products.slug', 'products.sku', 'products.brand', 'products.category']))
                      ->orWhereHas('variants', fn ($v) => $v->where($like(['product_variants.sku'])))
                      ->orWhereHas('vendor', fn ($v) => $v->where($like(['vendors.shop_name', 'vendors.owner_name'])))
                      ->orWhereHas('category', fn ($c) => $c->where($like(['categories.name_bn', 'categories.name_en'])));
                });
            })
            ->when($f['owner'] === 'platform', fn ($q) => $q->whereNull('vendor_id'))
            ->when($f['owner'] === 'vendor', fn ($q) => $q->whereNotNull('vendor_id'))
            ->when($f['vendor_id'], fn ($q, $vendorId) => $q->where('vendor_id', $vendorId))
            ->when($f['status'] !== '', fn ($q) => $q->where('is_active', $f['status'] === 'active'))
            ->when($f['channel'] === 'retail', fn ($q) => $q->where('show_in_retail', true))
            ->when($f['channel'] === 'wholesale', fn ($q) => $q->where('show_in_wholesale', true))
            // A parent category also matches products filed under its sub-categories.
            ->when($f['category_id'], fn ($q, $categoryId) => $q->whereIn('category_id',
                Category::where('id', $categoryId)->orWhere('parent_id', $categoryId)->select('id')));
    }

    private function stats(): array
    {
        return [
            'total'     => Product::count(),
            'active'    => Product::where('is_active', true)->count(),
            'retail'    => Product::where('is_active', true)->where('show_in_retail', true)->count(),
            'wholesale' => Product::where('is_active', true)->where('show_in_wholesale', true)->count(),
        ];
    }

    /**
     * Inline "quick edit" from the product list: price, stock, visibility and order
     * only. Deliberately not routed through ProductEditor::save (no media/variants/tags).
     */
    public function quickUpdate(Request $request, Product $product)
    {
        $hasVariants = $product->variants()->exists();
        $unitManaged = $product->isUnitManaged();

        $validator = Validator::make($request->all(), [
            'retail_price_1kg'    => $hasVariants ? 'prohibited' : 'sometimes|nullable|numeric|min:0.01|max:99999999.99',
            'stock'               => $unitManaged ? 'prohibited' : 'sometimes|required|integer|min:0|max:2147483647',
            'low_stock_threshold' => 'sometimes|nullable|numeric|min:0|max:999999999.999',
            'sort_order'          => 'sometimes|nullable|integer|min:0|max:999999',
            'is_active'           => 'sometimes|boolean',
            'show_in_retail'      => 'sometimes|boolean',
            'show_in_wholesale'   => 'sometimes|boolean',
        ], [
            'retail_price_1kg.prohibited' => 'ভ্যারিয়েন্টের দাম পূর্ণ সম্পাদনায় বদলান।',
            'retail_price_1kg.numeric'    => 'খুচরা দাম একটি সংখ্যা হতে হবে।',
            'retail_price_1kg.min'        => 'খুচরা দাম ০-এর বেশি হতে হবে।',
            'retail_price_1kg.max'        => 'খুচরা দাম অনেক বেশি।',
            'stock.prohibited'            => 'এই পণ্যের স্টক স্টক ব্যবস্থাপনা পেজ থেকে বদলান।',
            'stock.required'              => 'স্টক দিন।',
            'stock.integer'               => 'স্টক পূর্ণ সংখ্যা হতে হবে।',
            'stock.min'                   => 'স্টক ০ বা তার বেশি হতে হবে।',
            'stock.max'                   => 'স্টক অনেক বেশি।',
            'low_stock_threshold.numeric' => 'কম স্টকের সীমা একটি সংখ্যা হতে হবে।',
            'low_stock_threshold.min'     => 'কম স্টকের সীমা ০ বা তার বেশি হতে হবে।',
            'low_stock_threshold.max'     => 'কম স্টকের সীমা অনেক বেশি।',
            'sort_order.integer'          => 'ক্রম পূর্ণ সংখ্যা হতে হবে।',
            'sort_order.min'              => 'ক্রম ০ বা তার বেশি হতে হবে।',
            'sort_order.max'              => 'ক্রম অনেক বেশি।',
            'is_active.boolean'           => 'সক্রিয় অবস্থা সঠিক নয়।',
            'show_in_retail.boolean'      => 'খুচরা অপশন সঠিক নয়।',
            'show_in_wholesale.boolean'   => 'পাইকারি অপশন সঠিক নয়।',
        ]);
        $validator->after(function ($v) use ($request, $product) {
            if ($v->errors()->isNotEmpty()) { return; }
            $retail = $request->has('show_in_retail') ? $request->boolean('show_in_retail') : (bool) $product->show_in_retail;
            $wholesale = $request->has('show_in_wholesale') ? $request->boolean('show_in_wholesale') : (bool) $product->show_in_wholesale;
            if (! $retail && ! $wholesale) {
                $v->errors()->add('show_in_retail', 'অন্তত একটি বিক্রয় মাধ্যম (খুচরা বা পাইকারি) চালু রাখুন।');
            }
            $price = $request->filled('retail_price_1kg') ? (float) $request->input('retail_price_1kg') : (float) $product->retail_price_1kg;
            if ($retail && $price <= 0) {
                $v->errors()->add('retail_price_1kg', $product->variants()->exists()
                    ? 'খুচরা চালু করতে পূর্ণ সম্পাদনায় দাম দিন।'
                    : 'খুচরা চালু করতে ১ কেজির দাম দিন।');
            }
        });
        $data = $validator->validate();

        $changes = DB::transaction(function () use ($product, $data) {
            $product = Product::lockForUpdate()->findOrFail($product->id);
            $wasRetail = (bool) $product->show_in_retail;
            $oldPrice = $product->retail_price_1kg;

            $fields = Arr::only($data, ['stock', 'is_active', 'show_in_retail', 'show_in_wholesale']);
            if (isset($data['retail_price_1kg'])) {
                $fields['retail_price_1kg'] = $data['retail_price_1kg'];
            }
            if (array_key_exists('low_stock_threshold', $data)) {
                $fields['low_stock_threshold'] = $data['low_stock_threshold'] ?? 0;
            }
            if (array_key_exists('sort_order', $data)) {
                $fields['sort_order'] = $data['sort_order'] ?? 0;
            }
            $product->fill($fields);
            $product->is_wholesale = $product->show_in_wholesale && ! $product->show_in_retail;

            $changes = [];
            foreach ($product->getDirty() as $key => $value) {
                $changes[$key] = ['old' => $product->getOriginal($key), 'new' => $product->{$key}];
            }
            $product->save();

            if ($product->shouldResyncPrices(false, $wasRetail, $oldPrice)) {
                $product->syncPrices(); // manual-override packs keep their final_price
            }

            return $changes;
        });

        $product->refresh();
        if ($changes) {
            Log::info('Admin product quick edit', [
                'user_id' => $request->user()?->id, 'product_id' => $product->id, 'changes' => $changes,
            ]);
        }

        $packs = $product->prices()->whereNull('product_variant_id')->where('sell_type', 'retail')->get()
            ->map(fn ($p) => [
                'label' => $p->label, 'final_price' => (float) $p->final_price,
                'is_manual_override' => (bool) $p->is_manual_override, 'is_active' => (bool) $p->is_active,
            ])->values();

        return response()->json([
            'message' => $changes ? 'পরিবর্তন সংরক্ষণ হয়েছে।' : 'কোনো পরিবর্তন ছিল না।',
            'product' => $this->quickEditData($product),
            'packs'   => $packs,
            'stats'   => $this->stats(),
        ]);
    }

    /** Row state shared by the list view (data attribute) and the quickUpdate JSON. */
    public function quickEditData(Product $product): array
    {
        return [
            'id'                  => $product->id,
            'retail_price_1kg'    => (float) $product->retail_price_1kg,
            'stock'               => (int) $product->stock,
            'low_stock_threshold' => (float) $product->low_stock_threshold,
            'sort_order'          => (int) $product->sort_order,
            'is_active'           => (bool) $product->is_active,
            'show_in_retail'      => (bool) $product->show_in_retail,
            'show_in_wholesale'   => (bool) $product->show_in_wholesale,
            'has_variants'        => ($product->variants_count ?? $product->variants()->count()) > 0,
            'unit_managed'        => $product->isUnitManaged(),
            'update_url'          => route('admin.products.quick-update', $product),
            'edit_url'            => route('admin.products.edit', $product),
        ];
    }

    /** Include existing inactive categories so editing preserves the selection. */
    private function categoryOptions()
    {
        return Category::whereNull('parent_id')
            ->with(['children' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }

    public function create()
    {
        return view('admin.products.create', ['categories' => $this->categoryOptions()]);
    }

    public function store(Request $request)
    {
        $product = app(ProductEditor::class)->save($request, admin: true);
        return $this->redirectAfterSave($request, $product, 'পণ্য তৈরি হয়েছে।');
    }

    /**
     * "সংরক্ষণ করে নতুন পণ্য যোগ করুন": only the exact value 'new' goes to a fresh create
     * form; anything else (or nothing) keeps today's redirect to the edit page.
     */
    private function redirectAfterSave(Request $request, Product $product, string $message)
    {
        if ($request->input('after_save') === 'new') {
            return redirect()->route('admin.products.create')->with('pe_saved', [
                'name' => $product->name_bn, 'edit_url' => route('admin.products.edit', $product),
            ]);
        }
        return redirect()->route('admin.products.edit', $product)->with('success', $message);
    }

    public function show(Product $product)
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function edit(Product $product)
    {
        $retailPrices = $product->prices()->whereNull('product_variant_id')->where('sell_type', 'retail')->get();
        $variants     = $product->variants()->orderBy('sort_order')->orderBy('id')->get();

        $categories = $this->categoryOptions();

        return view('admin.products.edit', compact('product', 'retailPrices', 'variants', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $product = app(ProductEditor::class)->save($request, $product, admin: true);
        return $this->redirectAfterSave($request, $product, 'পণ্য আপডেট হয়েছে।');
    }

    public function destroy(Product $product)
    {
        $this->deleteProductFiles($product);

        // Return to the same filtered/paged list the delete was made from.
        $index = route('admin.products.index');
        $previous = url()->previous();
        $target = ($previous === $index || str_starts_with($previous, $index . '?')) ? $previous : $index;

        return redirect()->to($target)
            ->with('success', 'পণ্য মুছে ফেলা হয়েছে।');
    }

    private function deleteProductFiles(Product $product): void
    {
        $media = new ProductMedia();
        $media->retire($product->main_image);
        $media->retire($product->wholesale_main_image);
        $media->retire($product->video_path);
        $media->retire($product->og_image);
        foreach ($product->gallery_images ?? [] as $path) { $media->retire($path); }
        foreach ($product->variants as $variant) { $media->retire($variant->image); }
        DB::transaction(function () use ($product, $media) {
            $product->delete();
            DB::afterCommit(fn () => $media->committed());
        });
    }
}
