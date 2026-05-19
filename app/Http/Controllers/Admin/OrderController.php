<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::latest()->paginate(25);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('items');

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,verified,failed',
            'order_status'   => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
        ]);

        $order->update([
            'payment_status' => $request->payment_status,
            'order_status'   => $request->order_status,
        ]);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'অর্ডার স্ট্যাটাস আপডেট হয়েছে।');
    }
}
