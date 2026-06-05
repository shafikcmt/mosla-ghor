{{-- Shared admin pickup-point fields. Expects $vendors, optional $pickupPoint, optional $selectedVendorId. --}}
@php $pp = $pickupPoint ?? null; @endphp

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">ভেন্ডর <span class="text-red-500">*</span></label>
    @if($pp)
        {{-- Vendor is fixed on edit. --}}
        <input type="text" value="{{ $pp->vendor?->shop_name }}" readonly
               class="w-full border border-gray-200 bg-gray-100 rounded px-3 py-2 text-sm text-gray-600">
    @else
        <select name="vendor_id" required
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
            <option value="">— ভেন্ডর নির্বাচন করুন —</option>
            @foreach($vendors as $v)
            <option value="{{ $v->id }}" {{ (string)old('vendor_id', $selectedVendorId ?? '') === (string)$v->id ? 'selected' : '' }}>{{ $v->shop_name }}</option>
            @endforeach
        </select>
    @endif
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">পিকআপ নাম <span class="text-red-500">*</span></label>
        <input type="text" name="pickup_name" value="{{ old('pickup_name', $pp->pickup_name ?? '') }}" required
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">যোগাযোগ ব্যক্তির নাম <span class="text-red-500">*</span></label>
        <input type="text" name="contact_person_name" value="{{ old('contact_person_name', $pp->contact_person_name ?? '') }}" required
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">ফোন <span class="text-red-500">*</span></label>
        <input type="text" name="phone" value="{{ old('phone', $pp->phone ?? '') }}" required
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">বিকল্প ফোন</label>
        <input type="text" name="alternate_phone" value="{{ old('alternate_phone', $pp->alternate_phone ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">ঠিকানা <span class="text-red-500">*</span></label>
    <input type="text" name="address" value="{{ old('address', $pp->address ?? '') }}" required
           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">জেলা <span class="text-red-500">*</span></label>
        <input type="text" name="district" value="{{ old('district', $pp->district ?? '') }}" required
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">শহর / থানা <span class="text-red-500">*</span></label>
        <input type="text" name="city" value="{{ old('city', $pp->city ?? '') }}" required
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">এলাকা / জোন</label>
        <input type="text" name="zone_area" value="{{ old('zone_area', $pp->zone_area ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">পোস্টাল কোড</label>
        <input type="text" name="postal_code" value="{{ old('postal_code', $pp->postal_code ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">নোট</label>
    <textarea name="note" rows="2"
              class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">{{ old('note', $pp->note ?? '') }}</textarea>
</div>

<div class="flex items-center gap-6">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">স্ট্যাটাস</label>
        <select name="status" class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none">
            <option value="active" {{ old('status', $pp->status ?? 'active')==='active'?'selected':'' }}>সক্রিয়</option>
            <option value="inactive" {{ old('status', $pp->status ?? '')==='inactive'?'selected':'' }}>নিষ্ক্রিয়</option>
        </select>
    </div>
    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer mt-5">
        <input type="checkbox" name="is_default" value="1" {{ old('is_default', $pp->is_default ?? false) ? 'checked' : '' }}
               class="w-4 h-4 accent-[#14532d]">
        ডিফল্ট পিকআপ পয়েন্ট হিসেবে সেট করুন
    </label>
</div>
