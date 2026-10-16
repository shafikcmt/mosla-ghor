<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;

class CustomerWishlistController extends CustomerBaseController
{
    public function index()
    {
        $items = Wishlist::where('user_id', Auth::id())
            ->with(['product' => fn($q) => $q->where('is_active', true)->with('activePrices')])
            ->latest()
            ->paginate(20);

        return view('customer.wishlist.index', compact('items'));
    }

    public function store(Product $product)
    {
        $wish = Wishlist::firstOrCreate([
            'user_id'    => Auth::id(),
            'product_id' => $product->id,
        ]);

        // Meta AddToWishlist only for a real new addition (not a repeat click, not a failed write).
        if ($wish->wasRecentlyCreated) {
            \App\Support\MetaPixel::addToWishlist($product);
        }

        return back()->with('success', 'উইশলিস্টে যোগ হয়েছে।');
    }

    public function destroy(Product $product)
    {
        Wishlist::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->delete();

        return back()->with('success', 'উইশলিস্ট থেকে সরানো হয়েছে।');
    }
}
