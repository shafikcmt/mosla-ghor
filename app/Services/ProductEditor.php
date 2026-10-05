<?php

namespace App\Services;

use App\Http\Controllers\Concerns\ManagesProductVariants;
use App\Models\Product;
use App\Models\PriceSetting;
use App\Models\Tag;
use App\Support\ProductMedia;
use App\Support\TempUpload;
use App\Support\UploadErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Shared persistence for the existing admin and vendor product workflows. */
class ProductEditor
{
    use ManagesProductVariants;

    public function save(Request $request, ?Product $product = null, array $trusted = [], bool $admin = false): Product
    {
        $data = $this->validate($request, $product, $admin);
        $media = new ProductMedia();
        try {
            return DB::transaction(function () use ($request, $product, $data, $trusted, $admin, $media) {
                $product = $product ? Product::lockForUpdate()->findOrFail($product->id) : new Product();
                $wasRetail = (bool) $product->show_in_retail;
                $oldPrice = $product->retail_price_1kg;
                $fields = Arr::only($data, [
                    'name_bn', 'name_en', 'category_id', 'short_description', 'description', 'stock',
                    'sku', 'category', 'brand', 'unit', 'purchase_price', 'selling_price', 'low_stock_threshold',
                    'is_active', 'show_in_retail', 'show_in_wholesale',
                    'meta_title', 'meta_description', 'meta_keywords',
                    'main_image_alt', 'og_image_alt',
                ]);
                // Wholesale cover inputs only arrive while the পাইকারি section is enabled.
                if (array_key_exists('wholesale_main_image_alt', $data)) {
                    $fields['wholesale_main_image_alt'] = $data['wholesale_main_image_alt'];
                }
                // Canonical/robots can deindex or redirect ranking — admin only.
                if ($admin) {
                    $fields += Arr::only($data, ['canonical_url', 'meta_robots']);
                }
                $retail = (bool) $data['show_in_retail'];
                $wholesale = (bool) $data['show_in_wholesale'];
                $fields['is_wholesale'] = $wholesale && ! $retail;
                if (array_key_exists('low_stock_threshold', $fields)) {
                    $fields['low_stock_threshold'] ??= 0;
                }
                $fields['slug'] = $data['slug'] ?: ($product->slug ?: $this->newSlug($data['name_en'] ?? $data['name_bn']));
                if ($retail) {
                    $fields['retail_price_1kg'] = $data['retail_price_1kg'];
                } elseif (! $product->exists) {
                    // Existing schema requires this legacy column, but no retail rows are generated.
                    $fields['retail_price_1kg'] = 0;
                }
                if ($wholesale) {
                    $fields += Arr::only($data, ['wholesale_enquiry_enabled', 'min_order_quantity', 'min_order_unit', 'delivery_time', 'payment_terms', 'wholesale_price_1kg']);
                    if (array_key_exists('unit_conversions', $data)) {
                        $fields['unit_conversions'] = $this->cleanConversions($data['unit_conversions']) ?: null;
                    }
                    if (array_key_exists('min_order_unit', $fields)) { $fields['min_order_unit'] ??= 'kg'; }
                }
                if ($admin && $product->vendor_id && isset($data['approval_status'])) {
                    $fields['approval_status'] = $data['approval_status'];
                }
                if (array_key_exists('video_url', $data)) {
                    $fields['video_url'] = $data['video_url'];
                }
                // Images uploaded one-per-request by the editor arrive as tokens (see TempUpload);
                // a direct file upload is the no-JavaScript fallback.
                $uid = (int) $request->user()?->id;
                $adopt = function (string $key, string $kind, string $folder) use ($data, $uid, $media): ?string {
                    $token = $data[$key] ?? null;
                    if (! $token) {
                        return null;
                    }
                    $tmp = TempUpload::resolve($token, $uid, $kind);
                    if (! $tmp) {
                        throw ValidationException::withMessages([$key => 'ছবির আপলোডের মেয়াদ শেষ বা অবৈধ — ছবিটি আবার বেছে নিন।']);
                    }
                    return $media->adopt($tmp, $folder, $key);
                };
                // Empty/missing external URL never clears an existing uploaded image.
                if ($adopted = $adopt('main_image_token', 'main', 'products/images')) {
                    $fields['main_image'] = $adopted;
                    $media->retire($product->main_image);
                } elseif ($request->hasFile('main_image_file')) {
                    $fields['main_image'] = $media->store($request->file('main_image_file'), 'products/images', 'main_image_file');
                    $media->retire($product->main_image);
                } elseif ($request->boolean('remove_main_image')) {
                    $fields['main_image'] = null;
                    $media->retire($product->main_image);
                } elseif (! empty($data['main_image'])) {
                    $fields['main_image'] = $data['main_image'];
                    if ($fields['main_image'] !== $product->main_image) {
                        $media->retire($product->main_image);
                    }
                }
                // Separate পাইকারি cover photo (optional; falls back to the main image).
                if ($adopted = $adopt('wholesale_main_image_token', 'wholesale_main', 'products/images')) {
                    $fields['wholesale_main_image'] = $adopted;
                    $media->retire($product->wholesale_main_image);
                } elseif ($request->hasFile('wholesale_main_image_file')) {
                    $fields['wholesale_main_image'] = $media->store($request->file('wholesale_main_image_file'), 'products/images', 'wholesale_main_image_file');
                    $media->retire($product->wholesale_main_image);
                } elseif ($request->boolean('remove_wholesale_main_image')) {
                    $fields['wholesale_main_image'] = null;
                    $media->retire($product->wholesale_main_image);
                }
                if ($adopted = $adopt('og_image_token', 'og', 'products/images')) {
                    $fields['og_image'] = $adopted;
                    $media->retire($product->og_image);
                } elseif ($request->hasFile('og_image_file')) {
                    $fields['og_image'] = $media->store($request->file('og_image_file'), 'products/images', 'og_image_file');
                    $media->retire($product->og_image);
                } elseif ($request->boolean('remove_og_image')) {
                    $fields['og_image'] = null;
                    $media->retire($product->og_image);
                }
                $gallery = $product->gallery_images ?? [];
                $remove = $data['remove_gallery'] ?? [];
                $tokens = array_map(fn ($path) => hash('sha256', $path), $gallery);
                if (array_diff($remove, $tokens)) {
                    throw ValidationException::withMessages(['remove_gallery' => 'গ্যালারির ছবি বদলেছে বা এই পণ্যের নয়। পেজ রিফ্রেশ করুন।']);
                }
                foreach ($gallery as $i => $path) {
                    if (in_array(hash('sha256', $path), $remove, true)) {
                        $media->retire($path);
                        unset($gallery[$i]);
                    }
                }
                foreach ($request->file('gallery_images', []) as $file) {
                    $gallery[] = $media->store($file, 'products/images', 'gallery_images');
                }
                $galleryTokens = array_values(array_filter((array) ($data['gallery_tokens'] ?? [])));
                foreach ($galleryTokens as $i => $token) {
                    $tmp = TempUpload::resolve($token, $uid, 'gallery');
                    if (! $tmp) {
                        throw ValidationException::withMessages(["gallery_tokens.$i" => 'গ্যালারির ছবির আপলোডের মেয়াদ শেষ বা অবৈধ — ছবিটি আবার বেছে নিন।']);
                    }
                    $gallery[] = $media->adopt($tmp, 'products/images', "gallery_tokens.$i");
                }
                if ($remove || $request->hasFile('gallery_images') || $galleryTokens) {
                    $fields['gallery_images'] = array_values($gallery);
                }
                // Gallery alt text, keyed by sha256 of the image path; only for images still in the gallery.
                if (array_key_exists('gallery_alts', $data) || isset($fields['gallery_images'])) {
                    $alts = array_merge((array) ($product->gallery_alts ?? []), array_map(
                        fn ($v) => is_string($v) ? trim($v) : '', (array) ($data['gallery_alts'] ?? [])
                    ));
                    $current = array_map(fn ($p) => hash('sha256', $p), $fields['gallery_images'] ?? ($product->gallery_images ?? []));
                    $alts = array_filter(array_intersect_key($alts, array_flip($current)), fn ($v) => $v !== '');
                    $fields['gallery_alts'] = $alts ?: null;
                }
                if ($request->hasFile('video_file')) {
                    $fields['video_path'] = $media->store($request->file('video_file'), 'products/videos', 'video_file');
                    $media->retire($product->video_path);
                } elseif ($request->boolean('remove_video')) {
                    $fields['video_path'] = null;
                    $media->retire($product->video_path);
                }
                $new = ! $product->exists;
                $product->fill($fields + $trusted)->save();
                if ($product->shouldResyncPrices($new, $wasRetail, $oldPrice)) {
                    $product->syncPrices();
                }
                if ($retail) {
                    foreach ($data['prices'] ?? [] as $id => $row) {
                        $price = $product->prices()->whereNull('product_variant_id')->where('sell_type', 'retail')->find($id);
                        if (! $price) {
                            throw ValidationException::withMessages(['prices' => 'প্যাকটি এই পণ্যের নয়।']);
                        }
                        $manual = ! empty($row['is_manual_override']);
                        $price->update([
                            'manual_price' => $row['manual_price'] ?? null,
                            'is_manual_override' => $manual,
                            'is_active' => (bool) ($row['is_active'] ?? false),
                            'final_price' => $manual ? $row['manual_price'] :
                                ($price->is_manual_override ? PriceSetting::current()->roundPrice((float) $price->auto_price) : $price->final_price),
                        ]);
                    }
                }
                $this->saveVariants($request, $product, $data, $media);
                if (array_key_exists('tags', $data)) {
                    $ids = [];
                    foreach ($data['tags'] ?? [] as $name) {
                        $name = Tag::cleanName($name);
                        $ids[] = Tag::firstOrCreate(['normalized_key' => Tag::keyFor($name)], ['name' => $name])->id;
                    }
                    $product->tags()->sync(array_unique($ids));
                }
                DB::afterCommit(function () use ($media) {
                    try { $media->committed(); } catch (\Throwable $e) { report($e); }
                });
                return $product;
            });
        } catch (\Throwable $e) {
            $media->rollback();
            throw $e;
        }
    }

