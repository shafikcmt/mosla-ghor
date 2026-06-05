<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorPickupPoint;
use Illuminate\Http\Request;

class VendorPickupPointController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorPickupPoint::with('vendor')
            ->orderByDesc('is_default')->orderBy('vendor_id');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        $pickupPoints = $query->paginate(30)->withQueryString();
        $vendors = Vendor::orderBy('shop_name')->get(['id', 'shop_name']);

        return view('admin.vendor-pickup-points.index', compact('pickupPoints', 'vendors'));
    }

    public function create(Request $request)
    {
        $vendors = Vendor::orderBy('shop_name')->get(['id', 'shop_name']);
        $selectedVendorId = $request->vendor_id;

        return view('admin.vendor-pickup-points.create', compact('vendors', 'selectedVendorId'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request, true);

        $isFirst = VendorPickupPoint::where('vendor_id', $data['vendor_id'])->count() === 0;
        $data['is_default'] = $isFirst ? true : $request->boolean('is_default');

        $point = VendorPickupPoint::create($data);

        if ($point->is_default) {
            $point->makeDefault();
        }

        return redirect()->route('admin.vendor-pickup-points.index', ['vendor_id' => $point->vendor_id])
            ->with('success', 'পিকআপ পয়েন্ট যোগ করা হয়েছে।');
    }

    public function edit(VendorPickupPoint $vendorPickupPoint)
    {
        $vendors = Vendor::orderBy('shop_name')->get(['id', 'shop_name']);

        return view('admin.vendor-pickup-points.edit', [
            'pickupPoint' => $vendorPickupPoint,
            'vendors'     => $vendors,
        ]);
    }

    public function update(Request $request, VendorPickupPoint $vendorPickupPoint)
    {
        $data = $this->validateData($request, false);
        $data['is_default'] = $request->boolean('is_default');

        // vendor_id is fixed on edit to avoid reparenting an address by mistake.
        unset($data['vendor_id']);

        $vendorPickupPoint->update($data);

        if ($data['is_default']) {
            $vendorPickupPoint->makeDefault();
        }

        return redirect()->route('admin.vendor-pickup-points.index', ['vendor_id' => $vendorPickupPoint->vendor_id])
            ->with('success', 'পিকআপ পয়েন্ট আপডেট হয়েছে।');
    }

    public function destroy(VendorPickupPoint $vendorPickupPoint)
    {
        $wasDefault = $vendorPickupPoint->is_default;
        $vendorId   = $vendorPickupPoint->vendor_id;
        $vendorPickupPoint->delete();

        if ($wasDefault) {
            VendorPickupPoint::defaultFor($vendorId)?->update(['is_default' => true]);
        }

        return redirect()->route('admin.vendor-pickup-points.index', ['vendor_id' => $vendorId])
            ->with('success', 'পিকআপ পয়েন্ট মুছে ফেলা হয়েছে।');
    }

    public function setDefault(VendorPickupPoint $vendorPickupPoint)
    {
        $vendorPickupPoint->makeDefault();

        return redirect()->route('admin.vendor-pickup-points.index', ['vendor_id' => $vendorPickupPoint->vendor_id])
            ->with('success', $vendorPickupPoint->pickup_name . ' ডিফল্ট হিসেবে সেট হয়েছে।');
    }

    private function validateData(Request $request, bool $withVendor): array
    {
        $rules = [
            'pickup_name'         => 'required|string|max:100',
            'contact_person_name' => 'required|string|max:100',
            'phone'               => 'required|string|max:30',
            'alternate_phone'     => 'nullable|string|max:30',
            'address'             => 'required|string|max:255',
            'district'            => 'required|string|max:100',
            'city'                => 'required|string|max:100',
            'zone_area'           => 'nullable|string|max:100',
            'postal_code'         => 'nullable|string|max:20',
            'note'                => 'nullable|string|max:500',
            'status'              => 'required|in:active,inactive',
        ];

        if ($withVendor) {
            $rules['vendor_id'] = 'required|exists:vendors,id';
        }

        return $request->validate($rules);
    }
}
