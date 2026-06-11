{{-- Delivery address form (guest or new address). Expects $prefill, $activeZones.
     Cascade + zone/location behaviour is wired by the page script (see review.blade). --}}
<form action="{{ route('checkout.address.store') }}" method="POST" class="space-y-4" id="checkout-address-form">
    @csrf

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-[#14532d] text-xs font-semibold mb-1">নাম <span class="text-red-400">*</span></label>
            <input type="text" name="name" value="{{ old('name', $prefill['name'] ?? '') }}" required
                   class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
        </div>
        <div>
            <label class="block text-[#14532d] text-xs font-semibold mb-1">মোবাইল নম্বর <span class="text-red-400">*</span></label>
            <input type="tel" name="phone" value="{{ old('phone', $prefill['phone'] ?? '') }}" placeholder="01XXXXXXXXX" required
                   class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
            <label class="block text-[#14532d] text-xs font-semibold mb-1">বিভাগ <span class="text-red-400">*</span></label>
            <select name="bd_division_id" id="ca-division" required
                    class="w-full border border-green-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                <option value="">বিভাগ বেছে নিন</option>
            </select>
        </div>
        <div>
            <label class="block text-[#14532d] text-xs font-semibold mb-1">জেলা <span class="text-red-400">*</span></label>
            <select name="bd_district_id" id="ca-district" required
                    class="w-full border border-green-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                <option value="">জেলা বেছে নিন</option>
            </select>
        </div>
        <div>
            <label class="block text-[#14532d] text-xs font-semibold mb-1">উপজেলা <span class="text-red-400">*</span></label>
            <select name="bd_upazila_id" id="ca-upazila" required
                    class="w-full border border-green-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                <option value="">উপজেলা বেছে নিন</option>
            </select>
        </div>
    </div>

    <input type="hidden" name="bd_union_id" id="ca-union">

    <div>
        <label class="block text-[#14532d] text-xs font-semibold mb-1">বাড়ি / রোড / গ্রাম <span class="text-red-400">*</span></label>
        <textarea name="full_address" rows="2" required
                  placeholder="বাড়ি/ফ্ল্যাট নম্বর, রোড, মহল্লা/গ্রাম..."
                  class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[#14532d]">{{ old('full_address', $prefill['full_address'] ?? '') }}</textarea>
    </div>

    {{-- Delivery zone --}}
    <div>
        <label class="block text-[#14532d] text-xs font-semibold mb-2">ডেলিভারি জোন <span class="text-red-400">*</span></label>
        @if($activeZones->isEmpty())
            <p class="text-gray-400 text-sm">কোনো ডেলিভারি জোন পাওয়া যায়নি।</p>
        @else
        <div class="grid grid-cols-2 gap-2">
            @foreach($activeZones as $zone)
            <label class="block cursor-pointer">
                <input type="radio" name="delivery_zone_id" value="{{ $zone->id }}" class="sr-only ca-zone" required>
                <div class="ca-zone-card border-2 border-green-200 rounded-xl px-3 py-2.5 text-center bg-white hover:border-[#14532d] transition-colors">
                    <div class="text-sm font-bold text-[#14532d]">{{ $zone->zone_name }}</div>
                    <div class="text-xs text-gray-400">৳{{ number_format($zone->delivery_charge, 0) }}</div>
                </div>
            </label>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Location (filtered by zone) --}}
    <div id="ca-location-wrap" style="display:none">
        <label class="block text-[#14532d] text-xs font-semibold mb-1">এলাকা <span class="text-red-400">*</span></label>
        <select name="delivery_location_id" id="ca-location"
                class="w-full border border-green-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            <option value="">এলাকা বেছে নিন</option>
        </select>
    </div>

    <div class="flex gap-3 pt-1">
        <button type="submit" class="bg-[#14532d] hover:bg-[#166534] text-white font-bold px-6 py-2.5 rounded-xl text-sm transition-colors">
            ঠিকানা সংরক্ষণ করুন
        </button>
    </div>
</form>
