<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;

class WholesaleProductController extends Controller
{
    public function show(Product $product)
    {
        // The public, price-hidden product page is the single source of truth.
        // This legacy URL is kept (now public) and redirects there — no forced login.
        return redirect()->route('products.show', $product->slug);
    }
}
