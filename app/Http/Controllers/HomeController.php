<?php

namespace App\Http\Controllers;

use App\Models\Combo;
use App\Models\DeliveryZone;
use App\Models\PaymentSetting;
use App\Models\PriceSetting;
use App\Models\Product;

class HomeController extends Controller
{
    public function __invoke()
    {
        $products = Product::active()
            ->with(['activePrices'])
            ->get();

        $priceSetting    = PriceSetting::current();
        $packagingCost   = $priceSetting->default_packaging_cost;
        $minOrderAmount  = $priceSetting->minimum_order_amount;
        $paymentSettings = PaymentSetting::current();

        $activeZones = DeliveryZone::where('is_active', true)
            ->with(['activeLocations'])
            ->orderBy('sort_order')
            ->orderBy('zone_name')
            ->get();

        $fixedCombos = Combo::active()
            ->with(['items.product'])
            ->orderBy('sort_order')
            ->get();

        $fixedCombosForJs = $fixedCombos->map(fn($c) => [
            'id'         => $c->id,
            'name'       => $c->name,
            'sell_price' => (float) $c->sell_price,
            'items'      => $c->items->map(fn($item) => [
                'product_name'  => $item->product?->name_bn ?? '',
                'quantity_gram' => $item->quantity_gram,
                'label'         => $item->quantity_gram >= 1000
                    ? ($item->quantity_gram / 1000) . ' কেজি'
                    : $item->quantity_gram . ' গ্রাম',
                'unit_price'    => (float) $item->unit_price,
            ])->values()->all(),
        ])->values();

        $zonesForJs = $activeZones->map(fn($z) => [
            'id'                           => $z->id,
            'zone_name'                    => $z->zone_name,
            'zone_type'                    => $z->zone_type,
            'delivery_charge'              => (float) $z->delivery_charge,
            'free_delivery_minimum_amount' => $z->free_delivery_minimum_amount !== null ? (float) $z->free_delivery_minimum_amount : null,
            'locations'                    => $z->activeLocations->map(fn($l) => [
                'id'             => $l->id,
                'location_name'  => $l->location_name,
                'keywords'       => $l->keywords,
                'delivery_charge' => $l->delivery_charge !== null ? (float) $l->delivery_charge : null,
            ])->values()->all(),
        ])->values();

        return view('home', compact('products', 'packagingCost', 'minOrderAmount', 'paymentSettings', 'activeZones', 'zonesForJs', 'fixedCombos', 'fixedCombosForJs'));
    }
}
