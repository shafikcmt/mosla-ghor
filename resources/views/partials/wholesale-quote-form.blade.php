{{--
    Shared quote submit form (vendor + admin). No admin-approval gate — the quote
    is sent straight to the customer.
    Required vars: $enquiry, $action (form action URL), $backUrl
--}}
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

            <form action="{{ $action }}" method="POST" class="space-y-5" id="quote-form">
                @csrf

                {{-- Quotation line table --}}
                <div class="overflow-x-auto border border-gray-100 rounded-xl">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="text-left px-3 py-2">পণ্য</th>
                                <th class="text-left px-3 py-2">চাহিদা</th>
                                <th class="text-left px-3 py-2">ইউনিট মূল্য (৳) *</th>
                                <th class="text-left px-3 py-2">পরিমাণ *</th>
                                <th class="text-right px-3 py-2">সাবটোটাল</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t border-gray-100">
                                <td class="px-3 py-3 font-semibold text-gray-800">{{ $enquiry->product_name }}</td>
                                <td class="px-3 py-3 text-gray-500">{{ rtrim(rtrim(number_format((float)$enquiry->quantity_kg,2),'0'),'.') }} {{ $enquiry->quantity_unit ?: 'kg' }}</td>
                                <td class="px-3 py-3">
                                    <input type="number" name="unit_price" value="{{ old('unit_price') }}" step="0.01" min="0.01" required
                                           class="w-28 border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                           placeholder="850.00">
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex gap-1.5">
                                        <input type="number" name="quantity" value="{{ old('quantity', $enquiry->quantity_kg) }}" step="0.01" min="0.5" required
                                               class="w-24 border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500">
                                        <select name="quantity_unit"
                                                class="border border-gray-200 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500">
                                            @foreach(['kg','gram','ton','piece','bag','carton','packet'] as $u)
                                            <option value="{{ $u }}" {{ old('quantity_unit', $enquiry->quantity_unit ?: 'kg')===$u ? 'selected' : '' }}>{{ $u }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-right font-bold text-gray-800" id="prev-subtotal">—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">ডেলিভারি চার্জ (৳)</label>
                        <input type="number" name="delivery_charge" value="{{ old('delivery_charge', 0) }}" step="0.01" min="0"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white"
                               placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">অগ্রিম (%) — ঐচ্ছিক</label>
                        <input type="number" name="advance_percentage" value="{{ old('advance_percentage') }}" step="0.01" min="0" max="100"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white"
                               placeholder="যেমন: 30">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">ডেলিভারি সময়</label>
                        <input type="text" name="delivery_time" value="{{ old('delivery_time') }}" maxlength="100"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white"
                               placeholder="যেমন: ৩-৫ দিন">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">বৈধতা (দিন)</label>
                        <input type="number" name="validity_days" value="{{ old('validity_days', 7) }}" min="1" max="365"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">পেমেন্ট শর্ত</label>
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
                              placeholder="পণ্যের মান, প্যাকেজিং ইত্যাদি লিখুন...">{{ old('note') }}</textarea>
                </div>

                {{-- Live total preview --}}
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm">
                    <p class="font-semibold text-amber-800 mb-2">কোটেশন সারসংক্ষেপ (প্রিভিউ)</p>
                    <div class="grid grid-cols-2 gap-2 text-amber-700">
                        <span>সাবটোটাল:</span><span id="sum-subtotal" class="font-bold text-right">—</span>
                        <span>ডেলিভারি চার্জ:</span><span id="sum-delivery" class="font-bold text-right">—</span>
                        <span class="font-semibold">মোট:</span><span id="sum-total" class="font-bold text-lg text-right">—</span>
                        <span>অগ্রিম পরিমাণ:</span><span id="sum-advance" class="font-bold text-right">—</span>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-8 py-3 rounded-xl text-sm transition-colors shadow">
                        কোটেশন পাঠান →
                    </button>
                    <a href="{{ $backUrl }}"
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
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">চাহিদা</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ rtrim(rtrim(number_format((float)$enquiry->quantity_kg,2),'0'),'.') }} {{ $enquiry->quantity_unit ?: 'kg' }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">ডেলিভারি লোকেশন</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $enquiry->delivery_location }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">ব্যবসার ধরন</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $enquiry->businessTypeLabel() }}</dd></div>
                @if($enquiry->message)
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">বার্তা</dt><dd class="text-gray-600 mt-0.5">{{ $enquiry->message }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-2xl p-4 text-xs text-green-800 leading-relaxed">
            কোটেশন পাঠালে Customer সরাসরি দেখতে পাবে — কোনো admin approval লাগবে না। সঠিক মূল্য ও শর্ত দিন।
        </div>
    </div>
</div>

<script>
(function () {
    function n(sel) { return parseFloat(document.querySelector(sel)?.value) || 0; }
    function fmt(v) { return '৳' + v.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}); }
    function recalc() {
        var price = n('[name="unit_price"]'), qty = n('[name="quantity"]'),
            del = n('[name="delivery_charge"]'), advPct = n('[name="advance_percentage"]');
        var sub = price * qty, total = sub + del, adv = advPct > 0 ? total * advPct / 100 : 0;
        document.getElementById('prev-subtotal').textContent = sub > 0 ? fmt(sub) : '—';
        document.getElementById('sum-subtotal').textContent  = sub > 0 ? fmt(sub) : '—';
        document.getElementById('sum-delivery').textContent  = fmt(del);
        document.getElementById('sum-total').textContent     = total > 0 ? fmt(total) : '—';
        document.getElementById('sum-advance').textContent   = adv > 0 ? fmt(adv) : '—';
    }
    document.querySelectorAll('[name="unit_price"],[name="quantity"],[name="delivery_charge"],[name="advance_percentage"]')
        .forEach(function (el) { el.addEventListener('input', recalc); });
    recalc();
})();
</script>
