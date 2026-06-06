@php $customer = $customer ?? null; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">নাম <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $customer?->name) }}" required
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">ফোন <span class="text-red-500">*</span></label>
        <input type="text" name="phone" value="{{ old('phone', $customer?->phone) }}" required
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp নম্বর</label>
        <input type="text" name="whatsapp" value="{{ old('whatsapp', $customer?->whatsapp) }}"
               placeholder="ফাঁকা রাখলে ফোন নম্বর ব্যবহার হবে"
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">ইমেইল</label>
        <input type="email" name="email" value="{{ old('email', $customer?->email) }}"
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">কাস্টমার ধরন</label>
        @php $curType = old('customer_type', $customer?->customer_type ?? 'Regular'); @endphp
        <select name="customer_type" class="w-full border rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-indigo-400">
            @foreach(\App\Models\VendorCustomer::CUSTOMER_TYPES as $t)
                <option value="{{ $t }}" {{ $curType === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">স্ট্যাটাস</label>
        @php $curStatus = old('status', $customer?->status ?? 'active'); @endphp
        <select name="status" class="w-full border rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-indigo-400">
            <option value="active" {{ $curStatus === 'active' ? 'selected' : '' }}>সক্রিয়</option>
            <option value="inactive" {{ $curStatus === 'inactive' ? 'selected' : '' }}>নিষ্ক্রিয়</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">জেলা</label>
        <input type="text" name="district" value="{{ old('district', $customer?->district) }}"
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">এলাকা</label>
        <input type="text" name="area" value="{{ old('area', $customer?->area) }}"
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">ঠিকানা</label>
        <input type="text" name="address" value="{{ old('address', $customer?->address) }}"
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">নোট</label>
        <textarea name="notes" rows="2"
                  class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">{{ old('notes', $customer?->notes) }}</textarea>
    </div>
</div>

<div class="flex items-center gap-3 pt-5">
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm font-medium">সংরক্ষণ</button>
    <a href="{{ route('vendor.customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">বাতিল</a>
</div>
