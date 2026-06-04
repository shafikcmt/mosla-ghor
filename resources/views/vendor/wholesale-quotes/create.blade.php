@extends('vendor.layout')
@section('title', 'কোটেশন পাঠান')

@section('content')
<div class="mb-5 flex items-center gap-3">
    <a href="{{ route('vendor.wholesale.enquiry.show', $enquiry->id) }}" class="text-gray-500 hover:text-gray-700 text-sm">← Enquiry বিস্তারিত</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Quote form --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-5">Enquiry #{{ $enquiry->id }} — কোটেশন পাঠান</h2>

            @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('vendor.wholesale.quote.store', $enquiry->id) }}" method="POST" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">প্রতি kg মূল্য (৳) <span class="text-red-500">*</span></label>
                        <input type="number" name="unit_price" value="{{ old('unit_price') }}" step="0.01" min="0.01" required
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white"
                               placeholder="যেমন: 850.00">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">পরিমাণ <span class="text-red-500">*</span></label>
                        <div class="flex gap-2">
                            <input type="number" name="quantity" value="{{ old('quantity', $enquiry->quantity_kg) }}" step="0.01" min="0.01" required
                                   class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                            <select name="quantity_unit"
                                    class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                                <option value="kg" {{ old('quantity_unit','kg')==='kg' ? 'selected' : '' }}>kg</option>
                                <option value="ton" {{ old('quantity_unit')==='ton' ? 'selected' : '' }}>ton</option>
                                <option value="piece" {{ old('quantity_unit')==='piece' ? 'selected' : '' }}>piece</option>
                                <option value="bag" {{ old('quantity_unit')==='bag' ? 'selected' : '' }}>bag</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">ডেলিভারি চার্জ (৳)</label>
                        <input type="number" name="delivery_charge" value="{{ old('delivery_charge', 0) }}" step="0.01" min="0"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white"
                               placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">অগ্রিম প্রয়োজন (৳)</label>
                        <input type="number" name="advance_required" value="{{ old('advance_required', 0) }}" step="0.01" min="0"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white"
                               placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">কোটেশন বৈধতা</label>
                        <input type="date" name="valid_until" value="{{ old('valid_until', now()->addDays(7)->format('Y-m-d')) }}"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">পেমেন্ট অপশন</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach(['online' => 'অনলাইন পেমেন্ট', 'cod' => 'COD (ক্যাশ অন ডেলিভারি)', 'partial' => 'আংশিক অগ্রিম', 'manual' => 'ম্যানুয়াল ট্রান্সফার'] as $val => $lbl)
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" name="payment_options[]" value="{{ $val }}"
                                   {{ is_array(old('payment_options')) && in_array($val, old('payment_options')) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            {{ $lbl }}
                        </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">নোট / বিশেষ শর্ত</label>
                    <textarea name="note" rows="3"
                              class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white resize-none"
                              placeholder="পণ্যের মান, প্যাকেজিং, ডেলিভারি সময় ইত্যাদি লিখুন...">{{ old('note') }}</textarea>
                </div>

                {{-- Live subtotal preview --}}
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm" id="quote-preview">
                    <p class="font-semibold text-amber-800 mb-2">কোটেশন সারসংক্ষেপ (প্রিভিউ)</p>
                    <div class="grid grid-cols-2 gap-2 text-amber-700">
                        <span>সাবটোটাল:</span><span id="prev-subtotal" class="font-bold">—</span>
                        <span>ডেলিভারি চার্জ:</span><span id="prev-delivery" class="font-bold">—</span>
                        <span class="font-semibold">মোট:</span><span id="prev-total" class="font-bold text-lg">—</span>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-8 py-3 rounded-xl text-sm transition-colors shadow">
                        কোটেশন পাঠান →
                    </button>
                    <a href="{{ route('vendor.wholesale.enquiry.show', $enquiry->id) }}"
                       class="border border-gray-200 text-gray-600 hover:bg-gray-50 font-semibold px-6 py-3 rounded-xl text-sm transition-colors">
                        বাতিল
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Enquiry summary sidebar --}}
    <div class="space-y-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h3 class="text-sm font-bold text-gray-700 mb-3">Enquiry তথ্য</h3>
            <dl class="space-y-2.5 text-sm">
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">পণ্য</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $enquiry->product_name }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">চাহিদা</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $enquiry->quantity_kg }} kg</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">ডেলিভারি লোকেশন</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $enquiry->delivery_location }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">ব্যবসার ধরন</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $enquiry->businessTypeLabel() }}</dd></div>
                @if($enquiry->message)
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">বার্তা</dt><dd class="text-gray-600 mt-0.5">{{ $enquiry->message }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 text-xs text-indigo-800 leading-relaxed">
            Admin অনুমোদনের পরে Customer কোটেশন দেখতে পাবেন। সঠিক মূল্য ও শর্ত দিন।
        </div>
    </div>
</div>

<script>
function updatePreview() {
    var price = parseFloat(document.querySelector('[name="unit_price"]').value) || 0;
    var qty   = parseFloat(document.querySelector('[name="quantity"]').value) || 0;
    var del   = parseFloat(document.querySelector('[name="delivery_charge"]').value) || 0;
    var sub   = price * qty;
    var total = sub + del;
    document.getElementById('prev-subtotal').textContent = sub > 0 ? '৳' + sub.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
    document.getElementById('prev-delivery').textContent = '৳' + del.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('prev-total').textContent = total > 0 ? '৳' + total.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
}
document.querySelectorAll('[name="unit_price"],[name="quantity"],[name="delivery_charge"]').forEach(function(el){
    el.addEventListener('input', updatePreview);
});
updatePreview();
</script>
@endsection
