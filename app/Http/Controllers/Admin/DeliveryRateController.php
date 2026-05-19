<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\DeliveryRate;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;

class DeliveryRateController extends Controller
{
    public function index()
    {
        $rates = DeliveryRate::with(['courier', 'zone'])
            ->orderBy('courier_id')
            ->orderBy('min_weight')
            ->get();

        return view('admin.delivery-rates.index', compact('rates'));
    }

    public function create()
    {
        $couriers = Courier::orderBy('name')->get();
        $zones    = DeliveryZone::where('is_active', true)->orderBy('zone_name')->get();
        $zoneTypes = [
            'inside_dhaka'  => 'ঢাকার ভেতরে',
            'dhaka_sub_area'=> 'ঢাকা সাব-এরিয়া',
            'outside_dhaka' => 'ঢাকার বাইরে',
            'upazila'       => 'উপজেলা',
            'union'         => 'ইউনিয়ন / প্রত্যন্ত',
        ];
        return view('admin.delivery-rates.create', compact('couriers', 'zones', 'zoneTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'courier_id'               => 'required|exists:couriers,id',
            'delivery_zone_id'         => 'nullable|exists:delivery_zones,id',
            'zone_type'                => 'nullable|string|max:50',
            'min_weight'               => 'required|integer|min:0',
            'max_weight'               => 'required|integer|min:1',
            'courier_cost'             => 'required|numeric|min:0',
            'customer_delivery_charge' => 'required|numeric|min:0',
            'cod_percentage'           => 'nullable|numeric|min:0|max:100',
            'return_charge'            => 'nullable|numeric|min:0',
            'is_active'                => 'boolean',
        ]);

        $data['is_active']      = $request->boolean('is_active', true);
        $data['cod_percentage'] = $data['cod_percentage'] ?? 0;
        $data['return_charge']  = $data['return_charge'] ?? 0;

        DeliveryRate::create($data);

        return redirect()->route('admin.delivery-rates.index')->with('success', 'ডেলিভারি রেট যোগ করা হয়েছে।');
    }

    public function edit(DeliveryRate $deliveryRate)
    {
        $couriers  = Courier::orderBy('name')->get();
        $zones     = DeliveryZone::where('is_active', true)->orderBy('zone_name')->get();
        $zoneTypes = [
            'inside_dhaka'  => 'ঢাকার ভেতরে',
            'dhaka_sub_area'=> 'ঢাকা সাব-এরিয়া',
            'outside_dhaka' => 'ঢাকার বাইরে',
            'upazila'       => 'উপজেলা',
            'union'         => 'ইউনিয়ন / প্রত্যন্ত',
        ];
        return view('admin.delivery-rates.edit', compact('deliveryRate', 'couriers', 'zones', 'zoneTypes'));
    }

    public function update(Request $request, DeliveryRate $deliveryRate)
    {
        $data = $request->validate([
            'courier_id'               => 'required|exists:couriers,id',
            'delivery_zone_id'         => 'nullable|exists:delivery_zones,id',
            'zone_type'                => 'nullable|string|max:50',
            'min_weight'               => 'required|integer|min:0',
            'max_weight'               => 'required|integer|min:1',
            'courier_cost'             => 'required|numeric|min:0',
            'customer_delivery_charge' => 'required|numeric|min:0',
            'cod_percentage'           => 'nullable|numeric|min:0|max:100',
            'return_charge'            => 'nullable|numeric|min:0',
            'is_active'                => 'boolean',
        ]);

        $data['is_active']      = $request->boolean('is_active');
        $data['cod_percentage'] = $data['cod_percentage'] ?? 0;
        $data['return_charge']  = $data['return_charge'] ?? 0;

        $deliveryRate->update($data);

        return redirect()->route('admin.delivery-rates.index')->with('success', 'ডেলিভারি রেট আপডেট হয়েছে।');
    }

    public function destroy(DeliveryRate $deliveryRate)
    {
        $deliveryRate->delete();
        return redirect()->route('admin.delivery-rates.index')->with('success', 'ডেলিভারি রেট মুছে ফেলা হয়েছে।');
    }
}
