<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class CustomerAccountController extends CustomerBaseController
{
    private const CANCELLABLE = ['pending', 'confirmed', 'processing'];

    public function dashboard()
    {
        $customer = $this->currentCustomer();
        $phone    = Auth::user()->phone;

        $stats = $this->ordersQuery()->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN order_status = 'pending'   THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
            SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
            SUM(grand_total) as total_spent
        ")->first();

        $recentOrders = $this->ordersQuery()->latest()->limit(5)->get();

        $enquiryStats = ['my_enquiries' => 0, 'quotes_received' => 0, 'pending_confirmation' => 0, 'confirmed_orders' => 0];
        if ($customer) {
            $enquiryStats = [
                'my_enquiries'         => \App\Models\WholesaleEnquiry::where('customer_id', $customer->id)->count(),
                'quotes_received'      => \App\Models\WholesaleQuote::where('customer_id', $customer->id)->whereIn('status', \App\Models\WholesaleQuote::CUSTOMER_VISIBLE_STATUSES)->count(),
                'pending_confirmation' => \App\Models\WholesaleQuote::where('customer_id', $customer->id)->where('status', 'sent_to_customer')->count(),
                'confirmed_orders'     => \App\Models\WholesaleQuote::where('customer_id', $customer->id)->where('status', 'converted_to_order')->count(),
            ];
        }

        return view('customer.dashboard', compact('customer', 'stats', 'recentOrders', 'enquiryStats'));
    }

    public function orders()
    {
        $status = request('status');
        $query  = $this->ordersQuery()->latest();

        if ($status) {
            $query->where('order_status', $status);
        }

        $orders = $query->paginate(15);

        return view('customer.orders.index', compact('orders', 'status'));
    }

    public function orderShow(int $id)
    {
        $order = $this->findOwnOrder($id);
        $order->load('items.product', 'selectedCourier');

        $canCancel = in_array($order->order_status, self::CANCELLABLE);
        $canReturn = $order->order_status === 'delivered'
            && ($order->delivered_at
                ? $order->delivered_at->diffInDays(now()) <= 7
                : $order->updated_at->diffInDays(now()) <= 7)
            && ! $order->returnRequests()->where('user_id', Auth::id())->exists();

        return view('customer.orders.show', compact('order', 'canCancel', 'canReturn'));
    }

    public function cancelOrder(\Illuminate\Http\Request $request, int $id)
    {
        $order = $this->findOwnOrder($id);

        if (! in_array($order->order_status, self::CANCELLABLE)) {
            return back()->with('error', 'এই অর্ডারটি এখন বাতিল করা যাবে না।');
        }

        $request->validate(['reason' => 'required|string|max:300'], [
            'reason.required' => 'বাতিলের কারণ লিখুন।',
        ]);

        $order->update([
            'order_status'        => 'cancelled',
            'cancellation_reason' => $request->reason,
            'cancelled_by'        => 'customer',
            'cancelled_at'        => now(),
        ]);

        return redirect()->route('customer.orders.show', $id)
            ->with('success', 'অর্ডারটি বাতিল করা হয়েছে।');
    }
}
