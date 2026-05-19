<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryLocation;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;

class DeliveryLocationController extends Controller
{
    public function store(Request $request, DeliveryZone $deliveryZone)
    {
        $request->validate([
            'location_name'  => 'required|string|max:100',
            'keywords'       => 'nullable|string|max:500',
            'delivery_charge' => 'nullable|numeric|min:0',
        ]);

        $deliveryZone->locations()->create([
            'location_name'  => $request->location_name,
            'keywords'       => $request->keywords ?: null,
            'delivery_charge' => $request->delivery_charge ?: null,
            'is_active'      => true,
        ]);

        return redirect()->route('admin.delivery-zones.show', $deliveryZone)
            ->with('success', 'এলাকা যোগ হয়েছে।');
    }

    public function edit(DeliveryZone $deliveryZone, DeliveryLocation $location)
    {
        abort_unless($location->zone_id === $deliveryZone->id, 404);

        return view('admin.delivery-zones.location-edit', compact('deliveryZone', 'location'));
    }

    public function update(Request $request, DeliveryZone $deliveryZone, DeliveryLocation $location)
    {
        abort_unless($location->zone_id === $deliveryZone->id, 404);

        $request->validate([
            'location_name'  => 'required|string|max:100',
            'keywords'       => 'nullable|string|max:500',
            'delivery_charge' => 'nullable|numeric|min:0',
        ]);

        $location->update([
            'location_name'  => $request->location_name,
            'keywords'       => $request->keywords ?: null,
            'delivery_charge' => $request->delivery_charge ?: null,
            'is_active'      => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.delivery-zones.show', $deliveryZone)
            ->with('success', 'এলাকা আপডেট হয়েছে।');
    }

    public function destroy(DeliveryZone $deliveryZone, DeliveryLocation $location)
    {
        abort_unless($location->zone_id === $deliveryZone->id, 404);
        $location->delete();

        return redirect()->route('admin.delivery-zones.show', $deliveryZone)
            ->with('success', 'এলাকা মুছে ফেলা হয়েছে।');
    }

    public function toggle(DeliveryZone $deliveryZone, DeliveryLocation $location)
    {
        abort_unless($location->zone_id === $deliveryZone->id, 404);
        $location->update(['is_active' => ! $location->is_active]);

        return back()->with('success', $location->is_active ? 'এলাকা সক্রিয় হয়েছে।' : 'এলাকা নিষ্ক্রিয় হয়েছে।');
    }
}
