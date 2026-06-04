@extends('vendor.layouts.app')

@section('title', 'Quote তৈরি করুন — Enquiry #' . $enquiry->id)

@section('content')
<div class="px-4 sm:px-6 py-8 max-w-3xl mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('vendor.paykari-combo.show', $enquiry) }}" class="text-gray-400 hover:text-gray-600 text-sm">← Enquiry</a>
        <span class="text-gray-300">/</span>
        <span class="font-semibold text-gray-700">Quote তৈরি করুন</span>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6 text-sm text-amber-800">
        <strong>Enquiry #{{ $enquiry->id }}</strong> —
        {{ $enquiry->delivery_location }} · {{ $enquiry->businessTypeLabel() }}
        @if($enquiry->message)
        <br><span class="text-amber-600 text-xs mt-1 block">{{ $enquiry->message }}</span>
        @endif
    </div>

    <form action="{{ route('vendor.paykari-combo.quote.store', $enquiry) }}" method="POST">
        @csrf

        {{-- Item-wise quote --}}
        <div class="bg-white rounded-2xl border border-amber-100 p-5 mb-5">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">প্রতিটি পণ্যের Quote</h3>

            <div class="space-y-4" id="quote-items">
                @foreach($enquiry->items as $i => $item)
                <div class="bg-amber-50 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="font-serif-bn font-bold text-[#14532d] text-sm">{{ $item->product_name }}</span>
                            <span class="text-amber-700 text-xs ml-2">{{ $item->quantity_kg }} kg requested</span>
                        </div>
                    </div>
                    <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $item->product_id }}">
                    <input type="hidden" name="items[{{ $i }}][product_name]" value="{{ $item->product_name }}">

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Unit Price (৳/kg) <span class="text-red-400">*</span>
                            </label>
                            <input type="number" name="items[{{ $i }}][unit_price]"
                                   min="0" step="0.01"
                                   value="{{ old('items.' . $i . '.unit_price') }}"
                                   placeholder="0.00"
                                   class="w-full border border-amber-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white"
                                   oninput="updateSubtotal({{ $i }}, {{ $item->quantity_kg }}, this.value)">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Quantity (kg)
                            </label>
                            <input type="number" name="items[{{ $i }}][quantity_kg]"
                                   min="0" step="0.01"
                                   value="{{ $item->quantity_kg }}"
                                   class="w-full border border-amber-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Subtotal</label>
                            <div id="subtotal-{{ $i }}"
                                 class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50 text-[#14532d] font-bold">
                                ৳০
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Delivery + Payment --}}
        <div class="bg-white rounded-2xl border border-amber-100 p-5 mb-5">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Delivery ও Payment</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Delivery Charge (৳) <span class="text-red-400">*</span>
                    </label>
                    <input type="number" name="delivery_charge"
                           min="0" step="1" value="{{ old('delivery_charge', 0) }}"
                           class="w-full border border-amber-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Valid Until</label>
                    <input type="date" name="valid_until"
                           value="{{ old('valid_until', \Carbon\Carbon::today()->addDays(7)->format('Y-m-d')) }}"
                           min="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}"
                           class="w-full border border-amber-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                </div>
            </div>

            {{-- Advance --}}
            <div class="mt-4">
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="advance_required" value="1"
                           id="advance-check" onchange="toggleAdvance(this)"
                           {{ old('advance_required') ? 'checked' : '' }}
                           class="rounded text-amber-600">
                    <span>Advance Payment প্রয়োজন</span>
                </label>
                <div id="advance-amount-wrap" class="{{ old('advance_required') ? '' : 'hidden' }} mt-2">
                    <input type="number" name="advance_amount"
                           min="0" step="1" placeholder="Advance amount (৳)"
                           value="{{ old('advance_amount') }}"
                           class="w-full border border-amber-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                </div>
            </div>

            {{-- Payment Options --}}
            <div class="mt-4">
                <label class="block text-xs font-semibold text-gray-600 mb-2">Payment Options</label>
                <div class="flex flex-wrap gap-3">
                    @foreach(['cod' => 'Cash on Delivery', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'Bank Transfer', 'rocket' => 'Rocket'] as $val => $label)
                    <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                        <input type="checkbox" name="payment_options[]" value="{{ $val }}"
                               {{ in_array($val, old('payment_options', [])) ? 'checked' : '' }}
                               class="rounded text-amber-600">
                        {{ $label }}
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Note --}}
        <div class="bg-white rounded-2xl border border-amber-100 p-5 mb-5">
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                Note / Message (ঐচ্ছিক)
            </label>
            <textarea name="note" rows="3"
                      placeholder="Delivery সময়, stock availability, বিশেষ শর্ত..."
                      class="w-full border border-amber-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white resize-none">{{ old('note') }}</textarea>
        </div>

        {{-- Grand Total preview --}}
        <div class="bg-[#14532d] rounded-2xl p-5 mb-5">
            <div class="flex justify-between text-green-300 text-sm mb-1">
                <span>Item Total</span>
                <span id="preview-item-total">৳০</span>
            </div>
            <div class="flex justify-between text-green-300 text-sm">
                <span>Delivery Charge</span>
                <span>—</span>
            </div>
            <div class="flex justify-between font-bold text-[#c9a227] text-lg font-serif-bn mt-2 pt-2 border-t border-green-800">
                <span>Quote Grand Total</span>
                <span id="preview-grand-total">৳০</span>
            </div>
            <p class="text-green-600 text-xs mt-2">Admin approve করার পরে customer quote দেখতে পাবে।</p>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="flex-1 bg-amber-600 hover:bg-amber-700 text-white font-bold py-3 rounded-xl text-sm transition-colors">
                Quote পাঠান →
            </button>
            <a href="{{ route('vendor.paykari-combo.show', $enquiry) }}"
               class="px-5 py-3 border border-gray-200 text-gray-600 hover:bg-gray-50 font-semibold rounded-xl text-sm transition-colors">
                বাতিল
            </a>
        </div>

    </form>
</div>

<script>
const subtotals = {};

function updateSubtotal(index, qty, unitPrice) {
    const price = parseFloat(unitPrice) || 0;
    const q     = parseFloat(qty) || 0;
    const sub   = price * q;
    subtotals[index] = sub;

    const el = document.getElementById('subtotal-' + index);
    if (el) el.textContent = '৳' + sub.toLocaleString('bn-BD', {minimumFractionDigits: 0, maximumFractionDigits: 0});

    updatePreviewTotal();
}

function updatePreviewTotal() {
    const total = Object.values(subtotals).reduce((s, v) => s + v, 0);
    const el = document.getElementById('preview-item-total');
    const grandEl = document.getElementById('preview-grand-total');
    if (el) el.textContent = '৳' + Math.round(total).toLocaleString();
    if (grandEl) grandEl.textContent = '৳' + Math.round(total).toLocaleString() + '+';
}

function toggleAdvance(cb) {
    document.getElementById('advance-amount-wrap').classList.toggle('hidden', !cb.checked);
}
</script>
@endsection
