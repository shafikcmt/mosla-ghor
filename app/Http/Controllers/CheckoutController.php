<?php

namespace App\Http\Controllers;

use App\Models\BdDistrict;
use App\Models\BdDivision;
use App\Models\BdUpazila;
use App\Models\CustomerAddress;
use App\Models\DeliveryZone;
use App\Models\PaymentSetting;
use App\Services\CheckoutException;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function __construct(private CheckoutService $checkout)
    {
    }

    /** Box-builder hands the cart here; we stash it in the session and go to Review. */
    public function start(Request $request)
    {
        $validated = $request->validate([
            'combo_id'   => ['nullable', 'integer', 'exists:combos,id'],
            'items'      => ['nullable', 'array', 'max:20'],
            'items.*'    => ['integer', 'exists:product_prices,id'],
        ]);

        $comboId  = $validated['combo_id'] ?? null;
        $priceIds = array_map('intval', $validated['items'] ?? []);

        if (! $comboId && empty($priceIds)) {
            return redirect('/#combo-builder')->with('error', 'কমপক্ষে একটি পণ্য যোগ করুন।');
        }

        // Validate the cart up-front so Review never shows a broken state.
        try {
            $this->checkout->resolveItems($comboId, $priceIds);
        } catch (CheckoutException $e) {
            return redirect('/#combo-builder')->with('error', $e->getMessage());
        }

        session(['checkout' => [
            'combo_id'   => $comboId,
            'price_ids'  => $priceIds,
            'address_id' => session('checkout.address_id'),
        ]]);

        return redirect()->route('checkout.review');
    }

    public function review(Request $request)
    {
        $cart = $this->cartOrFail();
        if ($cart instanceof \Illuminate\Http\RedirectResponse) {
            return $cart;
        }

        try {
            $resolved = $this->checkout->resolveItems($cart['combo_id'] ?? null, $cart['price_ids'] ?? []);
        } catch (CheckoutException $e) {
            return redirect('/#combo-builder')->with('error', $e->getMessage());
        }

        $address       = $this->resolveAddress();
        $savedAddresses = Auth::check()
            ? CustomerAddress::where('user_id', Auth::id())->orderByDesc('is_default')->get()
            : collect();

        $charge = null;
        if ($address && $address->isCheckoutReady()) {
            try {
                $charge = $this->checkout->resolveCharge(
                    (int) $address->delivery_zone_id,
                    (int) $address->delivery_location_id,
                    $resolved['subtotal']
                );
            } catch (CheckoutException) {
                $charge = null; // stale zone/location → fall back to the form
            }
        }

        $activeZones = DeliveryZone::active()->with('activeLocations')->orderBy('sort_order')->orderBy('zone_name')->get();

        return view('checkout.review', [
            'items'          => $resolved['items'],
            'subtotal'       => $resolved['subtotal'],
            'address'        => ($address && $address->isCheckoutReady()) ? $address : null,
            'savedAddresses' => $savedAddresses,
            'charge'         => $charge,
            'packaging'      => $charge['packaging'] ?? 0,
            'activeZones'    => $activeZones,
            'zonesForJs'     => $this->zonesForJs($activeZones),
            'bdDivisions'    => BdDivision::where('is_active', true)->orderBy('bn_name')->get(['id', 'bn_name']),
            'bdDistricts'    => BdDistrict::where('is_active', true)->orderBy('bn_name')->get(['id', 'division_id', 'bn_name']),
            'bdUpazilas'     => BdUpazila::where('is_active', true)->orderBy('bn_name')->get(['id', 'district_id', 'bn_name']),
            'prefill'        => $this->addressPrefill(),
        ]);
    }

    /** Save a new address (logged-in → DB; guest → session) and return to Review. */
    public function storeAddress(Request $request)
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:100'],
            'phone'                => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'full_address'         => ['required', 'string', 'max:500'],
            'bd_division_id'       => ['required', 'integer', 'exists:bd_divisions,id'],
            'bd_district_id'       => ['required', 'integer', 'exists:bd_districts,id'],
            'bd_upazila_id'        => ['required', 'integer', 'exists:bd_upazilas,id'],
            'bd_union_id'          => ['nullable', 'integer', 'exists:bd_unions,id'],
            'delivery_zone_id'     => ['required', 'integer', 'exists:delivery_zones,id'],
            'delivery_location_id' => ['required', 'integer', 'exists:delivery_locations,id'],
            'label'                => ['nullable', 'string', 'max:50'],
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

        // Verify hierarchy + zone/location, capture region names for display.
        try {
            $bd   = $this->checkout->verifyBdHierarchy(
                (int) $data['bd_division_id'], (int) $data['bd_district_id'],
                (int) $data['bd_upazila_id'], $data['bd_union_id'] ?? null
            );
            $zoneInfo = $this->checkout->resolveCharge(
                (int) $data['delivery_zone_id'], (int) $data['delivery_location_id'], 0
            );
        } catch (CheckoutException $e) {
            return back()->withInput()->withErrors([$e->field => $e->getMessage()]);
        }

        $payload = [
            'label'                => ($data['label'] ?? null) ?: 'বাড়ি',
            'name'                 => $data['name'],
            'phone'                => $data['phone'],
            'full_address'         => $data['full_address'],
            'division_name'        => $bd['division']->bn_name,
            'district_name'        => $bd['district']->bn_name,
            'upazila_name'         => $bd['upazila']->bn_name,
            'union_name'           => $bd['union']?->bn_name,
            'delivery_zone_id'     => $zoneInfo['zone']->id,
            'delivery_location_id' => $zoneInfo['location']->id,
            'delivery_area'        => $zoneInfo['zone']->zone_type,
            'bd_division_id'       => $bd['division']->id,
            'bd_district_id'       => $bd['district']->id,
            'bd_upazila_id'        => $bd['upazila']->id,
            'bd_union_id'          => $bd['union']?->id,
        ];

        if (Auth::check()) {
            // First address (or none default yet) becomes the default automatically.
            $hasDefault = CustomerAddress::where('user_id', Auth::id())->where('is_default', true)->exists();
            if (! $hasDefault) {
                CustomerAddress::where('user_id', Auth::id())->update(['is_default' => false]);
            }
            $address = CustomerAddress::create(array_merge($payload, [
                'user_id'    => Auth::id(),
                'is_default' => ! $hasDefault,
            ]));
            $this->setCartAddress($address->id);
        } else {
            session(['checkout.guest_address' => $payload]);
        }

        return redirect()->route('checkout.review')->with('success', 'ঠিকানা সংরক্ষণ করা হয়েছে।');
    }

    /** Pick a different saved address (logged-in only). */
    public function selectAddress(CustomerAddress $address)
    {
        abort_unless(Auth::check() && $address->user_id === Auth::id(), 403);
        $this->setCartAddress($address->id);

        return redirect()->route('checkout.review');
    }

    public function payment(Request $request)
    {
        $cart = $this->cartOrFail();
        if ($cart instanceof \Illuminate\Http\RedirectResponse) {
            return $cart;
        }

        try {
            $resolved = $this->checkout->resolveItems($cart['combo_id'] ?? null, $cart['price_ids'] ?? []);
        } catch (CheckoutException $e) {
            return redirect('/#combo-builder')->with('error', $e->getMessage());
        }

        $address = $this->resolveAddress();
        if (! $address || ! $address->isCheckoutReady()) {
            return redirect()->route('checkout.review')->with('error', 'প্রথমে ডেলিভারি ঠিকানা নির্বাচন করুন।');
        }

        try {
            $charge = $this->checkout->resolveCharge(
                (int) $address->delivery_zone_id,
                (int) $address->delivery_location_id,
                $resolved['subtotal']
            );
        } catch (CheckoutException $e) {
            return redirect()->route('checkout.review')->with('error', $e->getMessage());
        }

        $settings        = PaymentSetting::current();
        $subtotal        = $resolved['subtotal'];
        $deliveryCharge  = $charge['delivery_charge'];
        $packaging       = $charge['packaging'];
        $instantDiscount = $settings->instantDiscountFor($subtotal);

        return view('checkout.payment', [
            'items'           => $resolved['items'],
            'comboId'         => $resolved['combo_id'],
            'subtotal'        => $subtotal,
            'packaging'       => $packaging,
            'deliveryCharge'  => $deliveryCharge,
            'instantDiscount' => $instantDiscount,
            'address'         => $address,
            'settings'        => $settings,
        ]);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /** @return array|\Illuminate\Http\RedirectResponse */
    private function cartOrFail()
    {
        $cart = session('checkout');
        if (! is_array($cart) || (empty($cart['combo_id']) && empty($cart['price_ids']))) {
            return redirect('/#combo-builder')->with('error', 'আপনার কার্ট খালি। প্রথমে পণ্য যোগ করুন।');
        }
        return $cart;
    }

    /** Compact zones+locations array for the address-form client JS. */
    private function zonesForJs($activeZones): array
    {
        return $activeZones->map(fn($z) => [
            'id'              => $z->id,
            'zone_name'       => $z->zone_name,
            'delivery_charge' => (float) $z->delivery_charge,
            'locations'       => $z->activeLocations->map(fn($l) => [
                'id'              => $l->id,
                'location_name'   => $l->location_name,
                'delivery_charge' => $l->delivery_charge !== null ? (float) $l->delivery_charge : null,
            ])->values()->all(),
        ])->values()->all();
    }

    private function setCartAddress(int $addressId): void
    {
        $cart = session('checkout', []);
        $cart['address_id'] = $addressId;
        session(['checkout' => $cart]);
    }

    /** Resolve the address in play: chosen saved → default → guest session. */
    private function resolveAddress(): ?CustomerAddress
    {
        if (Auth::check()) {
            $chosenId = session('checkout.address_id');
            if ($chosenId) {
                $a = CustomerAddress::where('user_id', Auth::id())->find($chosenId);
                if ($a) {
                    return $a;
                }
            }
            return CustomerAddress::where('user_id', Auth::id())
                ->orderByDesc('is_default')->first();
        }

        $guest = session('checkout.guest_address');
        return is_array($guest) ? (new CustomerAddress($guest)) : null;
    }

    /** Prefill the address form from the logged-in profile (so name/phone aren't retyped). */
    private function addressPrefill(): array
    {
        if (! Auth::check()) {
            $g = session('checkout.guest_address', []);
            return is_array($g) ? $g : [];
        }

        $user     = Auth::user();
        $customer = $user->customer;

        return [
            'name'         => $user->name,
            'phone'        => $user->phone,
            'full_address' => $customer?->last_full_address,
        ];
    }
}
