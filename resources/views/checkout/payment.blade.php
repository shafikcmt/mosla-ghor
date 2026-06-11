@extends('storefront.layout')
@section('title', 'পেমেন্ট মেথড')

@section('content')
@php
    $codTotal     = $subtotal + $packaging + $deliveryCharge;
    $instantTotal = max(0, $codTotal - $instantDiscount);
    $codEnabled   = $settings->cash_on_delivery_enabled;
    $instantOk    = $settings->instantAvailable() && $instantDiscount >= 0;
    $manualMethods = $settings->manualMethods();
    $methodLabels  = ['bkash' => 'বিকাশ', 'nagad' => 'নগদ', 'rocket' => 'রকেট'];
@endphp
<div class="max-w-3xl mx-auto px-4 py-6">

    @include('checkout.partials.steps', ['active' => 'payment'])

    <div id="checkout-error" class="hidden mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700"></div>

    {{-- Address + items summary (collapsible) --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4 text-sm">
        <button type="button" onclick="document.getElementById('sum-body').classList.toggle('hidden')"
                class="w-full flex items-center justify-between">
            <span class="font-bold text-[#14532d]">ঠিকানা ও পণ্য</span>
            <span class="text-xs text-gray-400">বিস্তারিত ▾</span>
        </button>
        <div id="sum-body" class="hidden mt-3 space-y-2">
            <div class="text-gray-700">
                <span class="font-semibold">{{ $address->name }}</span> · {{ $address->phone }}<br>
                <span class="text-gray-500 text-xs">{{ $address->full_address }} — {{ $address->regionLine() }}</span>
            </div>
            <div class="border-t border-gray-50 pt-2 space-y-1">
                @foreach($items as $item)
                <div class="flex justify-between text-xs text-gray-600">
                    <span class="truncate pr-2">{{ $item['product_name'] }} <span class="text-gray-400">{{ $item['label'] ?? '' }}</span></span>
                    <span>৳{{ number_format($item['line_total'], 0) }}</span>
                </div>
                @endforeach
            </div>
        </div>
        <a href="{{ route('checkout.review') }}" class="text-xs text-[#14532d] underline mt-2 inline-block">ঠিকানা পরিবর্তন</a>
    </div>

    <h2 class="text-base font-bold text-[#14532d] mb-3">পেমেন্ট মেথড নির্বাচন করুন</h2>

    <form id="place-order-form" action="{{ route('order.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
        @csrf
        {{-- Hidden order payload (server recomputes all prices) --}}
        <input type="hidden" name="full_name" value="{{ $address->name }}">
        <input type="hidden" name="mobile_number" value="{{ $address->phone }}">
        <input type="hidden" name="full_address" value="{{ $address->full_address }}">
        <input type="hidden" name="bd_division_id" value="{{ $address->bd_division_id }}">
        <input type="hidden" name="bd_district_id" value="{{ $address->bd_district_id }}">
        <input type="hidden" name="bd_upazila_id" value="{{ $address->bd_upazila_id }}">
        <input type="hidden" name="bd_union_id" value="{{ $address->bd_union_id }}">
        <input type="hidden" name="delivery_zone_id" value="{{ $address->delivery_zone_id }}">
        <input type="hidden" name="delivery_location_id" value="{{ $address->delivery_location_id }}">
        @if($address->exists)<input type="hidden" name="customer_address_id" value="{{ $address->id }}">@endif
        @if($comboId)
            <input type="hidden" name="combo_id" value="{{ $comboId }}">
        @else
            @foreach($items as $i => $item)
            <input type="hidden" name="items[{{ $i }}][price_id]" value="{{ $item['price_id'] }}">
            @endforeach
        @endif
        <input type="hidden" name="payment_mode" id="f-payment_mode" value="cod">
        <input type="hidden" name="payment_method" id="f-payment_method" value="cash_on_delivery">

        {{-- COD card --}}
        @if($codEnabled)
        <label class="block cursor-pointer">
            <input type="radio" name="_pay" value="cod" class="sr-only pay-opt" checked onchange="selectPay('cod')">
            <div id="pay-card-cod" class="border-2 border-[#14532d] rounded-2xl p-4 bg-green-50/40 transition-colors">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-bold text-gray-800">Cash on Delivery</p>
                        <p class="text-xs text-gray-500 mt-0.5">ডেলিভারির সময় পেমেন্ট করুন</p>
                        <p class="text-xs text-gray-400 mt-0.5">আনুমানিক ডেলিভারি: {{ $settings->codDeliveryText() }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-[#14532d] text-lg">৳{{ number_format($codTotal, 0) }}</p>
                    </div>
                </div>
            </div>
        </label>
        @endif

        {{-- Instant card --}}
        @if($instantOk)
        <label class="block cursor-pointer">
            <input type="radio" name="_pay" value="instant" class="sr-only pay-opt" {{ $codEnabled ? '' : 'checked' }} onchange="selectPay('instant')">
            <div id="pay-card-instant" class="border-2 {{ $codEnabled ? 'border-gray-200' : 'border-[#14532d] bg-green-50/40' }} rounded-2xl p-4 transition-colors">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-bold text-gray-800">Instant Payment
                            @if($instantDiscount > 0)<span class="text-[11px] bg-amber-500 text-white px-2 py-0.5 rounded-full ml-1">Save ৳{{ number_format($instantDiscount, 0) }}</span>@endif
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">এখন পেমেন্ট করলে ছাড় পাবেন</p>
                        <p class="text-xs text-gray-400 mt-0.5">দ্রুত ডেলিভারি: {{ $settings->instantDeliveryText() }}</p>
                    </div>
                    <div class="text-right">
                        @if($instantDiscount > 0)<p class="text-xs text-gray-400 line-through">৳{{ number_format($codTotal, 0) }}</p>@endif
                        <p class="font-bold text-[#c9a227] text-lg">৳{{ number_format($instantTotal, 0) }}</p>
                    </div>
                </div>

                {{-- Manual channel (revealed when instant selected) --}}
                <div id="instant-panel" class="{{ $codEnabled ? 'hidden' : '' }} mt-4 border-t border-gray-100 pt-3 space-y-3">
                    <div>
                        <p class="text-xs font-semibold text-[#14532d] mb-1.5">কোন মাধ্যমে পেমেন্ট করবেন?</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($manualMethods as $m)
                            <label class="cursor-pointer">
                                <input type="radio" name="_manual" value="{{ $m }}" class="sr-only manual-opt" onchange="selectManual('{{ $m }}')">
                                <span id="manual-chip-{{ $m }}" class="inline-block border-2 border-gray-200 rounded-lg px-3 py-1.5 text-sm">{{ $methodLabels[$m] ?? $m }}</span>
                            </label>
                            @endforeach
                        </div>
                        <div id="manual-number" class="hidden mt-2 bg-green-50 border border-green-200 rounded-xl px-3 py-2 text-xs">
                            <span class="font-bold text-[#14532d]" id="manual-number-text"></span>
                            @if($settings->payment_instruction)<p class="text-gray-600 mt-0.5">{{ $settings->payment_instruction }}</p>@endif
                        </div>
                    </div>
                    <input type="text" name="sender_number" placeholder="যে নম্বর থেকে পাঠিয়েছেন"
                           class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    <input type="text" name="transaction_id" placeholder="ট্রানজেকশন আইডি (TrxID)"
                           class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    <input type="number" name="paid_amount" value="{{ (int) $instantTotal }}" placeholder="পেমেন্ট করা পরিমাণ" min="0"
                           class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
            </div>
        </label>
        @elseif($settings->instant_payment_enabled)
        <div class="border border-dashed border-gray-300 rounded-2xl p-4 text-sm text-gray-500">
            Instant Payment এখন available নেই। অনুগ্রহ করে Cash on Delivery ব্যবহার করুন।
        </div>
        @endif

        @unless($codEnabled || $instantOk)
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800">
            কোনো পেমেন্ট মেথড চালু নেই। অনুগ্রহ করে অ্যাডমিনকে জানান।
        </div>
        @endunless

        {{-- Price details --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-sm">
            <div class="space-y-1.5 text-gray-600">
                <div class="flex justify-between"><span>পণ্যের মূল্য</span><span>৳{{ number_format($subtotal, 0) }}</span></div>
                @if($packaging > 0)<div class="flex justify-between"><span>প্যাকেজিং</span><span>৳{{ number_format($packaging, 0) }}</span></div>@endif
                <div class="flex justify-between"><span>পেমেন্ট ডিসকাউন্ট</span><span id="pd-discount" class="text-green-600">- ৳0</span></div>
                <div class="flex justify-between"><span>ডেলিভারি চার্জ</span><span>৳{{ number_format($deliveryCharge, 0) }}</span></div>
                <div class="border-t border-gray-100 pt-2 flex justify-between font-bold text-gray-800 text-base">
                    <span>অর্ডার মোট</span><span id="pd-total">৳{{ number_format($codTotal, 0) }}</span>
                </div>
            </div>
        </div>

        @if($codEnabled || $instantOk)
        <button type="submit" id="place-order-btn"
                class="w-full bg-[#14532d] hover:bg-[#166534] text-white font-bold py-3.5 rounded-xl transition-colors shadow">
            অর্ডার দিন
        </button>
        @endif
    </form>
</div>
@endsection

@section('scripts')
@php
    $payNumbers = [
        'bkash'  => $settings->bkash_number,
        'nagad'  => $settings->nagad_number,
        'rocket' => $settings->rocket_number,
    ];
@endphp
<script>
const PAY = {
    codTotal:     {{ $codTotal }},
    instantTotal: {{ $instantTotal }},
    discount:     {{ $instantDiscount }},
    numbers:      @json($payNumbers),
};

function money(n) { return '৳' + Math.round(n).toLocaleString('en-IN'); }

function selectPay(mode) {
    document.getElementById('f-payment_mode').value = mode;
    const cod = document.getElementById('pay-card-cod');
    const ins = document.getElementById('pay-card-instant');
    const panel = document.getElementById('instant-panel');
    if (cod) cod.classList.toggle('border-[#14532d]', mode === 'cod');
    if (cod) cod.classList.toggle('bg-green-50/40', mode === 'cod');
    if (cod) cod.classList.toggle('border-gray-200', mode !== 'cod');
    if (ins) ins.classList.toggle('border-[#14532d]', mode === 'instant');
    if (ins) ins.classList.toggle('bg-green-50/40', mode === 'instant');
    if (ins) ins.classList.toggle('border-gray-200', mode !== 'instant');

    if (mode === 'instant') {
        if (panel) panel.classList.remove('hidden');
        document.getElementById('pd-discount').textContent = '- ' + money(PAY.discount);
        document.getElementById('pd-total').textContent = money(PAY.instantTotal);
        // payment_method must be a manual channel; clear until chosen
        document.getElementById('f-payment_method').value = '';
    } else {
        if (panel) panel.classList.add('hidden');
        document.getElementById('pd-discount').textContent = '- ৳0';
        document.getElementById('pd-total').textContent = money(PAY.codTotal);
        document.getElementById('f-payment_method').value = 'cash_on_delivery';
    }
}

function selectManual(method) {
    document.getElementById('f-payment_method').value = method;
    document.querySelectorAll('.manual-opt').forEach(r => {
        const chip = document.getElementById('manual-chip-' + r.value);
        if (chip) { chip.classList.toggle('border-[#14532d]', r.value === method); chip.classList.toggle('text-[#14532d]', r.value === method); }
    });
    const box = document.getElementById('manual-number');
    const num = PAY.numbers[method];
    if (num) { document.getElementById('manual-number-text').textContent = method.toUpperCase() + ': ' + num; box.classList.remove('hidden'); }
    else box.classList.add('hidden');
}

// Initialise selection (instant preselected only if COD disabled)
selectPay(document.querySelector('.pay-opt:checked')?.value || 'cod');

// Submit via fetch (reuse the JSON contract of /order)
document.getElementById('place-order-form')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = document.getElementById('place-order-btn');
    const errBox = document.getElementById('checkout-error');
    errBox.classList.add('hidden');

    if (document.getElementById('f-payment_mode').value === 'instant' && !document.getElementById('f-payment_method').value) {
        errBox.textContent = 'পেমেন্ট মাধ্যম (বিকাশ/নগদ/রকেট) নির্বাচন করুন।';
        errBox.classList.remove('hidden');
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    btn.disabled = true; btn.textContent = 'অর্ডার হচ্ছে...';
    fetch(this.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: new FormData(this),
    }).then(async (res) => {
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.redirect) { window.location.href = data.redirect; return; }
        errBox.textContent = data.message || 'অর্ডার সম্পন্ন করা যায়নি। আবার চেষ্টা করুন।';
        errBox.classList.remove('hidden');
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        btn.disabled = false; btn.textContent = 'অর্ডার দিন';
    }).catch(() => {
        errBox.textContent = 'নেটওয়ার্ক সমস্যা। আবার চেষ্টা করুন।';
        errBox.classList.remove('hidden');
        btn.disabled = false; btn.textContent = 'অর্ডার দিন';
    });
});
</script>
@endsection
