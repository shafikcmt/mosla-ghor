<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;
use App\Support\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ManagesProductVariants
{
    protected function saveVariants(Request $request, Product $product, array $data, ProductMedia $media): void
    {
        if (! array_key_exists('variants', $data) && ! array_key_exists('new_variants', $data) && empty($data['default_variant'])) {
            return;
        }
        $selected = $data['default_variant'] ?? null;
        $defaultId = null;
        foreach (['variants', 'new_variants'] as $group) {
            foreach ($data[$group] ?? [] as $id => $row) {
                $existing = $group === 'variants';
                $variant = $existing ? $product->variants()->findOrFail($id) : $product->variants()->make();
                if (! empty($row['_delete'])) {
                    // Preserve IDs, files, prices and references in orders, enquiries and stock ledgers.
                    if ($existing) { $variant->update(['is_active' => false, 'is_default' => false]); }
                    continue;
                }
                $fields = [];
                if (! $existing) { $fields['is_active'] = true; }
                foreach (['sku', 'retail_price', 'sale_price', 'stock', 'is_active'] as $key) {
                    if (array_key_exists($key, $row)) { $fields[$key] = $row[$key]; }
                }
                if (array_key_exists('attributes', $row)) {
                    $fields['attributes'] = array_values(array_map(
                        fn ($a) => ['name' => trim($a['name']), 'value' => trim($a['value'])],
                        array_filter($row['attributes'], fn ($a) => ! empty(trim($a['name'] ?? '')))
                    ));
                }
                $fields['name'] = trim($row['name'] ?? '') ?: implode(' / ', array_column($fields['attributes'] ?? [], 'value'));
                if (! $existing) { $fields['sort_order'] = (int) $product->variants()->max('sort_order') + 1; }
                $field = "$group.$id.image_file";
                if ($request->hasFile($field)) {
                    $fields['image'] = $media->store($request->file($field), 'products/variants', $field);
                    $media->retire($variant->image);
                } elseif (! empty($row['remove_image'])) {
                    $fields['image'] = null;
                    $media->retire($variant->image);
                }
                $variant->fill($fields)->save();
                if ($selected === ($existing ? 'existing:' : 'new:').$id) {
                    if (! $variant->is_active) {
                        throw ValidationException::withMessages(['default_variant' => 'ডিফল্ট ভ্যারিয়েন্ট সক্রিয় হতে হবে।']);
                    }
                    $defaultId = $variant->id;
                }
            }
        }
        $active = $product->variants()->where('is_active', true)->get();
        if ($selected && ! $defaultId) {
            $selectedId = str_starts_with($selected, 'existing:') ? substr($selected, 9) : null;
            $defaultId = $active->firstWhere('id', $selectedId)?->id;
            if (! $defaultId) {
                throw ValidationException::withMessages(['default_variant' => 'সক্রিয় ভ্যারিয়েন্ট নির্বাচন করুন।']);
            }
        }
        // Retain the previous default when no new choice is submitted.
        $defaultId ??= $active->firstWhere('is_default', true)?->id ?? $active->first()?->id;
        $product->variants()->where('is_default', true)->update(['is_default' => false]);
        if ($defaultId) { $product->variants()->whereKey($defaultId)->update(['is_default' => true]); }
    }
}
