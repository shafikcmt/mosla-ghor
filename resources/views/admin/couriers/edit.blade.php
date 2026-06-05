@extends('admin.layout')
@section('title', 'কুরিয়ার সম্পাদনা')

@section('content')
<div class="mb-5">
    <a href="{{ route('admin.couriers.index') }}" class="text-sm text-gray-500 hover:text-gray-800">← কুরিয়ার তালিকায় ফিরুন</a>
</div>

<div class="bg-white rounded shadow p-6 max-w-2xl">
    <h2 class="text-base font-bold text-gray-800 mb-1">কুরিয়ার সম্পাদনা: {{ $courier->name }}</h2>
    <p class="text-xs text-gray-400 mb-5">
        API Key / Secret / Base URL এখানে নেই —
        <a href="{{ route('admin.courier-api-settings.index') }}" class="text-indigo-600 hover:underline">কুরিয়ার API সেটিং</a>
        পেজ থেকে কনফিগার করুন।
    </p>

    <form method="POST" action="{{ route('admin.couriers.update', $courier) }}" class="space-y-4">
        @csrf @method('PUT')

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">নাম <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $courier->name) }}" required
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $courier->slug) }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">স্ট্যাটাস</label>
            <select name="status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none">
                <option value="active" {{ old('status',$courier->status)==='active'?'selected':'' }}>সক্রিয়</option>
                <option value="inactive" {{ old('status',$courier->status)==='inactive'?'selected':'' }}>নিষ্ক্রিয়</option>
            </select>
        </div>

        <div class="flex items-center gap-6">
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" name="vendor_allowed" value="1"
                       {{ old('vendor_allowed', $courier->vendor_allowed) ? 'checked' : '' }}
                       class="w-4 h-4 accent-[#14532d]">
                ভেন্ডরদের জন্য অনুমোদিত
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" name="is_default" value="1"
                       {{ old('is_default', $courier->is_default) ? 'checked' : '' }}
                       class="w-4 h-4 accent-[#14532d]">
                ডিফল্ট কুরিয়ার
            </label>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">নোট</label>
            <textarea name="notes" rows="3"
                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">{{ old('notes', $courier->notes) }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="bg-[#14532d] text-white text-sm px-5 py-2 rounded hover:bg-[#0d3520] transition-colors">
                আপডেট করুন
            </button>
            <a href="{{ route('admin.couriers.index') }}"
               class="text-sm text-gray-500 hover:text-gray-800 px-5 py-2 border border-gray-300 rounded">বাতিল</a>
        </div>
    </form>
</div>
@endsection
