<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use Illuminate\Http\Request;

class CourierOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['selectedCourier'])
            ->whereNotNull('courier_status')
            ->orWhereNotNull('selected_courier_id');

        // Re-query cleanly with filters
        $query = Order::with(['selectedCourier', 'zone'])
            ->where(function ($q) {
                $q->whereNotNull('courier_status')->orWhereNotNull('selected_courier_id');
            });

        if ($request->filled('courier_id')) {
            $query->where('selected_courier_id', $request->courier_id);
        }

        if ($request->filled('courier_status')) {
            $query->where('courier_status', $request->courier_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders   = $query->latest()->paginate(25)->withQueryString();
        $couriers = Courier::orderBy('name')->get();

        $courierStatuses = [
            'pending'          => 'অপেক্ষায়',
            'processing'       => 'প্রসেসিং',
            'ready_for_courier'=> 'কুরিয়ার প্রস্তুত',
            'sent_to_courier'  => 'কুরিয়ারে পাঠানো',
            'picked_up'        => 'পিক-আপ হয়েছে',
            'in_transit'       => 'ট্রানজিটে',
            'delivered'        => 'ডেলিভারড',
            'returned'         => 'ফেরত এসেছে',
            'cancelled'        => 'বাতিল',
            'failed_delivery'  => 'ডেলিভারি ব্যর্থ',
        ];

        return view('admin.courier-orders.index', compact('orders', 'couriers', 'courierStatuses'));
    }
}
