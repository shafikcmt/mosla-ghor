<?php

namespace App\Http\Controllers\Admin;

use App\Services\ProductEditor;
use App\Support\ProductMedia;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{


    public function index()
    {
        $products = Product::orderBy('sort_order')->orderBy('id')->paginate(20);

        $stats = [
            'total'     => Product::count(),
            'active'    => Product::where('is_active', true)->count(),
            'retail'    => Product::where('is_active', true)->where('show_in_retail', true)->count(),
            'wholesale' => Product::where('is_active', true)->where('show_in_wholesale', true)->count(),
        ];

        return view('admin.products.index', compact('products', 'stats'));
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
        return redirect()->route('admin.products.edit', $product)->with('success', 'পণ্য তৈরি হয়েছে।');
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
        app(ProductEditor::class)->save($request, $product, admin: true);
        return redirect()->route('admin.products.edit', $product)->with('success', 'পণ্য আপডেট হয়েছে।');
    }

    public function destroy(Product $product)
    {
        $this->deleteProductFiles($product);

        return redirect()->route('admin.products.index')
            ->with('success', 'পণ্য মুছে ফেলা হয়েছে।');
    }

    private function deleteProductFiles(Product $product): void
    {
        $media = new ProductMedia();
        $media->retire($product->main_image);
        $media->retire($product->video_path);
        foreach ($product->gallery_images ?? [] as $path) { $media->retire($path); }
        foreach ($product->variants as $variant) { $media->retire($variant->image); }
        DB::transaction(function () use ($product, $media) {
            $product->delete();
            DB::afterCommit(fn () => $media->committed());
        });
    }
}
