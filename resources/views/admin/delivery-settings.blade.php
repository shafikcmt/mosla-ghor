@extends('admin.layout')

@section('title', 'ডেলিভারি সেটিং')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">ডেলিভারি সেটিং</h1>
</div>

<div class="bg-white rounded shadow">
    <form action="{{ route('admin.delivery-settings.update') }}" method="POST">
        @csrf

        {{-- Charges --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">ডেলিভারি চার্জ</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="inside_dhaka_charge">
                        ঢাকার ভেতরে চার্জ (৳)
                    </label>
                    <input type="number" name="inside_dhaka_charge" id="inside_dhaka_charge"
                           value="{{ old('inside_dhaka_charge', $settings->inside_dhaka_charge) }}"
                           min="0" step="1"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    @error('inside_dhaka_charge')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="outside_dhaka_charge">
                        ঢাকার বাইরে চার্জ (৳)
                    </label>
                    <input type="number" name="outside_dhaka_charge" id="outside_dhaka_charge"
                           value="{{ old('outside_dhaka_charge', $settings->outside_dhaka_charge) }}"
                           min="0" step="1"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    @error('outside_dhaka_charge')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        {{-- Free Delivery --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">ফ্রি ডেলিভারি</h3>

            <label class="flex items-center gap-3 cursor-pointer mb-4">
                <input type="checkbox" name="enable_free_delivery" value="1"
                       {{ $settings->enable_free_delivery ? 'checked' : '' }}
                       class="w-4 h-4 accent-gray-800">
                <div>
                    <div class="text-sm font-semibold text-gray-700">ফ্রি ডেলিভারি সক্রিয় করুন</div>
                    <div class="text-xs text-gray-400">নির্দিষ্ট পরিমাণের উপরে অর্ডারে ডেলিভারি ফ্রি হবে</div>
                </div>
            </label>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="free_delivery_minimum_amount">
                    ফ্রি ডেলিভারির ন্যূনতম পরিমাণ (৳)
                    <span class="text-gray-400 font-normal">(ঐচ্ছিক — সাবটোটাল এই পরিমাণ বা বেশি হলে ফ্রি)</span>
                </label>
                <input type="number" name="free_delivery_minimum_amount" id="free_delivery_minimum_amount"
                       value="{{ old('free_delivery_minimum_amount', $settings->free_delivery_minimum_amount) }}"
                       min="0" step="1" placeholder="যেমন: 1000"
                       class="w-full sm:w-64 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                @error('free_delivery_minimum_amount')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Delivery Note --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">ডেলিভারি নির্দেশনা</h3>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="delivery_note">
                    নির্দেশনা টেক্সট
                    <span class="text-gray-400 font-normal">(অর্ডার ফর্মে গ্রাহককে দেখানো হবে, ঐচ্ছিক)</span>
                </label>
                <textarea name="delivery_note" id="delivery_note" rows="3"
                          placeholder="যেমন: ঢাকার ভেতরে ২-৩ দিনে, ঢাকার বাইরে ৩-৫ দিনে ডেলিভারি দেওয়া হয়।"
                          class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 resize-none">{{ old('delivery_note', $settings->delivery_note) }}</textarea>
                @error('delivery_note')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Submit --}}
        <div class="px-6 py-5">
            <button type="submit"
                    class="bg-gray-800 text-white px-6 py-2.5 rounded text-sm font-semibold hover:bg-gray-700 transition-colors">
                সেটিং সংরক্ষণ করুন
            </button>
        </div>

    </form>
</div>

@endsection
