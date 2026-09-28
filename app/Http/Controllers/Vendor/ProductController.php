<?php

namespace App\Http\Controllers\Vendor;

use App\Services\ProductEditor;
use App\Support\ProductMedia;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\WebsiteSetting;
use App\Support\VendorSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{


    private function vendor()
    {
        return Auth::user()->vendor;
    }

    private function requireApproved()
    {
        if (! $this->vendor()?->isApproved()) {
            abort(403, 'অ্যাকাউন্ট অনুমোদিত হয়নি। পণ্য যোগ করতে পারবেন না।');
        }
    }

    private function requireCanAddProduct()
    {
        $this->requireApproved();
        if (! VendorSettings::vendorCanAddProduct()) {
            abort(403, 'পণ্য যোগ করার অনুমতি বন্ধ আছে।');
        }
    }

    public function index()
    {
        $vendor   = $this->vendor();
        $products = $vendor->products()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20);

        return view('vendor.products.index', compact('vendor', 'products'));
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
        $this->requireCanAddProduct();

        return view('vendor.products.create', [
            'vendor'     => $this->vendor(),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireCanAddProduct();
        $vendor = $this->vendor();
        $autoApprove = $vendor->product_auto_approve
            || filter_var(WebsiteSetting::get('vendor_product_auto_approve', '0'), FILTER_VALIDATE_BOOLEAN);
        $product = app(ProductEditor::class)->save($request, trusted: [
            'vendor_id' => $vendor->id, 'approval_status' => $autoApprove ? 'approved' : 'pending',
        ]);
        return $this->redirectAfterSave($request, $product,
            'পণ্য তৈরি হয়েছে।' . ($autoApprove ? '' : ' অ্যাডমিন অনুমোদনের পর ওয়েবসাইটে দেখাবে।'));
    }

    /** Same check that guards create/store (approved vendor + permission on). */
    public static function canAddProducts(): bool
    {
        return (bool) Auth::user()?->vendor?->isApproved() && VendorSettings::vendorCanAddProduct();
    }

    /**
     * "সংরক্ষণ করে নতুন পণ্য যোগ করুন": exact value 'new' only. If this vendor may not add
     * products, the save still happens and they stay on the edit page with a note.
     */
    private function redirectAfterSave(Request $request, Product $product, string $message)
    {
        if ($request->input('after_save') === 'new') {
            if (self::canAddProducts()) {
                return redirect()->route('vendor.products.create')->with('pe_saved', [
                    'name' => $product->name_bn, 'edit_url' => route('vendor.products.edit', $product),
                ]);
            }
            return redirect()->route('vendor.products.edit', $product)->with('success', $message)
                ->with('pe_note', 'পণ্যটি সংরক্ষিত হয়েছে। এই মুহূর্তে নতুন পণ্য যোগ করার অনুমতি নেই, তাই এই পণ্যের পেজেই রাখা হলো।');
        }
        return redirect()->route('vendor.products.edit', $product)->with('success', $message);
    }

    public function edit(Product $product)
    {
        $this->authorizeProduct($product);

        $retailPrices = $product->prices()->whereNull('product_variant_id')->where('sell_type', 'retail')->get();
        $variants     = $product->variants()->orderBy('sort_order')->orderBy('id')->get();

        $categories = $this->categoryOptions();

        return view('vendor.products.edit', compact('product', 'retailPrices', 'variants', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $this->requireApproved();
        $this->authorizeProduct($product);
        $product = app(ProductEditor::class)->save($request, $product);
        return $this->redirectAfterSave($request, $product, 'পণ্য আপডেট হয়েছে।');
    }

    public function destroy(Product $product)
    {
        $this->authorizeProduct($product);
        $this->deleteProductFiles($product);

        return redirect()->route('vendor.products.index')
            ->with('success', 'পণ্য মুছে ফেলা হয়েছে।');
    }

    private function authorizeProduct(Product $product): void
    {
        if ($product->vendor_id !== $this->vendor()?->id) {
            abort(403, 'এই পণ্যে আপনার অ্যাক্সেস নেই।');
        }
    }

    private function deleteProductFiles(Product $product): void
    {
        $media = new ProductMedia();
        $media->retire($product->main_image);
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
