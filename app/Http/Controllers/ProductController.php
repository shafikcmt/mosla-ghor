<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /** Public, SEO-friendly product detail page. */
    public function show(Product $product)
    {
        // Only active products are visible; vendor products must be approved.
        abort_unless(
            $product->is_active
                && (is_null($product->vendor_id) || $product->approval_status === 'approved'),
            404
        );

        $product->load([
            'vendor',
            'category.parent',
            'activeRetailPrices',
            'activeWholesalePrices',
            'activeVariants.activePrices',
        ]);

        $retailPrices    = $product->activeRetailPrices;
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

        $reviews     = $product->approvedReviews()->get();
        $avgRating   = $product->averageRating();
        $reviewCount = $reviews->count();

        return view('storefront.product-detail', compact(
            'product', 'retailPrices', 'wholesalePrices', 'relatedProducts',
            'reviews', 'avgRating', 'reviewCount'
        ));
    }

    /** Public review submission — guest or logged-in. Starts as pending. */
    public function storeReview(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $isCustomer = Auth::check() && Auth::user()->role === 'customer';

        $validated = $request->validate([
            'rating'           => ['required', 'integer', 'min:1', 'max:5'],
            'comment'          => ['required', 'string', 'max:2000'],
            'customer_name'    => [$isCustomer ? 'nullable' : 'required', 'string', 'max:100'],
            'customer_contact' => ['nullable', 'string', 'max:100'],
            'image'            => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $name = $isCustomer
            ? (Auth::user()->name ?: $request->input('customer_name'))
            : $request->input('customer_name');

        $imagePath = null;
        if ($request->hasFile('image')) {
            $path      = $request->file('image')->store('reviews', 'public');
            $imagePath = 'storage/' . $path;
        }

        ProductReview::create([
            'product_id'       => $product->id,
            'user_id'          => $isCustomer ? Auth::id() : null,
            'customer_name'    => $name,
            'customer_contact' => $request->input('customer_contact') ?: null,
            'rating'           => $validated['rating'],
            'comment'          => $validated['comment'],
            'image'            => $imagePath,
            'is_approved'      => false,
        ]);

        return redirect()
            ->to(route('products.show', $product->slug) . '#reviews')
            ->with('success', 'আপনার রিভিউ জমা হয়েছে। অনুমোদনের পর প্রকাশিত হবে। ধন্যবাদ!');
    }
}
