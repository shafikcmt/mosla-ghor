<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\CourierSetting;
use App\Models\VendorPickupPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PickupPointController extends Controller
{
    private function vendor()
    {
        return Auth::user()->vendor;
    }

    /**
     * Block access entirely when admin has disabled vendor pickup management.
     */
    private function ensureAllowed(): void
    {
        if (! CourierSetting::current()->vendorCanSetupPickup()) {
            abort(403, 'পিকআপ অ্যাড্রেস ব্যবস্থাপনার অনুমতি নেই।');
        }
    }

    private function ownOrFail(VendorPickupPoint $pickupPoint): void
    {
        if ($pickupPoint->vendor_id !== $this->vendor()?->id) {
            abort(403);
        }
    }

    public function index()
    {
        $this->ensureAllowed();

        $pickupPoints = $this->vendor()->pickupPoints()->orderByDesc('is_default')->orderBy('pickup_name')->get();

        return view('vendor.pickup-points.index', compact('pickupPoints'));
    }

    public function create()
    {
        $this->ensureAllowed();

        return view('vendor.pickup-points.create');
    }

    public function store(Request $request)
    {
        $this->ensureAllowed();

        $data = $this->validateData($request);
        $vendor = $this->vendor();

        // First pickup point is always the default.
        $isFirst = $vendor->pickupPoints()->count() === 0;
        $data['vendor_id']  = $vendor->id;
        $data['is_default'] = $isFirst ? true : $request->boolean('is_default');

        $point = VendorPickupPoint::create($data);

        if ($point->is_default) {
            $point->makeDefault();
        }

        return redirect()->route('vendor.pickup-points.index')
            ->with('success', 'পিকআপ পয়েন্ট যোগ করা হয়েছে।');
    }

    public function edit(VendorPickupPoint $pickupPoint)
    {
        $this->ensureAllowed();
        $this->ownOrFail($pickupPoint);

        return view('vendor.pickup-points.edit', compact('pickupPoint'));
    }

    public function update(Request $request, VendorPickupPoint $pickupPoint)
    {
        $this->ensureAllowed();
        $this->ownOrFail($pickupPoint);

        $data = $this->validateData($request);
        $data['is_default'] = $request->boolean('is_default');

        $pickupPoint->update($data);

        if ($data['is_default']) {
            $pickupPoint->makeDefault();
        }

        return redirect()->route('vendor.pickup-points.index')
            ->with('success', 'পিকআপ পয়েন্ট আপডেট হয়েছে।');
    }

    public function destroy(VendorPickupPoint $pickupPoint)
    {
        $this->ensureAllowed();
        $this->ownOrFail($pickupPoint);

        $wasDefault = $pickupPoint->is_default;
        $vendorId   = $pickupPoint->vendor_id;
        $pickupPoint->delete();

        // Promote another active pickup point to default if we removed the default.
        if ($wasDefault) {
            $next = VendorPickupPoint::defaultFor($vendorId);
            $next?->update(['is_default' => true]);
        }

        return redirect()->route('vendor.pickup-points.index')
            ->with('success', 'পিকআপ পয়েন্ট মুছে ফেলা হয়েছে।');
    }

    public function setDefault(VendorPickupPoint $pickupPoint)
    {
        $this->ensureAllowed();
        $this->ownOrFail($pickupPoint);

        $pickupPoint->makeDefault();

        return redirect()->route('vendor.pickup-points.index')
            ->with('success', $pickupPoint->pickup_name . ' ডিফল্ট পিকআপ পয়েন্ট হিসেবে সেট হয়েছে।');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
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
        ], [], [
            'pickup_name'         => 'পিকআপ নাম',
            'contact_person_name' => 'যোগাযোগ ব্যক্তির নাম',
            'phone'               => 'ফোন',
            'address'             => 'ঠিকানা',
            'district'            => 'জেলা',
            'city'                => 'শহর',
        ]);
    }
}
