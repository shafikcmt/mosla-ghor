<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorProductApprovalController extends Controller
{
    private const STATUSES = ['pending', 'approved', 'rejected'];

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : null;

        $products = Product::whereNotNull('vendor_id')
            ->with('vendor:id,shop_name,owner_name')
            ->when($status === 'pending', fn ($q) => $q->where(fn ($w) => $w->where('approval_status', 'pending')->orWhereNull('approval_status')))
            ->when($status && $status !== 'pending', fn ($q) => $q->where('approval_status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Legacy vendor products may have a null approval_status — treat those as pending.
        $counts = Product::whereNotNull('vendor_id')
            ->selectRaw("COALESCE(approval_status, 'pending') as status, COUNT(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'pending'  => (int) ($counts['pending'] ?? 0),
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
            'total'    => (int) $counts->sum(),
        ];

        return view('admin.vendor-products.index', compact('products', 'stats', 'status'));
    }

    public function approve(Product $product): RedirectResponse
    {
        abort_if(is_null($product->vendor_id), 404);

        $product->update(['approval_status' => 'approved', 'rejection_reason' => null]);

        return back()->with('success', "\"{$product->name_bn}\" পণ্যটি অনুমোদিত হয়েছে।");
    }

    public function reject(Product $product, Request $request): RedirectResponse
    {
        abort_if(is_null($product->vendor_id), 404);

        $data = $request->validate([
            'reason' => 'nullable|string|max:200',
        ]);

        $product->update([
            'approval_status'  => 'rejected',
            'rejection_reason' => $data['reason'] ?? null,
        ]);

        return back()->with('success', "\"{$product->name_bn}\" পণ্যটি প্রত্যাখ্যাত হয়েছে।");
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_ids'   => 'required|array|min:1',
            'product_ids.*' => 'integer',
        ]);

        $count = Product::whereNotNull('vendor_id')
            ->whereIn('id', $data['product_ids'])
            ->update(['approval_status' => 'approved', 'rejection_reason' => null]);

        return back()->with('success', "{$count}টি পণ্য অনুমোদিত হয়েছে।");
    }
}
