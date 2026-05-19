<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;

class DeliveryZoneController extends Controller
{
    public function index()
    {
        $zones = DeliveryZone::withCount('locations')
            ->orderBy('sort_order')
            ->orderBy('zone_name')
            ->get();

        return view('admin.delivery-zones.index', compact('zones'));
    }

    public function create()
    {
        return view('admin.delivery-zones.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DeliveryZone::create($data);

        return redirect()->route('admin.delivery-zones.index')
            ->with('success', 'ডেলিভারি জোন যোগ হয়েছে।');
    }

    public function show(DeliveryZone $deliveryZone)
    {
        $deliveryZone->load(['locations' => fn($q) => $q->orderBy('location_name')]);

        return view('admin.delivery-zones.show', compact('deliveryZone'));
    }

    public function edit(DeliveryZone $deliveryZone)
    {
        return view('admin.delivery-zones.edit', compact('deliveryZone'));
    }

    public function update(Request $request, DeliveryZone $deliveryZone)
    {
        $data = $this->validated($request, $deliveryZone->id);
        $deliveryZone->update($data);

        return redirect()->route('admin.delivery-zones.show', $deliveryZone)
            ->with('success', 'ডেলিভারি জোন আপডেট হয়েছে।');
    }

    public function destroy(DeliveryZone $deliveryZone)
    {
        $deliveryZone->delete();

        return redirect()->route('admin.delivery-zones.index')
            ->with('success', 'ডেলিভারি জোন মুছে ফেলা হয়েছে।');
    }

    public function toggle(DeliveryZone $deliveryZone)
    {
        $deliveryZone->update(['is_active' => ! $deliveryZone->is_active]);

        return back()->with('success', $deliveryZone->is_active ? 'জোন সক্রিয় হয়েছে।' : 'জোন নিষ্ক্রিয় হয়েছে।');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $request->validate([
            'zone_name'                    => 'required|string|max:100',
            'zone_type'                    => 'required|in:inside_dhaka,outside_dhaka,custom',
            'delivery_charge'              => 'required|numeric|min:0',
            'free_delivery_minimum_amount' => 'nullable|numeric|min:0',
            'sort_order'                   => 'nullable|integer|min:0',
        ]);

        return [
            'zone_name'                    => $request->zone_name,
            'zone_type'                    => $request->zone_type,
            'delivery_charge'              => $request->delivery_charge,
            'free_delivery_minimum_amount' => $request->free_delivery_minimum_amount ?: null,
            'is_active'                    => $request->boolean('is_active'),
            'sort_order'                   => $request->sort_order ?: null,
        ];
    }
}
