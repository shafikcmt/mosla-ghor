<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * WooCommerce-style variant persistence shared by the admin + vendor product
 * controllers. Handles create/update/delete, optional image upload, the
 * optional retail/sale/stock/sku fields, and single-default enforcement.
 *
 * Only `name` is required per row; everything else is optional and nullable.
 * Nothing here touches retail packs or the wholesale (enquiry-only) flow.
 */
trait ManagesProductVariants
{
    protected function saveVariants(Request $request, Product $product): void
    {
        // Radio value identifying the chosen default row: "existing:<id>" | "new:<index>".
        $defaultKey        = (string) $request->input('default_variant', '');
        $resolvedDefaultId = null;

        // ── Existing variants: update or delete ──────────────────────────────
        if ($request->has('variants')) {
            foreach ((array) $request->input('variants') as $variantId => $data) {
                $variant = $product->variants()->find((int) $variantId);
                if (! $variant) {
                    continue;
                }

                if (! empty($data['_delete'])) {
                    $this->deleteLocalVariantFile($variant->image);
                    $variant->delete();
                    continue;
                }

                $name = trim($data['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                $fields = $this->variantScalarData($data, $name, (int) ($variant->sort_order ?? 0));

                $file = $request->file("variants.{$variantId}.image_file");
                if ($file) {
                    $this->deleteLocalVariantFile($variant->image);
                    $fields['image'] = 'storage/' . $file->store('products/variants', 'public');
                } elseif (! empty($data['remove_image'])) {
                    $this->deleteLocalVariantFile($variant->image);
                    $fields['image'] = null;
                }

                $variant->update($fields);

                if ($defaultKey === 'existing:' . $variant->id && $variant->is_active) {
                    $resolvedDefaultId = $variant->id;
                }
            }
        }

        // ── New variants: create ─────────────────────────────────────────────
        if ($request->has('new_variants')) {
            $order = (int) $product->variants()->max('sort_order');
            foreach ((array) $request->input('new_variants') as $index => $data) {
                $name = trim($data['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                $fields = $this->variantScalarData($data, $name, ++$order);

                $file = $request->file("new_variants.{$index}.image_file");
                if ($file) {
                    $fields['image'] = 'storage/' . $file->store('products/variants', 'public');
                }

                $variant = $product->variants()->create($fields);

                if ($defaultKey === 'new:' . $index && $variant->is_active) {
                    $resolvedDefaultId = $variant->id;
                }
            }
        }

        $this->syncDefaultVariant($product, $resolvedDefaultId);
    }

    /** Map a submitted row to the variant's scalar columns (all optional but name). */
    private function variantScalarData(array $data, string $name, int $sortOrder): array
    {
        $num = fn(string $k) => (isset($data[$k]) && $data[$k] !== '' && is_numeric($data[$k])) ? (float) $data[$k] : null;
        $int = fn(string $k) => (isset($data[$k]) && $data[$k] !== '' && is_numeric($data[$k])) ? (int) $data[$k] : null;

        return [
            'name'         => $name,
            'sku'          => trim($data['sku'] ?? '') ?: null,
            'retail_price' => $num('retail_price'),
            'sale_price'   => $num('sale_price'),
            'stock'        => $int('stock'),
            'sort_order'   => $sortOrder,
            'is_active'    => ! empty($data['is_active']),
        ];
    }

    /**
     * Guarantee exactly one default among ACTIVE variants. Falls back to the
     * first active variant when the admin picked none (or picked an inactive one).
     */
    private function syncDefaultVariant(Product $product, ?int $resolvedDefaultId): void
    {
        $active = $product->variants()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

        // Clear every flag first so no stale/duplicate defaults survive.
        $product->variants()->update(['is_default' => false]);

        if ($active->isEmpty()) {
            return;
        }

        $defaultId = ($resolvedDefaultId && $active->contains('id', $resolvedDefaultId))
            ? $resolvedDefaultId
            : $active->first()->id;

        $product->variants()->whereKey($defaultId)->update(['is_default' => true]);
    }

    /** Delete a previously uploaded variant image (only local storage/ paths). */
    private function deleteLocalVariantFile(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http') || ! str_starts_with($path, 'storage/')) {
            return;
        }
        Storage::disk('public')->delete(preg_replace('#^storage/#', '', $path));
    }
}
