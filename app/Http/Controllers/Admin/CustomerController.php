<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('mobile_number', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('marketing')) {
            $query->where('accepts_marketing', $request->input('marketing') === '1');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === '1');
        }

        $customers = $query->orderByDesc('last_order_at')->paginate(25)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $recentOrders = $customer->orders()->latest()->limit(10)->get();
        $siteUrl      = url('/');
        $sitePhone    = WebsiteSetting::get('phone', '');

        return view('admin.customers.show', compact('customer', 'recentOrders', 'siteUrl', 'sitePhone'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'notes'     => 'nullable|string|max:2000',
            'is_active' => 'required|boolean',
        ]);

        $customer->update($data);

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'কাস্টমার তথ্য আপডেট হয়েছে।');
    }

    public function export()
    {
        $customers = Customer::where('accepts_marketing', true)
            ->where('is_active', true)
            ->orderByDesc('last_order_at')
            ->get(['name', 'mobile_number', 'total_orders', 'total_spent', 'last_order_at']);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="marketing-customers-' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($customers) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['নাম', 'মোবাইল', 'মোট অর্ডার', 'মোট খরচ (৳)', 'শেষ অর্ডার']);
            foreach ($customers as $c) {
                fputcsv($handle, [
                    $c->name,
                    $c->mobile_number,
                    $c->total_orders,
                    number_format((float) $c->total_spent, 2),
                    $c->last_order_at?->format('Y-m-d H:i') ?? '',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
