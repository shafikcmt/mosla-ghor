<?php

namespace App\Http\Controllers;

use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAddressController extends CustomerBaseController
{
    public function index()
    {
        $addresses = CustomerAddress::where('user_id', Auth::id())->orderByDesc('is_default')->get();
        return view('customer.addresses.index', compact('addresses'));
    }

    public function create()
    {
        return view('customer.addresses.form', ['address' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($data['is_default']) {
            CustomerAddress::where('user_id', Auth::id())->update(['is_default' => false]);
        }

        CustomerAddress::create(array_merge($data, ['user_id' => Auth::id()]));

        return redirect()->route('customer.addresses.index')->with('success', 'ঠিকানা যোগ হয়েছে।');
    }

    public function edit(CustomerAddress $address)
    {
        abort_unless($address->user_id === Auth::id(), 403);
        return view('customer.addresses.form', compact('address'));
    }

    public function update(Request $request, CustomerAddress $address)
    {
        abort_unless($address->user_id === Auth::id(), 403);
        $data = $this->validated($request);

        if ($data['is_default']) {
            CustomerAddress::where('user_id', Auth::id())->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return redirect()->route('customer.addresses.index')->with('success', 'ঠিকানা আপডেট হয়েছে।');
    }

    public function destroy(CustomerAddress $address)
    {
        abort_unless($address->user_id === Auth::id(), 403);
        $address->delete();
        return back()->with('success', 'ঠিকানা মুছে ফেলা হয়েছে।');
    }

    public function setDefault(CustomerAddress $address)
    {
        abort_unless($address->user_id === Auth::id(), 403);
        CustomerAddress::where('user_id', Auth::id())->update(['is_default' => false]);
        $address->update(['is_default' => true]);
        return back()->with('success', 'ডিফল্ট ঠিকানা সেট হয়েছে।');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label'         => 'required|string|max:50',
            'name'          => 'required|string|max:100',
            'phone'         => 'required|string|max:20',
            'division_name' => 'nullable|string|max:80',
            'district_name' => 'nullable|string|max:80',
            'upazila_name'  => 'nullable|string|max:80',
            'union_name'    => 'nullable|string|max:80',
            'full_address'  => 'required|string|max:500',
            'is_default'    => 'boolean',
        ], [
            'label.required'        => 'লেবেল লিখুন।',
            'name.required'         => 'নাম লিখুন।',
            'phone.required'        => 'ফোন নম্বর লিখুন।',
            'full_address.required' => 'পূর্ণ ঠিকানা লিখুন।',
        ]);
    }
}