    private function validate(Request $request, ?Product $product, bool $admin): array
    {
        $input = $request->all();
        foreach (['show_in_retail', 'show_in_wholesale', 'is_active'] as $flag) {
            $input[$flag] ??= $product?->{$flag} ?? ($flag !== 'show_in_wholesale');
        }
        $input['slug'] ??= $product?->slug;
        if (isset($input['tags']) && is_string($input['tags'])) {
            $input['tags'] = array_values(array_filter(array_map('trim', preg_split('/[,\r\n]+/u', $input['tags'])), fn ($s) => $s !== ''));
        }
        $retail = filter_var($input['show_in_retail'], FILTER_VALIDATE_BOOLEAN);
        $wholesale = filter_var($input['show_in_wholesale'], FILTER_VALIDATE_BOOLEAN);
        // Uploads that failed at the PHP level (too big, partial, temp dir) get one clear Bangla
        // message each and are removed from the input so they are never reported as "not an image".
        $uploadErrors = UploadErrors::collect($request);
        foreach (array_keys($uploadErrors) as $failedKey) {
            Arr::forget($input, $failedKey);
        }
        // 'bail': one message per image field.
        $image = ['bail', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:10240'];
        $rules = [
            'name_bn' => 'required|string|max:255', 'name_en' => 'nullable|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', 'regex:~^[\pL\pN_-]+$~u', Rule::unique('products')->ignore($product?->id)],
            'category_id' => 'nullable|integer|exists:categories,id',
            'short_description' => 'nullable|string|max:500', 'description' => 'nullable|string|max:50000',
            'stock' => 'required|integer|min:0|max:2147483647',
            'show_in_retail' => 'required|boolean', 'show_in_wholesale' => 'required|boolean', 'is_active' => 'required|boolean',
            'retail_price_1kg' => $retail ? 'required|numeric|min:0.01|max:99999999.99' : 'exclude',
            'main_image' => ['nullable', 'string', 'max:255', 'url:http,https'],
            'main_image_file' => $image, 'remove_main_image' => 'sometimes|boolean',
            'wholesale_main_image_file' => $image, 'remove_wholesale_main_image' => 'sometimes|boolean',
            'wholesale_main_image_alt' => ['nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
            'gallery_images' => 'sometimes|array|max:20', 'gallery_images.*' => array_merge(['required'], array_diff($image, ['nullable'])),
            'remove_gallery' => 'sometimes|array|max:100', 'remove_gallery.*' => 'string|size:64',
            'video_url' => 'nullable|url:http,https|max:255',
            'video_file' => 'nullable|file|mimes:mp4,webm,mov|extensions:mp4,webm,mov|max:51200', 'remove_video' => 'sometimes|boolean',
            'tags' => 'sometimes|nullable|array|max:20',
            'tags.*' => ['required', 'string', 'max:80', 'regex:/^[\pL\pM\pN\s&._-]+$/u'],
            'sku' => 'nullable|string|max:100', 'category' => 'nullable|string|max:100', 'brand' => 'nullable|string|max:100',
            'unit' => ['nullable', Rule::in(Product::UNITS)],
            'purchase_price' => 'nullable|numeric|min:0|max:9999999999.99', 'selling_price' => 'nullable|numeric|min:0|max:9999999999.99',
            'low_stock_threshold' => 'nullable|numeric|min:0|max:999999999.999',
            'wholesale_enquiry_enabled' => $wholesale ? 'sometimes|boolean' : 'exclude',
            'min_order_quantity' => $wholesale ? 'nullable|numeric|min:0|max:99999999.99' : 'exclude',
            'min_order_unit' => $wholesale ? ['nullable', Rule::in(array_unique([...Product::UNITS, 'piece']))] : 'exclude',
            'delivery_time' => $wholesale ? 'nullable|string|max:255' : 'exclude',
            'payment_terms' => $wholesale ? 'nullable|string|max:255' : 'exclude',
            'wholesale_price_1kg' => $wholesale ? 'nullable|numeric|min:0|max:99999999.99' : 'exclude',
            'unit_conversions' => $wholesale ? 'sometimes|nullable|array|max:10' : 'exclude',
            'unit_conversions.*' => $wholesale ? 'array' : 'exclude',
            'unit_conversions.*.unit' => $wholesale ? ['nullable', Rule::in(array_keys(Product::UNIT_LABELS))] : 'exclude',
            'unit_conversions.*.qty' => $wholesale ? 'nullable|numeric|gt:0|max:99999999' : 'exclude',
            'unit_conversions.*.base' => $wholesale ? ['nullable', Rule::in(array_keys(Product::UNIT_LABELS))] : 'exclude',
            'prices' => $retail ? 'sometimes|array|max:100' : 'exclude',
            'approval_status' => $admin && $product?->vendor_id ? 'sometimes|in:pending,approved,rejected' : 'exclude',
            'default_variant' => ['nullable', 'regex:/^(existing|new):[0-9]+$/'],
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:170',
            'meta_keywords' => 'nullable|string|max:255',
            'og_image_file' => $image,
            'remove_og_image' => 'sometimes|boolean',
            'canonical_url' => $admin ? 'nullable|url:http,https|max:255' : 'exclude',
            'meta_robots' => $admin ? ['nullable', Rule::in(Product::META_ROBOTS)] : 'exclude',
            // Image alt text (SEO / accessibility); empty = product-name fallback.
            'main_image_alt' => ['nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
            'og_image_alt' => ['nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
            'gallery_alts' => 'sometimes|nullable|array|max:100',
            'gallery_alts.*' => ['nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
            // One-image-per-request uploads (see TempUpload).
            'main_image_token' => 'nullable|string|max:2000',
            'wholesale_main_image_token' => 'nullable|string|max:2000',
            'og_image_token' => 'nullable|string|max:2000',
            'gallery_tokens' => 'sometimes|nullable|array|max:20',
            'gallery_tokens.*' => 'nullable|string|max:2000',
        ];
        $messages = [
            'meta_title.max' => 'Meta Title সর্বোচ্চ ৭০ অক্ষর হতে পারবে।',
            'meta_title.string' => 'Meta Title সঠিক লেখা নয়।',
            'meta_description.max' => 'Meta Description সর্বোচ্চ ১৭০ অক্ষর হতে পারবে।',
            'meta_description.string' => 'Meta Description সঠিক লেখা নয়।',
            'meta_keywords.max' => 'Meta Keywords সর্বোচ্চ ২৫৫ অক্ষর হতে পারবে।',
            'meta_keywords.string' => 'Meta Keywords সঠিক লেখা নয়।',
            'video_file.file' => 'ভিডিও একটি ফাইল হতে হবে।',
            'video_file.mimes' => 'ভিডিও শুধু MP4, WebM বা MOV হতে পারবে।',
            'video_file.extensions' => 'ভিডিও শুধু MP4, WebM বা MOV হতে পারবে।',
            'video_file.max' => 'ভিডিও সর্বোচ্চ ৫০ MB হতে পারবে।',
            'canonical_url.url' => 'Canonical URL একটি সঠিক http/https লিংক হতে হবে।',
            'canonical_url.max' => 'Canonical URL সর্বোচ্চ ২৫৫ অক্ষর হতে পারবে।',
            'meta_robots.in' => 'Robots-এর একটি সঠিক অপশন বেছে নিন।',
            'wholesale_price_1kg.numeric' => 'পাইকারি দাম একটি সংখ্যা হতে হবে।',
            'wholesale_price_1kg.min' => 'পাইকারি দাম ০ বা তার বেশি হতে হবে।',
            'wholesale_price_1kg.max' => 'পাইকারি দাম অনেক বেশি।',
            'unit_conversions.*.qty.numeric' => 'ইউনিট কনভার্শনের পরিমাণ একটি সংখ্যা হতে হবে।',
            'unit_conversions.*.qty.gt' => 'ইউনিট কনভার্শনের পরিমাণ ০-এর বেশি হতে হবে।',
            'unit_conversions.*.unit.in' => 'ইউনিট কনভার্শনের একক সঠিক নয়।',
            'unit_conversions.*.base.in' => 'ইউনিট কনভার্শনের একক সঠিক নয়।',
        ];
        if ($retail) {
            $rules += ['prices.*' => 'array', 'prices.*.manual_price' => 'nullable|numeric|min:0.01|max:99999999.99',
                'prices.*.is_manual_override' => 'sometimes|boolean', 'prices.*.is_active' => 'sometimes|boolean'];
        }
        foreach (['variants', 'new_variants'] as $group) {
            $rules[$group] = 'sometimes|array|max:50';
            $rules[$group.'.*'] = 'array';
            foreach (['name' => 'nullable|string|max:255', 'sku' => 'nullable|string|max:100',
                'retail_price' => 'nullable|numeric|min:0.01|max:99999999.99', 'sale_price' => 'nullable|numeric|min:0.01|max:99999999.99',
                'stock' => 'nullable|integer|min:0|max:2147483647', 'is_active' => 'sometimes|boolean',
                '_delete' => 'sometimes|boolean', 'remove_image' => 'sometimes|boolean',
                'attributes' => 'sometimes|array|max:10', 'attributes.*' => 'array',
                'attributes.*.name' => 'nullable|string|max:40', 'attributes.*.value' => 'nullable|string|max:80',
            ] as $key => $rule) {
                $rules[$group.'.*.'.$key] = $rule;
            }
            $rules[$group.'.*.image_file'] = $image;
            $rules[$group.'.*.image_token'] = 'nullable|string|max:2000';
            $rules[$group.'.*.image_alt'] = ['nullable', 'string', 'max:255', 'not_regex:/[<>]/'];
        }
        $messages += UploadErrors::imageMessages(['main_image_file', 'wholesale_main_image_file', 'og_image_file', 'gallery_images.*', 'variants.*.image_file', 'new_variants.*.image_file']);
        foreach (['main_image_alt' => 'মূল ছবির বিবরণ (alt)', 'wholesale_main_image_alt' => 'পাইকারি কভার ছবির বিবরণ (alt)', 'og_image_alt' => 'শেয়ার ছবির বিবরণ (alt)', 'gallery_alts.*' => 'গ্যালারির ছবির বিবরণ (alt)',
            'variants.*.image_alt' => 'ভ্যারিয়েন্ট ছবির বিবরণ (alt)', 'new_variants.*.image_alt' => 'ভ্যারিয়েন্ট ছবির বিবরণ (alt)'] as $key => $label) {
            $messages["$key.max"] = "{$label} সর্বোচ্চ ২৫৫ অক্ষর হতে পারবে।";
            $messages["$key.not_regex"] = "{$label}-এ < বা > চিহ্ন দেওয়া যাবে না।";
        }
        // Human Bangla field names (gallery/variant keys by their actual index).
        $attributes = ['main_image_file' => 'মূল ছবি', 'wholesale_main_image_file' => 'পাইকারি কভার ছবি', 'og_image_file' => 'শেয়ার ছবি', 'video_file' => 'ভিডিও'];
        foreach (array_keys((array) ($input['gallery_images'] ?? [])) as $i) {
            $attributes["gallery_images.$i"] = UploadErrors::label("gallery_images.$i");
        }
        foreach (['variants', 'new_variants'] as $group) {
            foreach (array_keys((array) ($input[$group] ?? [])) as $id) {
                $attributes["$group.$id.image_file"] = 'ভ্যারিয়েন্টের ছবি';
            }
        }
        $validator = Validator::make($input, $rules, $messages, $attributes);
        $validator->after(function ($v) use ($uploadErrors) {
            foreach ($uploadErrors as $key => $message) {
                $v->errors()->add($key, $message);
            }
        });
        // Tokens must belong to this user, be unexpired, match their kind and still exist.
        $uid = (int) $request->user()?->id;
        $validator->after(function ($v) use ($input, $uid) {
            $check = function (string $key, $token, string $kind, string $label) use ($v, $uid) {
                if (is_string($token) && $token !== '' && ! TempUpload::resolve($token, $uid, $kind)) {
                    $v->errors()->add($key, "{$label}-এর আপলোডের মেয়াদ শেষ বা অবৈধ — ছবিটি আবার বেছে নিন।");
                }
            };
            $check('main_image_token', $input['main_image_token'] ?? null, 'main', 'মূল ছবি');
            $check('wholesale_main_image_token', $input['wholesale_main_image_token'] ?? null, 'wholesale_main', 'পাইকারি কভার ছবি');
            $check('og_image_token', $input['og_image_token'] ?? null, 'og', 'শেয়ার ছবি');
            foreach ((array) ($input['gallery_tokens'] ?? []) as $i => $token) {
                $check("gallery_tokens.$i", $token, 'gallery', 'গ্যালারির ছবি');
            }
            foreach (['variants', 'new_variants'] as $group) {
                foreach ((array) ($input[$group] ?? []) as $id => $row) {
                    $check("$group.$id.image_token", is_array($row) ? ($row['image_token'] ?? null) : null, 'variant', 'ভ্যারিয়েন্টের ছবি');
                }
            }
        });
        $validator->after(function ($v) use ($input, $product, $retail, $wholesale) {
            if ($v->errors()->isNotEmpty()) { return; }
            if (! $retail && ! $wholesale) {
                $v->errors()->add('show_in_retail', 'অন্তত একটি বিক্রয় মাধ্যম বেছে নিন।');
            }
            if ($wholesale) {
                $seen = [];
                foreach ((array) ($input['unit_conversions'] ?? []) as $i => $row) {
                    $unit = $row['unit'] ?? null; $qty = $row['qty'] ?? null; $base = $row['base'] ?? null;
                    if (blank($unit) && blank($qty)) { continue; }
                    if (blank($unit) || blank($qty) || blank($base)) {
                        $v->errors()->add("unit_conversions.$i", 'ইউনিট কনভার্শনের একক, পরিমাণ ও মূল একক তিনটিই দিন।');
                    } elseif ($unit === $base) {
                        $v->errors()->add("unit_conversions.$i", 'একই এককে কনভার্শন করা যাবে না (যেমন: ১ কার্টন = ২০ কেজি)।');
                    } elseif (in_array($unit, $seen, true)) {
                        $v->errors()->add("unit_conversions.$i", Product::unitLabel($unit).'-এর কনভার্শন একবারই দিন।');
                    }
                    if (! blank($unit)) { $seen[] = $unit; }
                }
            }
            foreach ($retail ? ($input['prices'] ?? []) : [] as $id => $row) {
                if (! $product || ! $product->prices()->whereNull('product_variant_id')->where('sell_type', 'retail')->whereKey($id)->exists()) {
                    $v->errors()->add('prices', 'প্যাকটি এই পণ্যের নয়।');
                }
                if (! empty($row['is_manual_override']) && empty($row['manual_price'])) {
                    $v->errors()->add("prices.$id.manual_price", 'ম্যানুয়াল দাম দিন।');
                }
            }
            foreach (['variants', 'new_variants'] as $group) {
                foreach ($input[$group] ?? [] as $id => $row) {
                    if (! ctype_digit((string) $id) || ($group === 'variants' && (! $product || ! $product->variants()->whereKey($id)->exists()))) {
                        $v->errors()->add($group, 'ভ্যারিয়েন্টটি এই পণ্যের নয়।');
                        continue;
                    }
                    if (! empty($row['_delete'])) { continue; }
                    if (empty(trim($row['name'] ?? '')) && empty(array_filter($row['attributes'] ?? [], fn ($a) => ! empty($a['value'])))) {
                        $v->errors()->add("$group.$id.name", 'নাম বা অন্তত একটি বৈশিষ্ট্য দিন।');
                    }
                    $names = [];
                    if (trim($row['name'] ?? '') === '' && mb_strlen(implode(' / ', array_column($row['attributes'] ?? [], 'value'))) > 255) {
                        $v->errors()->add("$group.$id.name", 'সংক্ষিপ্ত ভ্যারিয়েন্ট নাম দিন (সর্বোচ্চ ২৫৫ অক্ষর)।');
                    }
                    foreach ($row['attributes'] ?? [] as $a) {
                        $name = mb_strtolower(trim($a['name'] ?? ''));
                        $value = trim($a['value'] ?? '');
                        if (($name === '') !== ($value === '') || ($name !== '' && in_array($name, $names, true))) {
                            $v->errors()->add("$group.$id.attributes", 'প্রতিটি বৈশিষ্ট্যের আলাদা নাম ও মান দিন।');
                        }
                        if ($name !== '') { $names[] = $name; }
                    }
                    $current = $group === 'variants' ? $product?->variants()->find($id) : null;
                    $base = array_key_exists('retail_price', $row) ? $row['retail_price'] : $current?->retail_price;
                    $sale = array_key_exists('sale_price', $row) ? $row['sale_price'] : $current?->sale_price;
                    if ($sale !== null && $sale !== '' && (! $base || (float) $sale > (float) $base)) {
                        $v->errors()->add("$group.$id.sale_price", 'অফার দাম মূল দামের বেশি হতে পারবে না।');
                    }
                }
            }
        });
        return $validator->validate();
    }

    /** Drop blank conversion rows and normalise the rest to {unit, qty, base}. */
    private function cleanConversions(?array $rows): array
    {
        $out = [];
        foreach ((array) $rows as $row) {
            if (! is_array($row) || blank($row['unit'] ?? null) || blank($row['qty'] ?? null) || blank($row['base'] ?? null)) { continue; }
            $out[] = ['unit' => $row['unit'], 'qty' => (float) $row['qty'], 'base' => $row['base']];
        }
        return $out;
    }

    private function newSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product-'.Str::lower(Str::random(8));
        $slug = $base;
        for ($i = 2; Product::where('slug', $slug)->exists(); $i++) { $slug = $base.'-'.$i; }
        return $slug;
    }
}
