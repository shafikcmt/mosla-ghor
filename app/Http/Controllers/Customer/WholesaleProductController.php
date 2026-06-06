<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;

class WholesaleProductController extends Controller
{
    public function show(Product $product)
    {
        // Only active products are visible; vendor products must be approved.
        abort_unless(
            $product->is_active
                && (is_null($product->vendor_id) || $product->approval_status === 'approved'),
            404
        );

        $product->load(['vendor', 'activeWholesalePrices', 'variants.prices', 'category.parent']);

        $wholesalePrices = $product->activeWholesalePrices;

        $relatedProducts = collect();
        if ($product->category_id) {
            $relatedProducts = Product::active()
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->with('category')
                ->limit(4)
                ->get();
        }

        return view('customer.wholesale.product-detail', compact('product', 'wholesalePrices', 'relatedProducts'));
    }
}
