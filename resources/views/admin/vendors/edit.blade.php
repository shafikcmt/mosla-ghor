@extends('admin.layout')
@section('title', 'ভেন্ডর সম্পাদনা')

@section('content')
<div class="mb-5">
    <a href="{{ route('admin.vendors.show', $vendor) }}" class="text-sm text-gray-500 hover:text-gray-800">← {{ $vendor->shop_name }}</a>
    <h2 class="text-xl font-bold text-gray-800 mt-1">ভেন্ডর সম্পাদনা</h2>
</div>

<form method="POST" action="{{ route('admin.vendors.update', $vendor) }}" class="max-w-3xl space-y-5">
    @csrf @method('PUT')

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">ব্যবসা ও যোগাযোগ</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ব্যবসা / দোকানের নাম <span class="text-red-500">*</span></label>
                <input type="text" name="shop_name" value="{{ old('shop_name', $vendor->shop_name) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">মালিকের নাম <span class="text-red-500">*</span></label>
                <input type="text" name="owner_name" value="{{ old('owner_name', $vendor->owner_name) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ফোন <span class="text-red-500">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', $vendor->phone) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ইমেইল <span class="text-gray-400">(login)</span></label>
                <input type="email" name="email" value="{{ old('email', $vendor->email) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ব্যবসার ধরন</label>
                <select name="business_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">— নির্বাচন করুন —</option>
                    @foreach($businessTypes as $bt)
                    <option value="{{ $bt }}" {{ old('business_type', $vendor->business_type) === $bt ? 'selected' : '' }}>{{ $bt }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ট্রেড লাইসেন্স</label>
                <input type="text" name="trade_license" value="{{ old('trade_license', $vendor->trade_license) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">ঠিকানা</label>
                <input type="text" name="address" value="{{ old('address', $vendor->address) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">জেলা</label>
                <input type="text" name="district" value="{{ old('district', $vendor->district) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">শহর / এলাকা</label>
                <input type="text" name="city" value="{{ old('city', $vendor->city) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">NID</label>
                <input type="text" name="nid" value="{{ old('nid', $vendor->nid) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">স্ট্যাটাস ও কমিশন</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">স্ট্যাটাস <span class="text-red-500">*</span></label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
                    @foreach(['pending' => 'পেন্ডিং', 'approved' => 'অনুমোদিত', 'suspended' => 'স্থগিত', 'rejected' => 'প্রত্যাখ্যাত'] as $val => $label)
                    <option value="{{ $val }}" {{ old('status', $vendor->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">কমিশন ধরন</label>
                <select name="commission_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">ডিফল্ট</option>
                    <option value="percentage" {{ old('commission_type', $vendor->commission_type) === 'percentage' ? 'selected' : '' }}>শতাংশ (%)</option>
                    <option value="fixed" {{ old('commission_type', $vendor->commission_type) === 'fixed' ? 'selected' : '' }}>নির্দিষ্ট (৳)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">কমিশন মান</label>
                <input type="number" name="commission_value" value="{{ old('commission_value', $vendor->commission_value) }}" step="0.01" min="0"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-700 mt-4 cursor-pointer">
            <input type="checkbox" name="product_auto_approve" value="1" {{ old('product_auto_approve', $vendor->product_auto_approve) ? 'checked' : '' }} class="w-4 h-4 accent-[#14532d]">
            পণ্য অটো অনুমোদন
        </label>
        <div class="mt-4">
            <label class="block text-xs font-medium text-gray-600 mb-1">অ্যাডমিন নোট</label>
            <textarea name="admin_note" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">{{ old('admin_note', $vendor->admin_note) }}</textarea>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-[#14532d] text-white text-sm font-medium px-6 py-2.5 rounded-lg hover:bg-[#0d3520] transition-colors">আপডেট করুন</button>
        <a href="{{ route('admin.vendors.show', $vendor) }}" class="text-sm text-gray-500 px-6 py-2.5 border border-gray-300 rounded-lg">বাতিল</a>
    </div>
</form>
@endsection
