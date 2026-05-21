@extends('customer.layout')
@section('title', $address ? 'ঠিকানা সম্পাদনা' : 'নতুন ঠিকানা')

@section('content')
<div class="max-w-lg">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('customer.addresses.index') }}" class="text-sm text-gray-500 hover:text-[#14532d]">← ঠিকানা তালিকা</a>
    </div>

    <h1 class="text-xl font-bold text-gray-800 mb-5">{{ $address ? 'ঠিকানা সম্পাদনা' : 'নতুন ঠিকানা' }}</h1>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <form method="POST"
              action="{{ $address ? route('customer.addresses.update', $address->id) : route('customer.addresses.store') }}"
              class="space-y-4">
            @csrf
            @if($address) @method('PUT') @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">লেবেল <span class="text-red-500">*</span></label>
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
                    <input type="text" name="phone" value="{{ old('phone', $address?->phone) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">বিভাগ</label>
                    <input type="text" name="division_name" value="{{ old('division_name', $address?->division_name) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">জেলা</label>
                    <input type="text" name="district_name" value="{{ old('district_name', $address?->district_name) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">উপজেলা</label>
                    <input type="text" name="upazila_name" value="{{ old('upazila_name', $address?->upazila_name) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ইউনিয়ন</label>
                    <input type="text" name="union_name" value="{{ old('union_name', $address?->union_name) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">পূর্ণ ঠিকানা <span class="text-red-500">*</span></label>
                <textarea name="full_address" required rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">{{ old('full_address', $address?->full_address) }}</textarea>
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
