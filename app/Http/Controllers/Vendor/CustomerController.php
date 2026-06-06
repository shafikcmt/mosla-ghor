<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorCustomer;
use App\Support\VendorSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    private function vendor()
    {
        return Auth::user()->vendor;
    }

    /** Approved + feature toggle on. */
    private function guard()
    {
        $vendor = $this->vendor();
        if (! $vendor?->isApproved()) {
            abort(403, 'অ্যাকাউন্ট অনুমোদিত হয়নি।');
        }
        if (! VendorSettings::vendorCanCreateCustomer()) {
            abort(403, 'কাস্টমার ম্যানেজমেন্ট বন্ধ আছে।');
        }
        return $vendor;
    }

    private function ownCustomer(VendorCustomer $customer): VendorCustomer
    {
        if ($customer->vendor_id !== $this->vendor()?->id) {
            abort(403, 'এই কাস্টমারে আপনার অ্যাক্সেস নেই।');
        }
        return $customer;
    }

    public function index(Request $request)
    {
        $vendor = $this->guard();

        $query = $vendor->customers()->withCount('orders')->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('whatsapp', 'like', "%{$s}%");
            });
        }
        if ($request->filled('type')) {
            $query->where('customer_type', $request->type);
        }
        if ($request->status === 'due') {
            $query->where('due_balance', '>', 0);
        }

        $customers = $query->paginate(20)->withQueryString();

        $all     = $vendor->customers();
        $summary = [
            'total'     => (clone $all)->count(),
            'due_count' => (clone $all)->where('due_balance', '>', 0)->count(),
            'due_total' => (clone $all)->sum('due_balance'),
        ];

        return view('vendor.customers.index', compact('vendor', 'customers', 'summary'));
    }

    public function create()
    {
        $this->guard();

        return view('vendor.customers.create', ['vendor' => $this->vendor()]);
    }

    public function store(Request $request)
    {
        $vendor = $this->guard();

        $data = $this->validateData($request);
        $data['vendor_id'] = $vendor->id;

        VendorCustomer::create($data);

        return redirect()->route('vendor.customers.index')->with('success', 'কাস্টমার যোগ হয়েছে।');
    }

    public function edit(VendorCustomer $customer)
    {
        $this->guard();
        $this->ownCustomer($customer);

        return view('vendor.customers.edit', ['vendor' => $this->vendor(), 'customer' => $customer]);
    }

    public function update(Request $request, VendorCustomer $customer)
    {
        $this->guard();
        $this->ownCustomer($customer);

        $customer->update($this->validateData($request));

        return redirect()->route('vendor.customers.index')->with('success', 'কাস্টমার আপডেট হয়েছে।');
    }

    public function destroy(VendorCustomer $customer)
    {
        $this->guard();
        $this->ownCustomer($customer);

        if ($customer->orders()->exists()) {
            return back()->with('error', 'এই কাস্টমারের অর্ডার আছে, মুছে ফেলা যাবে না। বরং নিষ্ক্রিয় করুন।');
        }

        $customer->delete();

        return back()->with('success', 'কাস্টমার মুছে ফেলা হয়েছে।');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'phone'         => 'required|string|max:30',
            'whatsapp'      => 'nullable|string|max:30',
            'email'         => 'nullable|email|max:150',
            'address'       => 'nullable|string|max:500',
            'district'      => 'nullable|string|max:100',
            'area'          => 'nullable|string|max:100',
            'customer_type' => ['nullable', Rule::in(VendorCustomer::CUSTOMER_TYPES)],
            'notes'         => 'nullable|string|max:1000',
            'status'        => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $data['customer_type'] = $data['customer_type'] ?: 'Regular';
        $data['status']        = $data['status'] ?: 'active';

        return $data;
    }
}
