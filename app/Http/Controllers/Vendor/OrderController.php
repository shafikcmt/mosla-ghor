<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    private function vendor()
    {
        return Auth::user()->vendor;
    }

    public function index(Request $request)
    {
        $vendor = $this->vendor();

        $query = $vendor->vendorOrders()->with('order')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vendorOrders = $query->paginate(20);

        return view('vendor.orders.index', compact('vendor', 'vendorOrders'));
    }

    public function show(VendorOrder $vendorOrder)
    {
        if ($vendorOrder->vendor_id !== $this->vendor()?->id) {
            abort(403);
        }

        $vendorOrder->load(['order.items' => fn($q) => $q->where('vendor_id', $this->vendor()->id), 'order']);

        return view('vendor.orders.show', compact('vendorOrder'));
    }
}
