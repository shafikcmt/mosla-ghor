<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class TrackOrderController extends Controller
{
    public function index()
    {
        return view('track-order', ['order' => null, 'searched' => false]);
    }

    public function track(Request $request)
    {
        $data = $request->validate([
            'order_number' => 'required|string',
            'phone'        => 'required|string',
        ], [
            'order_number.required' => 'অর্ডার নম্বর দিন।',
            'phone.required'        => 'মোবাইল নম্বর দিন।',
        ]);

        $order = Order::where('order_number', $data['order_number'])
            ->where('mobile_number', $data['phone'])
            ->with(['items', 'selectedCourier'])
            ->first();

        return view('track-order', ['order' => $order, 'searched' => true]);
    }
}
