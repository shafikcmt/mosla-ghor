<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Models\WholesaleEnquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    /** Delete a (dummy/test) customer that never ordered. */
    public function destroy(Customer $customer)
    {
        if ($reason = $this->blockedReason($customer)) {
            return back()->with('error', "{$customer->name}: {$reason}");
        }

        $name = $customer->name;
        $this->purge($customer);

        return redirect()->route('admin.customers.index')->with('success', "কাস্টমার \"{$name}\" মুছে ফেলা হয়েছে।");
    }

    /** Delete the ticked customers; ones with orders are kept. */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'max:100'],
            'ids.*' => ['integer'],
        ], ['ids.required' => 'অন্তত একজন কাস্টমার বেছে নিন।']);

        $deleted = 0;
        $kept    = [];
        foreach (Customer::whereIn('id', $data['ids'])->get() as $customer) {
            if ($this->blockedReason($customer)) {
                $kept[] = $customer->name;
            } else {
                $this->purge($customer);
                $deleted++;
            }
        }

        $msg = "{$deleted} জন কাস্টমার মুছে ফেলা হয়েছে।";
        if ($kept) {
            $msg .= ' অর্ডার থাকায় রাখা হয়েছে: '.implode(', ', $kept).'।';
        }

        return back()->with($deleted ? 'success' : 'error', $msg);
    }

    /** Orders are financial records — a customer who ordered is never deleted. */
    private function blockedReason(Customer $customer): ?string
    {
        $hasOrders = Order::withTrashed()
            ->where(fn ($q) => $q->where('customer_id', $customer->id)
                ->when($customer->mobile_number, fn ($q) => $q->orWhere('mobile_number', $customer->mobile_number)))
            ->exists();

        return $hasOrders ? 'এই কাস্টমারের অর্ডার আছে — রেকর্ড রক্ষায় মুছা যাবে না।' : null;
    }

    /** Remove the CRM row, its enquiries (+quotes/chat) and its customer login. */
    private function purge(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {
            foreach (WholesaleEnquiry::where('customer_id', $customer->id)->get() as $enquiry) {
                $enquiry->chatMessages()->delete();
                $enquiry->quotes()->delete();
                $enquiry->delete();
            }
            if ($customer->mobile_number) {
                // Only a plain customer login — never an admin/vendor account.
                User::where('phone', $customer->mobile_number)->where('role', 'customer')->get()->each->delete();
            }
            $customer->delete();
        });
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
