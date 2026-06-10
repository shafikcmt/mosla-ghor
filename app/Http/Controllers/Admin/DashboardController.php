<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\Order;
use App\Models\Product;
use App\Models\WholesaleChatMessage;
use App\Models\WholesaleEnquiry;
use App\Models\WholesaleQuote;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $statusCounts = Order::select('order_status', DB::raw('count(*) as count'))
            ->groupBy('order_status')
            ->pluck('count', 'order_status')
            ->all();

        $stats = [
            'total_orders'    => Order::count(),
            'pending'         => $statusCounts['pending']    ?? 0,
            'confirmed'       => $statusCounts['confirmed']  ?? 0,
            'processing'      => $statusCounts['processing'] ?? 0,
            'shipped'         => $statusCounts['shipped']    ?? 0,
            'delivered'       => $statusCounts['delivered']  ?? 0,
            'cancelled'       => $statusCounts['cancelled']  ?? 0,
            'total_sales'     => (float) Order::sum('grand_total'),
            'today_sales'     => (float) Order::whereDate('created_at', today())->sum('grand_total'),
            'today_orders'    => Order::whereDate('created_at', today())->count(),
            'total_products'  => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'total_combos'    => Combo::count(),
            'active_combos'   => Combo::where('is_active', true)->count(),
        ];

        $enquiryStats = [
            'new_enquiries'   => WholesaleEnquiry::where('status', 'pending')->count(),
            'new_quotes'      => WholesaleQuote::where('status', 'sent_to_customer')->count(),
            'new_messages'    => WholesaleChatMessage::where('sender_type', '!=', 'admin')->where('is_read_by_admin', false)->count(),
            'confirmed_orders'=> WholesaleQuote::where('status', 'converted_to_order')->count(),
        ];

        $recentOrders = Order::latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'enquiryStats', 'recentOrders'));
    }
}
