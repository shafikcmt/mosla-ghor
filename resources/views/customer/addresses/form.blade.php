@extends('customer.layout')
@section('title', $address ? 'ঠিকানা সম্পাদনা' : 'নতুন ঠিকানা')

@section('content')
<div class="max-w-lg">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('customer.addresses.index') }}" class="text-sm text-gray-500 hover:text-[#14532d]">← ঠিকানা তালিকা</a>
    </div>

    <h1 class="text-xl font-bold text-gray-800 mb-5">{{ $address ? 'ঠিকানা সম্পাদনা' : 'নতুন ঠিকানা' }}</h1>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST"
              action="{{ $address ? route('customer.addresses.update', $address->id) : route('customer.addresses.store') }}"
              class="space-y-4">
            @csrf
            @if($address) @method('PUT') @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">লেবেল</label>
                <select name="label" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    @foreach(['বাড়ি','অফিস','অন্যান্য'] as $lbl)
                    <option value="{{ $lbl }}" {{ old('label', $address?->label) === $lbl ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">নাম <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $address?->name) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ফোন <span class="text-red-500">*</span></label>
                    <input type="tel" name="phone" value="{{ old('phone', $address?->phone) }}" placeholder="01XXXXXXXXX" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">বিভাগ <span class="text-red-500">*</span></label>
                    <select name="bd_division_id" id="ca-division" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                        <option value="">বিভাগ বেছে নিন</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">জেলা <span class="text-red-500">*</span></label>
                    <select name="bd_district_id" id="ca-district" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                        <option value="">জেলা বেছে নিন</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">উপজেলা <span class="text-red-500">*</span></label>
                    <select name="bd_upazila_id" id="ca-upazila" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                        <option value="">উপজেলা বেছে নিন</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">পূর্ণ ঠিকানা (বাড়ি/রোড/গ্রাম) <span class="text-red-500">*</span></label>
                <textarea name="full_address" required rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[#14532d]">{{ old('full_address', $address?->full_address) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ডেলিভারি জোন <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($activeZones as $zone)
                    <label class="block cursor-pointer">
                        <input type="radio" name="delivery_zone_id" value="{{ $zone->id }}" class="sr-only ca-zone" required>
                        <div class="ca-zone-card border-2 border-gray-200 rounded-xl px-3 py-2.5 text-center bg-white hover:border-[#14532d] transition-colors">
                            <div class="text-sm font-bold text-[#14532d]">{{ $zone->zone_name }}</div>
                            <div class="text-xs text-gray-400">৳{{ number_format($zone->delivery_charge, 0) }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            <div id="ca-location-wrap" style="display:none">
                <label class="block text-sm font-medium text-gray-700 mb-1">এলাকা <span class="text-red-500">*</span></label>
                <select name="delivery_location_id" id="ca-location"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    <option value="">এলাকা বেছে নিন</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_default" id="is_default" value="1"
                       {{ old('is_default', $address?->is_default) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-[#14532d]">
                <label for="is_default" class="text-sm text-gray-700">ডিফল্ট ঠিকানা হিসেবে সেট করুন</label>
            </div>

            <button type="submit"
                    class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                {{ $address ? 'আপডেট করুন' : 'ঠিকানা যোগ করুন' }}
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const BD_DIVISIONS = @json($bdDivisions);
const BD_DISTRICTS = @json($bdDistricts);
const BD_UPAZILAS  = @json($bdUpazilas);
const CHECKOUT_ZONES = @json($zonesForJs);
@php
    $pre = [
        'division' => old('bd_division_id', $address?->bd_division_id),
        'district' => old('bd_district_id', $address?->bd_district_id),
        'upazila'  => old('bd_upazila_id', $address?->bd_upazila_id),
        'zone'     => old('delivery_zone_id', $address?->delivery_zone_id),
        'location' => old('delivery_location_id', $address?->delivery_location_id),
    ];
@endphp
const PRE = @json($pre);

(function () {
    const divSel = document.getElementById('ca-division');
    const disSel = document.getElementById('ca-district');
    const upaSel = document.getElementById('ca-upazila');
    const locWrap = document.getElementById('ca-location-wrap');
    const locSel  = document.getElementById('ca-location');

    function fill(sel, rows, placeholder) {
        sel.innerHTML = '<option value="">' + placeholder + '</option>';
        rows.forEach(r => { const o = document.createElement('option'); o.value = r.id; o.textContent = r.bn_name; sel.appendChild(o); });
    }
    function fillLocations(zoneId) {
        const zone = CHECKOUT_ZONES.find(z => String(z.id) === String(zoneId));
        locSel.innerHTML = '<option value="">এলাকা বেছে নিন</option>';
        (zone ? zone.locations : []).forEach(l => {
            const o = document.createElement('option');
            o.value = l.id;
            o.textContent = l.location_name + (l.delivery_charge !== null ? ' (৳' + l.delivery_charge + ')' : '');
            locSel.appendChild(o);
        });
        locWrap.style.display = '';
    }

    fill(divSel, BD_DIVISIONS, 'বিভাগ বেছে নিন');
    divSel.addEventListener('change', function () {
        fill(disSel, BD_DISTRICTS.filter(d => String(d.division_id) === String(this.value)), 'জেলা বেছে নিন');
        fill(upaSel, [], 'উপজেলা বেছে নিন');
    });
    disSel.addEventListener('change', function () {
        fill(upaSel, BD_UPAZILAS.filter(u => String(u.district_id) === String(this.value)), 'উপজেলা বেছে নিন');
    });
    document.querySelectorAll('.ca-zone').forEach(radio => {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.ca-zone-card').forEach(c => c.classList.remove('border-[#14532d]','bg-green-50/40'));
            const card = this.closest('label').querySelector('.ca-zone-card');
            if (card) card.classList.add('border-[#14532d]','bg-green-50/40');
            fillLocations(this.value);
        });
    });

    // Preselect existing/old values
    if (PRE.division) {
        divSel.value = PRE.division; divSel.dispatchEvent(new Event('change'));
        if (PRE.district) { disSel.value = PRE.district; disSel.dispatchEvent(new Event('change')); }
        if (PRE.upazila) upaSel.value = PRE.upazila;
    }
    if (PRE.zone) {
        const zr = document.querySelector('.ca-zone[value="' + PRE.zone + '"]');
        if (zr) { zr.checked = true; zr.dispatchEvent(new Event('change')); if (PRE.location) locSel.value = PRE.location; }
    }
})();
</script>
@endsection
