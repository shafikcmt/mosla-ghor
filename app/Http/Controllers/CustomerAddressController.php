<?php

namespace App\Http\Controllers;

use App\Models\BdDistrict;
use App\Models\BdDivision;
use App\Models\BdUpazila;
use App\Models\CustomerAddress;
use App\Models\DeliveryZone;
use App\Services\CheckoutException;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAddressController extends CustomerBaseController
{
    public function __construct(private CheckoutService $checkout)
    {
    }

    public function index()
    {
        $addresses = CustomerAddress::where('user_id', Auth::id())->orderByDesc('is_default')->get();
        return view('customer.addresses.index', compact('addresses'));
    }

    public function create()
    {
        return view('customer.addresses.form', array_merge(['address' => null], $this->formData()));
    }

    public function store(Request $request)
    {
        $result = $this->resolve($request);
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        if ($result['is_default']) {
            CustomerAddress::where('user_id', Auth::id())->update(['is_default' => false]);
        }

        // Reuse an identical saved address instead of creating a duplicate.
        $existing = CustomerAddress::findDuplicateFor(Auth::id(), $result['data']);

        if ($existing) {
            $existing->update(array_merge($result['data'], $result['is_default'] ? ['is_default' => true] : []));
            return redirect()->route('customer.addresses.index')->with('success', 'ঠিকানা আপডেট হয়েছে।');
        }

        CustomerAddress::create(array_merge($result['data'], [
            'user_id'    => Auth::id(),
            'is_default' => $result['is_default'],
        ]));

        return redirect()->route('customer.addresses.index')->with('success', 'ঠিকানা যোগ হয়েছে।');
    }

    public function edit(CustomerAddress $address)
    {
        abort_unless($address->user_id === Auth::id(), 403);
        return view('customer.addresses.form', array_merge(compact('address'), $this->formData()));
    }

    public function update(Request $request, CustomerAddress $address)
    {
        abort_unless($address->user_id === Auth::id(), 403);

        $result = $this->resolve($request);
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        if ($result['is_default']) {
            CustomerAddress::where('user_id', Auth::id())->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update(array_merge($result['data'], ['is_default' => $result['is_default']]));

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

    /** Validate + resolve names/IDs; returns ['data'=>..., 'is_default'=>bool] or a redirect on error. */
    private function resolve(Request $request)
    {
        $data = $request->validate([
            'label'                => 'nullable|string|max:50',
            'name'                 => 'required|string|max:100',
            'phone'                => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'full_address'         => 'required|string|max:500',
            'bd_division_id'       => 'required|integer|exists:bd_divisions,id',
            'bd_district_id'       => 'required|integer|exists:bd_districts,id',
            'bd_upazila_id'        => 'required|integer|exists:bd_upazilas,id',
            'bd_union_id'          => 'nullable|integer|exists:bd_unions,id',
            'delivery_zone_id'     => 'required|integer|exists:delivery_zones,id',
            'delivery_location_id' => 'required|integer|exists:delivery_locations,id',
            'is_default'           => 'boolean',
        ], [
            'name.required'                 => 'নাম লিখুন।',
            'phone.required'                => 'ফোন নম্বর দিন।',
            'phone.regex'                   => 'সঠিক মোবাইল নম্বর দিন। যেমন: 01700000000',
            'full_address.required'         => 'পূর্ণ ঠিকানা লিখুন।',
            'bd_division_id.required'       => 'বিভাগ বেছে নিন।',
            'bd_district_id.required'       => 'জেলা বেছে নিন।',
            'bd_upazila_id.required'        => 'উপজেলা বেছে নিন।',
            'delivery_zone_id.required'     => 'ডেলিভারি জোন বেছে নিন।',
            'delivery_location_id.required' => 'ডেলিভারি এলাকা বেছে নিন।',
        ]);

        try {
            $bd   = $this->checkout->verifyBdHierarchy(
                (int) $data['bd_division_id'], (int) $data['bd_district_id'],
                (int) $data['bd_upazila_id'], $data['bd_union_id'] ?? null
            );
            $zone = $this->checkout->resolveCharge((int) $data['delivery_zone_id'], (int) $data['delivery_location_id'], 0);
        } catch (CheckoutException $e) {
            return back()->withInput()->withErrors([$e->field => $e->getMessage()]);
        }

        return [
            'is_default' => (bool) ($data['is_default'] ?? false),
            'data' => [
                'label'                => ($data['label'] ?? null) ?: 'বাড়ি',
                'name'                 => $data['name'],
                'phone'                => $data['phone'],
                'full_address'         => $data['full_address'],
                'division_name'        => $bd['division']->bn_name,
                'district_name'        => $bd['district']->bn_name,
                'upazila_name'         => $bd['upazila']->bn_name,
                'union_name'           => $bd['union']?->bn_name,
                'delivery_zone_id'     => $zone['zone']->id,
                'delivery_location_id' => $zone['location']->id,
                'delivery_area'        => $zone['zone']->zone_type,
                'bd_division_id'       => $bd['division']->id,
                'bd_district_id'       => $bd['district']->id,
                'bd_upazila_id'        => $bd['upazila']->id,
                'bd_union_id'          => $bd['union']?->id,
            ],
        ];
    }

    /** BD + zones data the address form needs for its cascade. */
    private function formData(): array
    {
        $activeZones = DeliveryZone::active()->with('activeLocations')->orderBy('sort_order')->orderBy('zone_name')->get();

        return [
            'activeZones' => $activeZones,
            'bdDivisions' => BdDivision::where('is_active', true)->orderBy('bn_name')->get(['id', 'bn_name']),
            'bdDistricts' => BdDistrict::where('is_active', true)->orderBy('bn_name')->get(['id', 'division_id', 'bn_name']),
            'bdUpazilas'  => BdUpazila::where('is_active', true)->orderBy('bn_name')->get(['id', 'district_id', 'bn_name']),
            'zonesForJs'  => $activeZones->map(fn($z) => [
                'id'        => $z->id,
                'zone_name' => $z->zone_name,
                'delivery_charge' => (float) $z->delivery_charge,
                'locations' => $z->activeLocations->map(fn($l) => [
                    'id'              => $l->id,
                    'location_name'   => $l->location_name,
                    'delivery_charge' => $l->delivery_charge !== null ? (float) $l->delivery_charge : null,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
