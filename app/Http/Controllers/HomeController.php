<?php

namespace App\Http\Controllers;

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

        return view('home', compact('products', 'packagingCost', 'minOrderAmount', 'paymentSettings'));
    }
}
