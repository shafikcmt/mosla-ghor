@extends('storefront.layout')
@section('title', 'অর্ডার রিভিউ')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6">

    @include('checkout.partials.steps', ['active' => 'review'])

    @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif
    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    {{-- ── How to order (logged-out only; guest is the easy default) ──── --}}
    @guest
    <div id="guest-choice" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-4">
        <h2 class="text-base font-bold text-[#14532d] mb-1">কিভাবে অর্ডার করবেন?</h2>
        <p class="text-xs text-gray-500 mb-3">লগইন বা রেজিস্ট্রেশন বাধ্যতামূলক নয় — চাইলে গেস্ট হিসেবেই দ্রুত অর্ডার করুন।</p>
        <div class="grid sm:grid-cols-3 gap-2">
            <button type="button"
                    onclick="document.getElementById('guest-choice').classList.add('hidden'); document.getElementById('addr-section')?.scrollIntoView({behavior:'smooth'});"
                    class="text-left border-2 border-[#14532d] bg-green-50/40 rounded-xl p-3 hover:bg-green-50 transition-colors">
                <div class="text-sm font-bold text-[#14532d]">গেস্ট হিসেবে অর্ডার করুন</div>
                <div class="text-[11px] text-gray-500 mt-0.5">লগইন ছাড়াই দ্রুত অর্ডার করুন।</div>
            </button>
            <a href="{{ route('customer.login') }}?redirect={{ urlencode(request()->getRequestUri()) }}"
               class="block text-left border border-gray-200 rounded-xl p-3 hover:border-[#14532d] transition-colors">
                <div class="text-sm font-bold text-gray-800">লগইন করুন</div>
                <div class="text-[11px] text-gray-500 mt-0.5">সংরক্ষিত ঠিকানা ব্যবহার করুন ও অর্ডার ট্র্যাক করুন।</div>
            </a>
            <a href="{{ route('customer.register') }}?redirect={{ urlencode(request()->getRequestUri()) }}"
               class="block text-left border border-gray-200 rounded-xl p-3 hover:border-[#14532d] transition-colors">
                <div class="text-sm font-bold text-gray-800">নতুন অ্যাকাউন্ট</div>
                <div class="text-[11px] text-gray-500 mt-0.5">একবার অ্যাকাউন্ট করলে ভবিষ্যতে সহজ হবে।</div>
            </a>
        </div>
    </div>
    @endguest

    {{-- ── Delivery address ─────────────────────────────────────────── --}}
    <div id="addr-section" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-4">
        <h2 class="text-base font-bold text-[#14532d] mb-3">ডেলিভারি ঠিকানা</h2>

        @if($address)
            {{-- Saved-address summary card (no form unless the customer asks) --}}
            <div class="border border-green-100 bg-green-50/40 rounded-xl p-4 text-sm">
                <p class="font-bold text-gray-800">{{ $address->name }} <span class="font-normal text-gray-500">· {{ $address->phone }}</span></p>
                <p class="text-gray-600 mt-1">{{ $address->full_address }}</p>
                <p class="text-gray-500 text-xs mt-0.5">{{ $address->regionLine() }}</p>
            </div>

            {{-- Card actions --}}
            <div class="flex flex-wrap gap-2 mt-3">
                @if($charge)
                <a href="{{ route('checkout.payment') }}"
                   class="flex-1 min-w-[160px] text-center bg-[#14532d] hover:bg-[#166534] text-white text-sm font-semibold py-2.5 rounded-xl transition-colors">
                    এই ঠিকানায় ডেলিভারি দিন
                </a>
                @endif
                <button type="button" onclick="msToggle('addr-change')"
                        class="flex-1 min-w-[120px] text-center border border-[#14532d] text-[#14532d] text-sm font-semibold py-2.5 rounded-xl hover:bg-green-50 transition-colors">
                    ঠিকানা পরিবর্তন
                </button>
                <button type="button" onclick="msAddrNew()"
                        class="flex-1 min-w-[120px] text-center border border-gray-300 text-gray-600 text-sm font-semibold py-2.5 rounded-xl hover:bg-gray-50 transition-colors">
                    নতুন ঠিকানা যোগ করুন
                </button>
            </div>

            {{-- Saved-address list (hidden until "ঠিকানা পরিবর্তন") --}}
            <div id="addr-change" class="hidden mt-4 space-y-3">
                @auth
                    @foreach($savedAddresses as $sa)
                    <div class="flex items-center justify-between border rounded-xl px-4 py-3 text-sm {{ $sa->id === $address->id ? 'border-[#14532d] bg-green-50/40' : 'border-gray-200' }}">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-800 truncate">{{ $sa->name }} · {{ $sa->phone }}
                                @if($sa->is_default)<span class="text-[10px] bg-[#14532d] text-white px-1.5 py-0.5 rounded-full ml-1">ডিফল্ট</span>@endif
                            </p>
                            <p class="text-gray-500 text-xs truncate">{{ $sa->full_address }} — {{ $sa->regionLine() }}</p>
                            @unless($sa->isCheckoutReady())<p class="text-amber-600 text-[11px] mt-0.5">⚠ ডেলিভারি এলাকা সেট করা নেই</p>@endunless
                        </div>
                        @if($sa->id === $address->id)
                            <span class="flex-shrink-0 ml-3 text-xs text-[#14532d] font-semibold">✓ নির্বাচিত</span>
                        @elseif($sa->isCheckoutReady())
                        <form action="{{ route('checkout.address.select', $sa->id) }}" method="POST" class="flex-shrink-0 ml-3">
                            @csrf
                            <button class="text-xs bg-[#14532d] text-white px-3 py-1.5 rounded-lg">নির্বাচন</button>
                        </form>
                        @endif
                    </div>
                    @endforeach
                @endauth
                <button type="button" onclick="msAddrNew()" class="text-sm text-[#14532d] font-semibold">+ নতুন ঠিকানা যোগ করুন</button>
            </div>

            {{-- New-address form (sibling → can open from the card or the change panel) --}}
            <div id="addr-new" class="hidden mt-3 border-t border-gray-100 pt-4">
                @include('checkout.partials.address-form')
            </div>
        @else
            {{-- No usable address → show the form directly --}}
            <p class="text-sm text-gray-500 mb-3">ডেলিভারির জন্য আপনার ঠিকানা দিন।</p>
            @include('checkout.partials.address-form')
        @endif
    </div>

    {{-- ── Products ─────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-4">
        <h2 class="text-base font-bold text-[#14532d] mb-3">পণ্য ({{ count($items) }})</h2>
        <div class="divide-y divide-gray-50">
            @foreach($items as $item)
            <div class="flex items-center gap-3 py-3">
                <div class="w-14 h-14 rounded-lg bg-gray-50 border border-gray-100 flex-shrink-0 overflow-hidden">
                    @if(!empty($item['image']))
                    <img src="{{ asset($item['image']) }}" alt="" class="w-full h-full object-cover">
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $item['product_name'] }}</p>
                    <p class="text-xs text-gray-400">{{ $item['label'] ?? '' }}{{ !empty($item['variant_name']) ? ' · '.$item['variant_name'] : '' }}</p>
                </div>
                <div class="text-sm font-bold text-[#14532d] flex-shrink-0">৳{{ number_format($item['line_total'], 0) }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── Price details ────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-4 text-sm">
        <h2 class="text-base font-bold text-[#14532d] mb-3">মূল্য বিবরণ</h2>
        <div class="space-y-1.5 text-gray-600">
            <div class="flex justify-between"><span>পণ্যের মূল্য</span><span>৳{{ number_format($subtotal, 0) }}</span></div>
            @if($packaging > 0)
            <div class="flex justify-between"><span>প্যাকেজিং</span><span>৳{{ number_format($packaging, 0) }}</span></div>
            @endif
            <div class="flex justify-between">
                <span>ডেলিভারি চার্জ</span>
                <span>{{ $charge ? '৳'.number_format($charge['delivery_charge'], 0) : 'ঠিকানা নির্বাচনের পর' }}</span>
            </div>
            <div class="border-t border-gray-100 pt-2 flex justify-between font-bold text-gray-800 text-base">
                <span>মোট</span>
                <span>{{ $charge ? '৳'.number_format($subtotal + $packaging + $charge['delivery_charge'], 0) : '—' }}</span>
            </div>
        </div>
    </div>

    {{-- ── Continue ─────────────────────────────────────────────────── --}}
    @if($address && $charge)
    <a href="{{ route('checkout.payment') }}"
       class="block w-full text-center bg-[#14532d] hover:bg-[#166534] text-white font-bold py-3.5 rounded-xl transition-colors shadow">
        পেমেন্ট-এ যান →
    </a>
    @else
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800 text-center">
        পেমেন্টে যেতে প্রথমে ডেলিভারি ঠিকানা যোগ করুন।
    </div>
    @endif

    <a href="/#combo-builder" class="block text-center text-sm text-gray-400 hover:text-gray-600 mt-3">← কার্টে ফিরে যান</a>
</div>
@endsection

@section('scripts')
<script>
const BD_DIVISIONS = @json($bdDivisions);
const BD_DISTRICTS = @json($bdDistricts);
const BD_UPAZILAS  = @json($bdUpazilas);
const CHECKOUT_ZONES = @json($zonesForJs);
@php
    $oldAddr = [
        'division' => old('bd_division_id'),
        'district' => old('bd_district_id'),
        'upazila'  => old('bd_upazila_id'),
        'zone'     => old('delivery_zone_id'),
        'location' => old('delivery_location_id'),
    ];
@endphp
const OLD_ADDR = @json($oldAddr);

// Simple show/hide toggle (no Alpine dependency on this page)
function msToggle(id) {
    const el = document.getElementById(id);
    if (el) el.classList.toggle('hidden');
}

// Reveal the new-address form (from the card or the change panel) and scroll to it.
function msAddrNew() {
    const el = document.getElementById('addr-new');
    if (el) { el.classList.remove('hidden'); el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
}

(function () {
    const divSel = document.getElementById('ca-division');
    const disSel = document.getElementById('ca-district');
    const upaSel = document.getElementById('ca-upazila');
    if (!divSel) return; // no form on this page

    function fill(sel, rows, labelKey, placeholder) {
        sel.innerHTML = '<option value="">' + placeholder + '</option>';
        rows.forEach(r => {
            const o = document.createElement('option');
            o.value = r.id; o.textContent = r[labelKey];
            sel.appendChild(o);
        });
    }

    fill(divSel, BD_DIVISIONS, 'bn_name', 'বিভাগ বেছে নিন');

    divSel.addEventListener('change', function () {
        const rows = BD_DISTRICTS.filter(d => String(d.division_id) === String(this.value));
        fill(disSel, rows, 'bn_name', 'জেলা বেছে নিন');
        fill(upaSel, [], 'bn_name', 'উপজেলা বেছে নিন');
    });
    disSel.addEventListener('change', function () {
        const rows = BD_UPAZILAS.filter(u => String(u.district_id) === String(this.value));
        fill(upaSel, rows, 'bn_name', 'উপজেলা বেছে নিন');
    });

    // Zone → location
    const locWrap = document.getElementById('ca-location-wrap');
    const locSel  = document.getElementById('ca-location');
    document.querySelectorAll('.ca-zone').forEach(radio => {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.ca-zone-card').forEach(c => c.classList.remove('border-[#14532d]','bg-green-50/40'));
            const card = this.closest('label').querySelector('.ca-zone-card');
            if (card) card.classList.add('border-[#14532d]','bg-green-50/40');
            const zone = CHECKOUT_ZONES.find(z => String(z.id) === String(this.value));
            locSel.innerHTML = '<option value="">এলাকা বেছে নিন</option>';
            (zone ? zone.locations : []).forEach(l => {
                const o = document.createElement('option');
                o.value = l.id;
                o.textContent = l.location_name + (l.delivery_charge !== null ? ' (৳' + l.delivery_charge + ')' : '');
                locSel.appendChild(o);
            });
            locWrap.style.display = '';
        });
    });

    // Restore old() values after a validation error
    if (OLD_ADDR.division) {
        divSel.value = OLD_ADDR.division; divSel.dispatchEvent(new Event('change'));
        if (OLD_ADDR.district) { disSel.value = OLD_ADDR.district; disSel.dispatchEvent(new Event('change')); }
        if (OLD_ADDR.upazila) upaSel.value = OLD_ADDR.upazila;
    }
    if (OLD_ADDR.zone) {
        const zr = document.querySelector('.ca-zone[value="' + OLD_ADDR.zone + '"]');
        if (zr) { zr.checked = true; zr.dispatchEvent(new Event('change')); if (OLD_ADDR.location) locSel.value = OLD_ADDR.location; }
    }
})();
</script>
@endsection
