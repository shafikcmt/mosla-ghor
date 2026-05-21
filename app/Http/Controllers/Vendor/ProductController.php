<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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

    public function index()
    {
        $vendor   = $this->vendor();
        $products = $vendor->products()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20);

        return view('vendor.products.index', compact('vendor', 'products'));
    }

    public function create()
    {
        $this->requireApproved();

        return view('vendor.products.create', ['vendor' => $this->vendor()]);
    }

    public function store(Request $request)
    {
        $this->requireApproved();
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
        $this->saveWholesalePrices($request, $product);
        $this->saveVariants($request, $product);

        return redirect()->route('vendor.products.edit', $product)
            ->with('success', 'পণ্য তৈরি হয়েছে।' . ($autoApprove ? '' : ' অ্যাডমিন অনুমোদনের পর দেখাবে।'));
    }

    public function edit(Product $product)
    {
        $this->authorizeProduct($product);

        $retailPrices    = $product->prices()->whereNull('product_variant_id')->where('sell_type', 'retail')->get();
        $wholesalePrices = $product->prices()->whereNull('product_variant_id')->where('sell_type', 'wholesale')->get();
        $variants        = $product->variants()
            ->with(['prices' => fn($q) => $q->orderBy('sell_type')->orderBy('sort_order')->orderBy('quantity_gram')])
            ->orderBy('sort_order')
            ->get();

        return view('vendor.products.edit', compact('product', 'retailPrices', 'wholesalePrices', 'variants'));
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
        $this->saveWholesalePrices($request, $product);
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
            'name_en'             => 'nullable|string|max:255',
            'slug'                => ['required', 'string', 'max:255',
                                      Rule::unique('products', 'slug')->ignore($ignoreId)],
            'short_description'   => 'nullable|string|max:500',
            'description'         => 'nullable|string',
            'retail_price_1kg'    => 'required|numeric|min:0.01',
            'wholesale_price_1kg' => 'nullable|numeric|min:0',
            'stock'               => 'required|integer|min:0',
            'main_image_file'     => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'gallery_images.*'    => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'video_file'          => 'nullable|file|mimes:mp4,webm,mov|max:51200',
        ]);

        return [
            'name_bn'             => $request->name_bn,
            'name_en'             => $request->name_en,
            'slug'                => $request->slug,
            'video_url'           => $request->video_url ?: null,
            'short_description'   => $request->short_description ?: null,
            'description'         => $request->description ?: null,
            'retail_price_1kg'    => $request->retail_price_1kg,
            'wholesale_price_1kg' => $request->wholesale_price_1kg ?: null,
            'stock'               => $request->stock,
            'is_active'           => $request->boolean('is_active'),
        ];
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

    private function saveWholesalePrices(Request $request, Product $product): void
    {
        if ($request->has('wholesale_prices')) {
            foreach ($request->input('wholesale_prices') as $priceId => $data) {
                $row = ProductPrice::where('id', $priceId)
                    ->where('product_id', $product->id)
                    ->where('sell_type', 'wholesale')
                    ->first();
                if (! $row) continue;

                if (! empty($data['_delete'])) { $row->delete(); continue; }

                $finalPrice = (float) ($data['final_price'] ?? 0);
                if ($finalPrice <= 0) continue;

                $row->label         = $data['label'] ?? $row->label;
                $row->final_price   = $finalPrice;
                $row->auto_price    = $finalPrice;
                $row->min_order_qty = (isset($data['min_order_qty']) && $data['min_order_qty'] !== '') ? (int) $data['min_order_qty'] : null;
                $row->is_active     = ! empty($data['is_active']);
                $row->save();
            }
        }

        if ($request->has('new_wholesale_prices')) {
            foreach ($request->input('new_wholesale_prices') as $data) {
                $label        = trim($data['label'] ?? '');
                $quantityGram = (int) ($data['quantity_gram'] ?? 0);
                $finalPrice   = (float) ($data['final_price'] ?? 0);
                if (! $label || $quantityGram <= 0 || $finalPrice <= 0) continue;

                $product->prices()->create([
                    'sell_type'          => 'wholesale',
                    'label'              => $label,
                    'quantity_gram'      => $quantityGram,
                    'auto_price'         => $finalPrice,
                    'manual_price'       => null,
                    'final_price'        => $finalPrice,
                    'is_manual_override' => false,
                    'is_active'          => ! empty($data['is_active']),
                    'min_order_qty'      => (isset($data['min_order_qty']) && $data['min_order_qty'] !== '') ? (int) $data['min_order_qty'] : null,
                ]);
            }
        }
    }

    private function saveVariants(Request $request, Product $product): void
    {
        if ($request->has('variants')) {
            foreach ($request->input('variants') as $variantId => $data) {
                $variant = $product->variants()->find((int) $variantId);
                if (! $variant) continue;
                if (! empty($data['_delete'])) { $variant->delete(); continue; }

                $name = trim($data['name'] ?? '');
                if (! $name) continue;

                $variant->update([
                    'name'       => $name,
                    'origin'     => ($data['origin'] ?? '') ?: null,
                    'grade'      => ($data['grade'] ?? '') ?: null,
                    'size_label' => ($data['size_label'] ?? '') ?: null,
                    'sort_order' => (int) ($data['sort_order'] ?? 0),
                    'is_active'  => ! empty($data['is_active']),
                ]);
                $this->saveVariantPrices($product, $variant, $data);
            }
        }

        if ($request->has('new_variants')) {
            foreach ($request->input('new_variants') as $data) {
                $name = trim($data['name'] ?? '');
                if (! $name) continue;

                $variant = $product->variants()->create([
                    'name'       => $name,
                    'origin'     => ($data['origin'] ?? '') ?: null,
                    'grade'      => ($data['grade'] ?? '') ?: null,
                    'size_label' => ($data['size_label'] ?? '') ?: null,
                    'sort_order' => (int) ($data['sort_order'] ?? 0),
                    'is_active'  => ! empty($data['is_active']),
                ]);
                $this->saveVariantPrices($product, $variant, $data);
            }
        }
    }

    private function saveVariantPrices(Product $product, ProductVariant $variant, array $data): void
    {
        foreach (['retail', 'wholesale'] as $sellType) {
            $key = $sellType . '_prices';
            if (! empty($data[$key]) && is_array($data[$key])) {
                foreach ($data[$key] as $priceId => $priceData) {
                    $price = $variant->prices()->where('sell_type', $sellType)->find((int) $priceId);
                    if (! $price) continue;
                    if (! empty($priceData['_delete'])) { $price->delete(); continue; }

                    $finalPrice = (float) ($priceData['final_price'] ?? 0);
                    if ($finalPrice <= 0) continue;

                    $price->update([
                        'label'         => ($priceData['label'] ?? '') ?: $price->label,
                        'final_price'   => $finalPrice,
                        'auto_price'    => $finalPrice,
                        'min_order_qty' => (isset($priceData['min_order_qty']) && $priceData['min_order_qty'] !== '') ? (int) $priceData['min_order_qty'] : null,
                        'is_active'     => ! empty($priceData['is_active']),
                    ]);
                }
            }

            $newKey = 'new_' . $key;
            if (! empty($data[$newKey]) && is_array($data[$newKey])) {
                foreach ($data[$newKey] as $priceData) {
                    $label      = trim($priceData['label'] ?? '');
                    $gram       = (int) ($priceData['quantity_gram'] ?? 0);
                    $finalPrice = (float) ($priceData['final_price'] ?? 0);
                    if (! $label || $gram <= 0 || $finalPrice <= 0) continue;

                    ProductPrice::create([
                        'product_id'         => $product->id,
                        'product_variant_id' => $variant->id,
                        'sell_type'          => $sellType,
                        'label'              => $label,
                        'quantity_gram'      => $gram,
                        'auto_price'         => $finalPrice,
                        'final_price'        => $finalPrice,
                        'is_manual_override' => false,
                        'min_order_qty'      => (isset($priceData['min_order_qty']) && $priceData['min_order_qty'] !== '') ? (int) $priceData['min_order_qty'] : null,
                        'is_active'          => ! empty($priceData['is_active']),
                    ]);
                }
            }
        }
    }
}
