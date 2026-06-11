@extends('admin.layout')

@section('title', 'পেমেন্ট সেটিং')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">পেমেন্ট সেটিং</h1>
</div>

<div class="bg-white rounded shadow">
    <form action="{{ route('admin.payment-settings.update') }}" method="POST">
        @csrf

        {{-- Enabled Methods --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">পেমেন্ট পদ্ধতি সক্রিয়/নিষ্ক্রিয়</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

                <label class="flex items-center gap-3 cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="cash_on_delivery_enabled" value="1"
                           {{ $settings->cash_on_delivery_enabled ? 'checked' : '' }}
                           class="w-4 h-4 accent-gray-800">
                    <div>
                        <div class="text-sm font-semibold text-gray-700">ক্যাশ অন ডেলিভারি</div>
                        <div class="text-xs text-gray-400">Cash on Delivery</div>
                    </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="bkash_enabled" value="1"
                           {{ $settings->bkash_enabled ? 'checked' : '' }}
                           class="w-4 h-4 accent-gray-800">
                    <div>
                        <div class="text-sm font-semibold text-gray-700">বিকাশ</div>
                        <div class="text-xs text-gray-400">bKash</div>
                    </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="rocket_enabled" value="1"
                           {{ $settings->rocket_enabled ? 'checked' : '' }}
                           class="w-4 h-4 accent-gray-800">
                    <div>
                        <div class="text-sm font-semibold text-gray-700">রকেট</div>
                        <div class="text-xs text-gray-400">Rocket</div>
                    </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="nagad_enabled" value="1"
                           {{ $settings->nagad_enabled ? 'checked' : '' }}
                           class="w-4 h-4 accent-gray-800">
                    <div>
                        <div class="text-sm font-semibold text-gray-700">নগদ</div>
                        <div class="text-xs text-gray-400">Nagad (ঐচ্ছিক)</div>
                    </div>
                </label>

            </div>
        </div>

        {{-- Payment Numbers --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">পেমেন্ট নম্বর</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="bkash_number">বিকাশ নম্বর</label>
                    <input type="text" name="bkash_number" id="bkash_number"
                           value="{{ old('bkash_number', $settings->bkash_number) }}"
                           placeholder="01XXXXXXXXX"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    @error('bkash_number')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="rocket_number">রকেট নম্বর</label>
                    <input type="text" name="rocket_number" id="rocket_number"
                           value="{{ old('rocket_number', $settings->rocket_number) }}"
                           placeholder="01XXXXXXXXX"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    @error('rocket_number')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="nagad_number">নগদ নম্বর</label>
                    <input type="text" name="nagad_number" id="nagad_number"
                           value="{{ old('nagad_number', $settings->nagad_number) }}"
                           placeholder="01XXXXXXXXX (ঐচ্ছিক)"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    @error('nagad_number')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        {{-- Payment Instruction --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">পেমেন্ট নির্দেশনা</h3>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="payment_instruction">
                    নির্দেশনা টেক্সট
                    <span class="text-gray-400 font-normal">(গ্রাহককে দেখানো হবে, ঐচ্ছিক)</span>
                </label>
                <textarea name="payment_instruction" id="payment_instruction" rows="3"
                          placeholder="যেমন: পেমেন্ট পাঠানোর পর ট্রানজেকশন আইডি দিন। সেন্ড মানি করুন। মার্চেন্ট নয়।"
                          class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 resize-none">{{ old('payment_instruction', $settings->payment_instruction) }}</textarea>
                @error('payment_instruction')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Instant Payment offer (Meesho-style) --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">ইনস্ট্যান্ট পেমেন্ট অফার</h3>

            <label class="flex items-center gap-3 cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50 mb-4 max-w-md">
                <input type="checkbox" name="instant_payment_enabled" value="1"
                       {{ $settings->instant_payment_enabled ? 'checked' : '' }}
                       class="w-4 h-4 accent-gray-800">
                <div>
                    <div class="text-sm font-semibold text-gray-700">Instant Payment চালু</div>
                    <div class="text-xs text-gray-400">আগে পেমেন্ট করলে ছাড় + দ্রুত ডেলিভারি (বিকাশ/নগদ/রকেট চালু থাকতে হবে)</div>
                </div>
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">ডিসকাউন্ট টাইপ</label>
                    <select name="instant_discount_type"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                        <option value="percentage" {{ $settings->instant_discount_type === 'percentage' ? 'selected' : '' }}>শতাংশ (%)</option>
                        <option value="fixed" {{ $settings->instant_discount_type === 'fixed' ? 'selected' : '' }}>নির্দিষ্ট টাকা (৳)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">ডিসকাউন্ট ভ্যালু</label>
                    <input type="number" step="0.01" min="0" name="instant_discount_value"
                           value="{{ old('instant_discount_value', $settings->instant_discount_value) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">ন্যূনতম অর্ডার (ঐচ্ছিক)</label>
                    <input type="number" step="0.01" min="0" name="instant_min_order_amount"
                           value="{{ old('instant_min_order_amount', $settings->instant_min_order_amount) }}"
                           placeholder="ছাড় পেতে ন্যূনতম অর্ডার"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">COD ডেলিভারি (দিন)</label>
                    <input type="text" name="cod_delivery_days"
                           value="{{ old('cod_delivery_days', $settings->cod_delivery_days) }}"
                           placeholder="৫–৭"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Instant ডেলিভারি (দিন)</label>
                    <input type="text" name="instant_delivery_days"
                           value="{{ old('instant_delivery_days', $settings->instant_delivery_days) }}"
                           placeholder="২–৩"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                </div>
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
