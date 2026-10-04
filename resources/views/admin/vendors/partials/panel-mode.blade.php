{{-- Panel mode picker shared by the vendor create + edit forms. --}}
@php $currentMode = old('panel_mode', $mode ?? 'full'); @endphp
<div class="bg-white rounded-xl border border-gray-100 p-5 mb-5">
    <h2 class="text-sm font-bold text-gray-700 mb-1">প্যানেল মোড</h2>
    <p class="text-xs text-gray-400 mb-3">যেসব ভেন্ডর শুধু স্টকের হিসাব রাখতে চান, তাদের জন্য "শুধু স্টক" বেছে নিন — তারা লগইন করলে শুধু স্টক সম্পর্কিত অপশন দেখবেন।</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="flex items-start gap-3 border rounded-lg p-3 cursor-pointer has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
            <input type="radio" name="panel_mode" value="full" {{ $currentMode === 'full' ? 'checked' : '' }} class="mt-1 accent-[#14532d]">
            <span>
                <span class="block text-sm font-semibold text-gray-800">সম্পূর্ণ প্যানেল</span>
                <span class="block text-xs text-gray-500">পণ্য, অর্ডার, POS, পাইকারি, পেআউট — সব অপশন।</span>
            </span>
        </label>
        <label class="flex items-start gap-3 border rounded-lg p-3 cursor-pointer has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
            <input type="radio" name="panel_mode" value="stock_only" {{ $currentMode === 'stock_only' ? 'checked' : '' }} class="mt-1 accent-[#14532d]">
            <span>
                <span class="block text-sm font-semibold text-gray-800">📦 শুধু স্টক ম্যানেজমেন্ট</span>
                <span class="block text-xs text-gray-500">স্টক ইন/আউট, স্টক গণনা, কম-স্টক সতর্কতা ও হিস্ট্রি — অন্য কিছু দেখাবে না।</span>
            </span>
        </label>
    </div>
</div>
