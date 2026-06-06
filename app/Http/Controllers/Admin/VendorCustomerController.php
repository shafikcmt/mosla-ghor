<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\VendorCustomer;
use App\Notifications\PaymentUpdatedNotification;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin oversight of every vendor's local customers (read-only list + detail).
 */
class VendorCustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorCustomer::with('vendor')->withCount('orders')->latest();

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->vendor_id);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('whatsapp', 'like', "%{$s}%");
            });
        }
        if ($request->status === 'due') {
            $query->where('due_balance', '>', 0);
        }

        $customers = $query->paginate(25)->withQueryString();
        $vendors   = Vendor::orderBy('shop_name')->get(['id', 'shop_name']);

        $summary = [
            'total'     => VendorCustomer::count(),
            'due_total' => VendorCustomer::sum('due_balance'),
        ];

        return view('admin.vendor-customers.index', compact('customers', 'vendors', 'summary'));
    }

    public function show(VendorCustomer $vendorCustomer)
    {
        $vendorCustomer->load(['vendor', 'orders' => fn ($q) => $q->latest()]);

        return view('admin.vendor-customers.show', compact('vendorCustomer'));
    }

    /** Admin toggles a vendor customer active/inactive. */
    public function toggleStatus(VendorCustomer $vendorCustomer)
    {
        $vendorCustomer->update([
            'status' => $vendorCustomer->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'কাস্টমার স্ট্যাটাস আপডেট হয়েছে।');
    }

    /** Admin settles (records) a payment against a vendor order's due. */
    public function settleOrder(Request $request, Order $order)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($order->due_amount <= 0) {
            return back()->with('error', 'এই অর্ডারে কোনো বাকি নেই।');
        }

        $amount = min((float) $data['amount'], (float) $order->due_amount);
        $paid   = round((float) $order->paid_amount + $amount, 2);
        $due    = round((float) $order->grand_total - $paid, 2);

        DB::transaction(function () use ($order, $paid, $due, $amount) {
            $order->update([
                'paid_amount'         => $paid,
                'partial_paid_amount' => $paid,
                'due_amount'          => max(0, $due),
                'payment_status'      => $due <= 0 ? 'paid' : 'partial',
            ]);
            if ($order->vendorCustomer) {
                $newBal = max(0, (float) $order->vendorCustomer->due_balance - $amount);
                $order->vendorCustomer->update(['due_balance' => $newBal]);
            }
        });

        // Let the vendor know admin recorded a payment on their order.
        $order->loadMissing('createdByVendor');
        Notify::vendor($order->createdByVendor, new PaymentUpdatedNotification($order->fresh(), $due <= 0 ? 'paid' : 'partial'));

        return back()->with('success', '৳' . number_format($amount, 0) . ' পেমেন্ট রেকর্ড করা হয়েছে।');
    }
}
