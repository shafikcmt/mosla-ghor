<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Concerns\ManagesProductVariants;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\WebsiteSetting;
use App\Support\VendorSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use ManagesProductVariants;

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

    /** Active top-level categories with their children, for the product form select. */
    private function categoryOptions()
    {
        return Category::whereNull('parent_id')
            ->with(['children' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->where('is_active', true)
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

        $data    = $this->productData($request);
        $autoApprove = $vendor->product_auto_approve
            || filter_var(WebsiteSetting::get('vendor_product_auto_approve', '0'), FILTER_VALIDATE_BOOLEAN);

        $data['vendor_id']       = $vendor->id;
        $data['approval_status'] = $autoApprove ? 'approved' : 'pending';

        $product = Product::create($data);

        $fileUpdates = $this->processFileUploads($request, $product);
        if (! empty($fileUpdates)) {
            $product->update($fileUpdates);
        }

        $product->syncPrices();
        $this->saveVariants($request, $product);

        return redirect()->route('vendor.products.edit', $product)
            ->with('success', 'পণ্য তৈরি হয়েছে।' . ($autoApprove ? '' : ' অ্যাডমিন অনুমোদনের পর দেখাবে।'));
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
        $this->authorizeProduct($product);

        $data         = $this->productData($request, $product->id);
        $priceChanged = (float) $product->retail_price_1kg !== (float) $data['retail_price_1kg'];

        $fileUpdates = $this->processFileUploads($request, $product);
        $data        = array_merge($data, $fileUpdates);

        $product->update($data);

        if ($priceChanged) {
            $product->syncPrices();
        }

        $this->savePriceOverrides($request, $product);
        $this->saveVariants($request, $product);

        return redirect()->route('vendor.products.edit', $product)
            ->with('success', 'পণ্য আপডেট হয়েছে।');
    }

    public function destroy(Product $product)
    {
        $this->authorizeProduct($product);
        $this->deleteProductFiles($product);
        $product->delete();

        return redirect()->route('vendor.products.index')
            ->with('success', 'পণ্য মুছে ফেলা হয়েছে।');
    }

    private function authorizeProduct(Product $product): void
    {
        if ($product->vendor_id !== $this->vendor()?->id) {
            abort(403, 'এই পণ্যে আপনার অ্যাক্সেস নেই।');
        }
    }

    private function productData(Request $request, ?int $ignoreId = null): array
    {
        $request->validate([
            'name_bn'             => 'required|string|max:255',
            'slug'                => ['nullable', 'string', 'max:255',
                                      Rule::unique('products', 'slug')->ignore($ignoreId)],
            'category_id'         => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'short_description'   => 'nullable|string|max:500',
            'description'         => 'nullable|string',
            'retail_price_1kg'    => 'required|numeric|min:0.01',
            'stock'               => 'required|integer|min:0',
            'sku'                 => 'nullable|string|max:100',
            'category'            => 'nullable|string|max:100',
            'brand'               => 'nullable|string|max:100',
            'unit'                => ['nullable', Rule::in(\App\Models\Product::UNITS)],
            'purchase_price'      => 'nullable|numeric|min:0',
            'selling_price'       => 'nullable|numeric|min:0',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'main_image_file'     => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'gallery_images.*'    => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'video_file'          => 'nullable|file|mimes:mp4,webm,mov|max:51200',
            'min_order_quantity'  => 'nullable|numeric|min:0',
            'min_order_unit'      => ['nullable', Rule::in(\App\Models\Product::UNITS)],
            'delivery_time'       => 'nullable|string|max:255',
            'payment_terms'       => 'nullable|string|max:255',
            'is_active'           => 'nullable|boolean',
            'show_in_retail'      => 'nullable|boolean',
            'show_in_wholesale'   => 'nullable|boolean',
        ]);

        $showInRetail    = $request->boolean('show_in_retail');
        $showInWholesale = $request->boolean('show_in_wholesale');

        return [
            'name_bn'                   => $request->name_bn,
            'slug'                      => $this->resolveSlug($request, $ignoreId),
            'category_id'               => $request->category_id ?: null,
            'video_url'                 => $request->video_url ?: null,
            'short_description'         => $request->short_description ?: null,
            'description'               => $request->description ?: null,
            'retail_price_1kg'          => $request->retail_price_1kg,
            'stock'                     => $request->stock,
            'sku'                       => $request->sku ?: null,
            'category'                  => $request->category ?: null,
            'brand'                     => $request->brand ?: null,
            'unit'                      => $request->unit ?: 'kg',
            'purchase_price'            => $request->purchase_price !== null && $request->purchase_price !== '' ? $request->purchase_price : null,
            'selling_price'             => $request->selling_price !== null && $request->selling_price !== '' ? $request->selling_price : null,
            'low_stock_threshold'       => $request->low_stock_threshold !== null && $request->low_stock_threshold !== '' ? $request->low_stock_threshold : 0,
            'is_active'                 => $request->boolean('is_active'),
            'show_in_retail'            => $showInRetail,
            'show_in_wholesale'         => $showInWholesale,
            // Legacy flag kept in sync: "wholesale-only" = wholesale but not retail.
            'is_wholesale'              => $showInWholesale && ! $showInRetail,
            'wholesale_enquiry_enabled' => $request->boolean('wholesale_enquiry_enabled'),
            'min_order_quantity'        => $request->min_order_quantity !== null && $request->min_order_quantity !== '' ? $request->min_order_quantity : null,
            'min_order_unit'            => $request->min_order_unit ?: 'kg',
            'delivery_time'             => $request->delivery_time ?: null,
            'payment_terms'             => $request->payment_terms ?: null,
        ];
    }

    /**
     * Build a unique slug from the given slug, else a transliterated name_bn,
     * then a timestamp. Mirrors the client-side generator so a vendor who never
     * opens the advanced panel still gets a valid slug.
     */
    private function resolveSlug(Request $request, ?int $ignoreId): string
    {
        $slug = $request->slug ? Str::slug($request->slug) : '';
        if ($slug === '') {
            $slug = Str::slug($this->transliterateBn($request->name_bn ?? ''));
        }
        if ($slug === '') {
            $slug = 'product-' . now()->timestamp;
        }

        $base = $slug;
        $i = 2;
        while (Product::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function transliterateBn(string $str): string
    {
        static $map = [
            'অ'=>'o','আ'=>'a','ই'=>'i','ঈ'=>'i','উ'=>'u','ঊ'=>'u','ঋ'=>'ri','এ'=>'e','ঐ'=>'oi','ও'=>'o','ঔ'=>'ou',
            'ক'=>'k','খ'=>'kh','গ'=>'g','ঘ'=>'gh','ঙ'=>'ng','চ'=>'ch','ছ'=>'chh','জ'=>'j','ঝ'=>'jh','ঞ'=>'n',
            'ট'=>'t','ঠ'=>'th','ড'=>'d','ঢ'=>'dh','ণ'=>'n','ত'=>'t','থ'=>'th','দ'=>'d','ধ'=>'dh','ন'=>'n',
            'প'=>'p','ফ'=>'ph','ব'=>'b','ভ'=>'bh','ম'=>'m','য'=>'j','র'=>'r','ল'=>'l',
            'শ'=>'sh','ষ'=>'sh','স'=>'s','হ'=>'h','ড়'=>'r','ঢ়'=>'rh','য়'=>'y','ৎ'=>'t','ং'=>'ng','ঃ'=>'h','ঁ'=>'',
            'া'=>'a','ি'=>'i','ী'=>'i','ু'=>'u','ূ'=>'u','ৃ'=>'ri','ে'=>'e','ৈ'=>'oi','ো'=>'o','ৌ'=>'ou','্'=>'',
            '০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9',
        ];

        return strtr($str, $map);
    }

    private function processFileUploads(Request $request, Product $product): array
    {
        $updates = [];

        if ($request->hasFile('main_image_file')) {
            $this->deleteLocalFile($product->main_image);
            $path = $request->file('main_image_file')->store('products/images', 'public');
            $updates['main_image'] = 'storage/' . $path;
        } elseif ($request->boolean('remove_main_image')) {
            $this->deleteLocalFile($product->main_image);
            $updates['main_image'] = null;
        }

        $currentGallery = $product->gallery_images ?? [];
        $galleryChanged = false;

        if ($request->has('remove_gallery')) {
            foreach ((array) $request->input('remove_gallery') as $imgPath) {
                $this->deleteLocalFile($imgPath);
                $currentGallery = array_values(
                    array_filter($currentGallery, fn($img) => $img !== $imgPath)
                );
            }
            $galleryChanged = true;
        }

        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $file) {
                $path = $file->store('products/images', 'public');
                $currentGallery[] = 'storage/' . $path;
            }
            $galleryChanged = true;
        }

        if ($galleryChanged) {
            $updates['gallery_images'] = ! empty($currentGallery) ? array_values($currentGallery) : null;
        }

        if ($request->hasFile('video_file')) {
            $this->deleteLocalFile($product->video_path ?? null);
            $path = $request->file('video_file')->store('products/videos', 'public');
            $updates['video_path'] = 'storage/' . $path;
        } elseif ($request->boolean('remove_video')) {
            $this->deleteLocalFile($product->video_path ?? null);
            $updates['video_path'] = null;
        }

        return $updates;
    }

    private function deleteLocalFile(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http') || ! str_starts_with($path, 'storage/')) {
            return;
        }
        Storage::disk('public')->delete(preg_replace('#^storage/#', '', $path));
    }

    private function deleteProductFiles(Product $product): void
    {
        $this->deleteLocalFile($product->main_image);
        $this->deleteLocalFile($product->video_path ?? null);
        foreach ($product->gallery_images ?? [] as $img) {
            $this->deleteLocalFile($img);
        }
    }

    private function savePriceOverrides(Request $request, Product $product): void
    {
        if (! $request->has('prices')) return;

        foreach ($request->input('prices') as $priceId => $data) {
            $row = ProductPrice::where('id', $priceId)
                ->where('product_id', $product->id)
                ->where('sell_type', 'retail')
                ->first();
            if (! $row) continue;

            $manualPrice = (isset($data['manual_price']) && $data['manual_price'] !== '')
                ? (float) $data['manual_price'] : null;

            $row->is_manual_override = ! empty($data['is_manual_override']) && $manualPrice !== null;
            $row->manual_price       = $manualPrice;
            $row->final_price        = $row->is_manual_override ? $manualPrice : $row->auto_price;
            $row->is_active          = ! empty($data['is_active']);
            $row->save();
        }
    }

}
