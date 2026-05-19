@extends('admin.layout')

@section('title', 'জেনারেল সেটিং')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">জেনারেল সেটিং</h1>
</div>

<div class="bg-white rounded shadow">
    <form action="{{ route('admin.general-settings.update') }}" method="POST">
        @csrf

        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">অর্ডার সেটিং</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="minimum_order_amount">
                        ন্যূনতম অর্ডার পরিমাণ (৳)
                        <span class="text-gray-400 font-normal">(গ্রাহকের কার্টের মোট এর চেয়ে কম হলে অর্ডার দেওয়া যাবে না)</span>
                    </label>
                    <input type="number" name="minimum_order_amount" id="minimum_order_amount"
                           value="{{ old('minimum_order_amount', $settings->minimum_order_amount) }}"
                           min="0" step="1"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    @error('minimum_order_amount')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="default_packaging_cost">
                        প্যাকেজিং চার্জ (৳)
                        <span class="text-gray-400 font-normal">(প্রতিটি অর্ডারে যোগ হয়)</span>
                    </label>
                    <input type="number" name="default_packaging_cost" id="default_packaging_cost"
                           value="{{ old('default_packaging_cost', $settings->default_packaging_cost) }}"
                           min="0" step="1"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    @error('default_packaging_cost')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        <div class="px-6 py-5">
            <button type="submit"
                    class="bg-gray-800 text-white px-6 py-2.5 rounded text-sm font-semibold hover:bg-gray-700 transition-colors">
                সেটিং সংরক্ষণ করুন
            </button>
        </div>

    </form>
</div>

@endsection
