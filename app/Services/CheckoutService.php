<?php

namespace App\Services;

use App\Models\BdDistrict;
use App\Models\BdDivision;
use App\Models\BdUnion;
use App\Models\BdUpazila;
use App\Models\Combo;
use App\Models\DeliveryLocation;
use App\Models\DeliveryZone;
use App\Models\PriceSetting;
use App\Models\Product;
use App\Models\ProductPrice;

/**
 * Single source of truth for retail-order pricing, stock checks, and delivery
 * charge. Shared by the checkout pages (CheckoutController) and the final
 * place-order step (OrderController) so the two can never diverge.
 *
 * Every failure throws CheckoutException (Bangla message + related field).
 */
class CheckoutService
{
    /**
     * Resolve line items + subtotal from a cart payload.
     *
     * @param  int|null  $comboId   fixed-combo id (overrides $priceIds)
     * @param  array     $priceIds  ProductPrice ids for a custom/single order
     * @return array{items: array, subtotal: float, order_type: string, combo_id: ?int}
     */
    public function resolveItems(?int $comboId, array $priceIds): array
    {
        if ($comboId) {
            return $this->resolveCombo($comboId);
        }

        if (empty($priceIds)) {
            throw new CheckoutException('কমপক্ষে একটি পণ্য যোগ করুন।');
        }
        if (count($priceIds) > 20) {
            throw new CheckoutException('সর্বোচ্চ ২০টি পণ্য যোগ করা যাবে।');
        }

        $subtotal = 0.0;
        $items    = [];

        foreach ($priceIds as $priceId) {
            $productPrice = ProductPrice::with(['product.vendor', 'variant'])
                ->where('id', (int) $priceId)
                ->where('is_active', true)
                ->first();

            if (! $productPrice || ! $productPrice->product?->is_active) {
                throw new CheckoutException('একটি পণ্যের তথ্য পাওয়া যায়নি। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।');
            }

            $lineTotal = (float) $productPrice->final_price;
            $subtotal += $lineTotal;
            $vendorId  = $productPrice->product->vendor_id;

            $items[] = [
                'sell_type'     => $productPrice->sell_type,
                'price_id'      => $productPrice->id,
                'product_id'    => $productPrice->product->id,
                'product_name'  => $productPrice->product->name_bn,
                'variant_name'  => $productPrice->variant?->name,
                'quantity_gram' => (int) $productPrice->quantity_gram,
                'unit_price'    => $lineTotal,
                'line_total'    => $lineTotal,
                'vendor_id'     => $vendorId,
                'vendor_name'   => $vendorId ? ($productPrice->product->vendor?->shop_name ?? null) : null,
                // Display-only fields (not persisted to order_items)
                'label'         => $productPrice->label ?? null,
                'image'         => $productPrice->product->main_image ?? null,
            ];
        }

        return [
            'items'      => $items,
            'subtotal'   => $subtotal,
            'order_type' => count($items) === 1 ? 'single_product' : 'custom',
            'combo_id'   => null,
        ];
    }

    private function resolveCombo(int $comboId): array
    {
        $combo = Combo::with('items.product')
            ->where('id', $comboId)
            ->where('is_active', true)
            ->first();

        if (! $combo) {
            throw new CheckoutException('কম্বোটি পাওয়া যায়নি বা নিষ্ক্রিয়।');
        }

        $items = [];
        foreach ($combo->items as $item) {
            $items[] = [
                'sell_type'     => $item->sell_type ?? 'retail',
                'price_id'      => $item->product_price_id,
                'product_id'    => $item->product_id,
                'product_name'  => $item->product?->name_bn ?? '',
                'quantity_gram' => $item->quantity_gram,
                'unit_price'    => (float) $item->unit_price,
                'line_total'    => (float) $item->line_total,
                'label'         => null,
                'image'         => $item->product?->main_image ?? null,
            ];
        }

        return [
            'items'      => $items,
            'subtotal'   => (float) $combo->sell_price,
            'order_type' => 'fixed_combo',
            'combo_id'   => $combo->id,
        ];
    }

    /** Aggregate required grams per product id. */
    public function neededGramsByProduct(array $items): array
    {
        $needed = [];
        foreach ($items as $item) {
            $pid = $item['product_id'];
            $needed[$pid] = ($needed[$pid] ?? 0) + $item['quantity_gram'];
        }
        return $needed;
    }

    /** Fast-fail stock pre-check (no locks). Throws on shortfall. */
    public function assertStock(array $neededByProduct): void
    {
        foreach ($neededByProduct as $productId => $neededGram) {
            $product = Product::find($productId);
            if (! $product || $product->stock * 1000 < $neededGram) {
                $name = $product?->name_bn ?? 'পণ্যটি';
                throw new CheckoutException('দুঃখিত, ' . $name . '-এর পর্যাপ্ত স্টক নেই। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।');
            }
        }
    }

    /**
     * Resolve + validate a delivery zone/location and compute the charge.
     *
     * @return array{zone: DeliveryZone, location: DeliveryLocation, delivery_charge: float, packaging: float}
     */
    public function resolveCharge(int $zoneId, int $locationId, float $subtotal): array
    {
        $zone = DeliveryZone::where('id', $zoneId)->where('is_active', true)->first();
        if (! $zone) {
            throw new CheckoutException('ডেলিভারি জোন পাওয়া যায়নি বা নিষ্ক্রিয়।', 'delivery_zone_id');
        }

        $location = DeliveryLocation::where('id', $locationId)
            ->where('zone_id', $zone->id)
            ->where('is_active', true)
            ->first();
        if (! $location) {
            throw new CheckoutException('ডেলিভারি এলাকা পাওয়া যায়নি বা নিষ্ক্রিয়।', 'delivery_location_id');
        }

        return [
            'zone'            => $zone,
            'location'        => $location,
            'delivery_charge' => $zone->chargeFor($location, $subtotal),
            'packaging'       => (float) PriceSetting::current()->default_packaging_cost,
        ];
    }

    /**
     * Verify the BD address hierarchy (prevents tampered IDs).
     *
     * @return array{division: BdDivision, district: BdDistrict, upazila: BdUpazila, union: ?BdUnion}
     */
    public function verifyBdHierarchy(int $divisionId, int $districtId, int $upazilaId, ?int $unionId): array
    {
        $division = BdDivision::where('id', $divisionId)->where('is_active', true)->first();
        if (! $division) {
            throw new CheckoutException('বিভাগ পাওয়া যায়নি।', 'bd_division_id');
        }

        $district = BdDistrict::where('id', $districtId)->where('division_id', $division->id)
            ->where('is_active', true)->first();
        if (! $district) {
            throw new CheckoutException('জেলা পাওয়া যায়নি বা বিভাগের সাথে মিলছে না।', 'bd_district_id');
        }

        $upazila = BdUpazila::where('id', $upazilaId)->where('district_id', $district->id)
            ->where('is_active', true)->first();
        if (! $upazila) {
            throw new CheckoutException('উপজেলা পাওয়া যায়নি বা জেলার সাথে মিলছে না।', 'bd_upazila_id');
        }

        $union = null;
        if ($unionId) {
            $union = BdUnion::where('id', $unionId)->where('upazila_id', $upazila->id)
                ->where('is_active', true)->first();
            if (! $union) {
                throw new CheckoutException('ইউনিয়ন পাওয়া যায়নি বা উপজেলার সাথে মিলছে না।', 'bd_union_id');
            }
        }

        return ['division' => $division, 'district' => $district, 'upazila' => $upazila, 'union' => $union];
    }
}
