<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductReview::with(['product', 'user'])->latest();

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        // Filter by status
        if ($request->status === 'approved') {
            $query->where('is_approved', true);
        } elseif ($request->status === 'pending') {
            $query->where('is_approved', false);
        }

        $reviews  = $query->paginate(20)->withQueryString();
        $products = Product::orderBy('name_bn')->get(['id', 'name_bn', 'name_en']);

        return view('admin.product-reviews.index', compact('reviews', 'products'));
    }

    public function approve(ProductReview $productReview)
    {
        $productReview->update(['is_approved' => true]);

        return back()->with('success', 'রিভিউ অনুমোদিত হয়েছে।');
    }

    /** Set back to pending (unapprove). */
    public function pending(ProductReview $productReview)
    {
        $productReview->update(['is_approved' => false]);

        return back()->with('success', 'রিভিউ পেন্ডিং করা হয়েছে।');
    }

    public function destroy(ProductReview $productReview)
    {
        // Clean up an uploaded review image, if any (storage/... → public disk path).
        if ($productReview->image && str_starts_with($productReview->image, 'storage/')) {
            Storage::disk('public')->delete(substr($productReview->image, strlen('storage/')));
        }

        $productReview->delete();

        return back()->with('success', 'রিভিউ মুছে ফেলা হয়েছে।');
    }
}
