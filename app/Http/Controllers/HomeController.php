<?php

namespace App\Http\Controllers;

use App\Models\PriceSetting;
use App\Models\Product;

class HomeController extends Controller
{
    public function __invoke()
    {
        $products = Product::active()
            ->with(['activePrices'])
            ->get();

        $packagingCost = PriceSetting::current()->default_packaging_cost;

        return view('home', compact('products', 'packagingCost'));
    }
}
