@extends('admin.layout')
@section('title', 'ফোন / WhatsApp অর্ডার')

@section('content')
@php
    $productData = $products->mapWithKeys(fn ($p) => [$p->id => [
        'name'  => $p->name_bn ?: $p->name_en,
        'unit'  => $p->stockUnit(),
        'price' => (float) ($p->retail_price_1kg ?: $p->selling_price ?: 0),
        'stock' => $p->onHand(),
    ]]);
    $customerData = $customers->mapWithKeys(fn ($c) => [$c->mobile_number => [
        'name' => $c->name, 'email' => $c->email, 'address' => $c->last_full_address,
    ]]);
    $oldItems = old('items', [['product_id' => $prefill['product_id'] ?? '', 'quantity' => $prefill['qty'] ?? 1, 'unit_price' => '']]);
    $inp = 'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] bg-white';
@endphp

<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800">ফোন / WhatsApp অর্ডার</h1>
        <p class="text-xs text-gray-500 mt-0.5">কল বা চ্যাটে পাওয়া অর্ডার এখানে তুলুন — অর্ডার তৈরি হলে ভাউচার WhatsApp / ইমেইলে শেয়ার করতে পারবেন।</p>
    </div>
    <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← অর্ডার তালিকা</a>
</div>

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
    <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('admin.orders.manual.store') }}" id="mo-form" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    @csrf
    @if(old('enquiry_id', $prefill['enquiry_id'] ?? null))
    <input type="hidden" name="enquiry_id" value="{{ old('enquiry_id', $prefill['enquiry_id'] ?? '') }}">
    <div class="lg:col-span-3 text-sm bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-2.5">
        পাইকারি Enquiry #{{ old('enquiry_id', $prefill['enquiry_id'] ?? '') }} থেকে অর্ডার — তথ্য আগে থেকে বসানো হয়েছে।
    </div>
    @endif
    <div class="lg:col-span-2 space-y-5">

        {{-- Customer --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h2 class="text-sm font-bold text-gray-800 mb-4">১. ক্রেতা</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">মোবাইল নম্বর *</label>
                    <input type="tel" name="customer_phone" id="mo-phone" list="mo-customers" required autocomplete="off"
                           value="{{ old('customer_phone', $prefill['phone'] ?? '') }}" placeholder="01XXXXXXXXX" class="{{ $inp }}">
                    <datalist id="mo-customers">
                        @foreach($customers as $c)<option value="{{ $c->mobile_number }}">{{ $c->name }}</option>@endforeach
                    </datalist>
                    <p id="mo-known" class="hidden text-[11px] text-green-700 mt-1">✓ পুরনো কাস্টমার — তথ্য বসানো হয়েছে</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">নাম *</label>
                    <input type="text" name="customer_name" id="mo-name" required maxlength="150" value="{{ old('customer_name', $prefill['name'] ?? '') }}" class="{{ $inp }}">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">ইমেইল <span class="font-normal text-gray-400">(ঐচ্ছিক — ভাউচার পাঠাতে)</span></label>
                    <input type="email" name="customer_email" id="mo-email" maxlength="150" value="{{ old('customer_email', $prefill['email'] ?? '') }}" class="{{ $inp }}">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">জেলা / এলাকা</label>
                    <input type="text" name="district" maxlength="80" value="{{ old('district') }}" placeholder="যেমন: ঢাকা, মিরপুর" class="{{ $inp }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">ডেলিভারি ঠিকানা</label>
                    <input type="text" name="full_address" id="mo-address" maxlength="500" value="{{ old('full_address', $prefill['address'] ?? '') }}" class="{{ $inp }}">
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-3">
                <div>
                    <p class="text-xs font-semibold text-gray-600 mb-1.5">অর্ডার এসেছে</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($channels as $val => $lbl)
                        <label class="cursor-pointer">
                            <input type="radio" name="channel" value="{{ $val }}" class="peer sr-only" {{ old('channel', $prefill['channel'] ?? 'phone') === $val ? 'checked' : '' }}>
                            <span class="inline-block text-xs font-semibold px-3 py-1.5 rounded-full border border-gray-200 text-gray-600 peer-checked:bg-[#14532d] peer-checked:text-white peer-checked:border-[#14532d]">{{ $lbl }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-600 mb-1.5">ধরন</p>
                    <div class="flex gap-1.5">
                        @foreach(['retail' => 'খুচরা', 'wholesale' => 'পাইকারি'] as $val => $lbl)
                        <label class="cursor-pointer">
                            <input type="radio" name="order_type" value="{{ $val }}" class="peer sr-only" {{ old('order_type', $prefill['type'] ?? 'retail') === $val ? 'checked' : '' }}>
                            <span class="inline-block text-xs font-semibold px-3 py-1.5 rounded-full border border-gray-200 text-gray-600 peer-checked:bg-amber-600 peer-checked:text-white peer-checked:border-amber-600">{{ $lbl }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h2 class="text-sm font-bold text-gray-800 mb-4">২. পণ্য</h2>
            <div class="hidden sm:grid grid-cols-12 gap-2 text-[11px] font-semibold text-gray-500 uppercase mb-1.5 px-1">
                <span class="col-span-5">পণ্য</span><span class="col-span-2">পরিমাণ</span><span class="col-span-2">দর (৳/একক)</span><span class="col-span-2 text-right">মোট</span><span></span>
            </div>
            <div id="mo-items" class="space-y-2">
                @foreach($oldItems as $i => $row)
                <div class="mo-row grid grid-cols-12 gap-2 items-center bg-gray-50 sm:bg-transparent rounded-lg p-2 sm:p-1">
                    <select name="items[{{ $i }}][product_id]" required class="mo-product col-span-12 sm:col-span-5 {{ $inp }}">
                        <option value="">— পণ্য বেছে নিন —</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected((string) ($row['product_id'] ?? '') === (string) $p->id)>{{ $p->name_bn ?: $p->name_en }}</option>
                        @endforeach
                    </select>
                    <div class="col-span-4 sm:col-span-2 flex items-center gap-1">
                        <input type="number" name="items[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? 1 }}" step="0.001" min="0.001" required class="mo-qty {{ $inp }}">
                        <span class="mo-unit text-xs text-gray-500 w-8">kg</span>
                    </div>
                    <input type="number" name="items[{{ $i }}][unit_price]" value="{{ $row['unit_price'] ?? '' }}" step="0.01" min="0" required placeholder="দর" class="mo-price col-span-4 sm:col-span-2 {{ $inp }}">
                    <span class="mo-line col-span-3 sm:col-span-2 text-right font-semibold text-gray-800 text-sm">৳0</span>
                    <button type="button" onclick="moRemove(this)" class="col-span-1 text-gray-400 hover:text-red-600 text-lg" aria-label="সারি মুছুন">×</button>
                    <p class="mo-stock col-span-12 text-[11px] text-gray-400 -mt-1 px-1"></p>
                </div>
                @endforeach
            </div>
            <button type="button" onclick="moAdd()" class="mt-3 text-sm font-semibold text-[#14532d] hover:underline">+ আরও পণ্য যোগ করুন</button>
        </div>
    </div>

    {{-- Summary --}}
    <div class="space-y-5">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 lg:sticky lg:top-4">
            <h2 class="text-sm font-bold text-gray-800 mb-4">৩. দাম ও পেমেন্ট</h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">পণ্যের মোট</span><span id="mo-sub" class="font-semibold">৳0</span></div>
                <div class="flex items-center justify-between gap-3">
                    <label class="text-gray-500" for="mo-delivery">ডেলিভারি চার্জ</label>
                    <input type="number" name="delivery_charge" id="mo-delivery" value="{{ old('delivery_charge', 0) }}" min="0" step="1" class="w-28 border border-gray-200 rounded-lg px-2 py-1.5 text-right">
                </div>
                <div class="flex items-center justify-between gap-3">
                    <label class="text-gray-500" for="mo-discount">ছাড়</label>
                    <input type="number" name="discount" id="mo-discount" value="{{ old('discount', 0) }}" min="0" step="1" class="w-28 border border-gray-200 rounded-lg px-2 py-1.5 text-right">
                </div>
                <div class="flex justify-between border-t pt-3 text-base"><span class="font-bold">সর্বমোট</span><span id="mo-grand" class="font-bold text-[#14532d]">৳0</span></div>
                <div class="flex items-center justify-between gap-3">
                    <label class="text-gray-500" for="mo-paid">অগ্রিম / পরিশোধ</label>
                    <input type="number" name="paid_amount" id="mo-paid" value="{{ old('paid_amount', 0) }}" min="0" step="1" class="w-28 border border-gray-200 rounded-lg px-2 py-1.5 text-right">
                </div>
                <div class="flex justify-between"><span class="text-gray-500">বাকি</span><span id="mo-due" class="font-semibold text-red-600">৳0</span></div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">পেমেন্ট পদ্ধতি</label>
                    <select name="payment_method" class="{{ $inp }}">
                        @foreach($payments as $val => $lbl)<option value="{{ $val }}" @selected(old('payment_method', 'cash_on_delivery') === $val)>{{ $lbl }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">নোট</label>
                    <textarea name="order_note" rows="2" maxlength="1000" class="{{ $inp }}" placeholder="ডেলিভারির সময়, বিশেষ অনুরোধ...">{{ old('order_note') }}</textarea>
                </div>
            </div>
            <button type="submit" class="mt-5 w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-bold py-3 rounded-xl transition-colors">
                অর্ডার তৈরি করুন → ভাউচার
            </button>
            <p class="text-[11px] text-gray-400 mt-2 text-center">স্টক কেটে নেওয়া হবে। নতুন নম্বর হলে কাস্টমার অ্যাকাউন্ট নিজে থেকে তৈরি হবে।</p>
        </div>
    </div>
</form>

<template id="mo-row-tpl">
    <div class="mo-row grid grid-cols-12 gap-2 items-center bg-gray-50 sm:bg-transparent rounded-lg p-2 sm:p-1">
        <select name="items[__I__][product_id]" required class="mo-product col-span-12 sm:col-span-5 {{ $inp }}">
            <option value="">— পণ্য বেছে নিন —</option>
            @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name_bn ?: $p->name_en }}</option>@endforeach
        </select>
        <div class="col-span-4 sm:col-span-2 flex items-center gap-1">
            <input type="number" name="items[__I__][quantity]" value="1" step="0.001" min="0.001" required class="mo-qty {{ $inp }}">
            <span class="mo-unit text-xs text-gray-500 w-8">kg</span>
        </div>
        <input type="number" name="items[__I__][unit_price]" step="0.01" min="0" required placeholder="দর" class="mo-price col-span-4 sm:col-span-2 {{ $inp }}">
        <span class="mo-line col-span-3 sm:col-span-2 text-right font-semibold text-gray-800 text-sm">৳0</span>
        <button type="button" onclick="moRemove(this)" class="col-span-1 text-gray-400 hover:text-red-600 text-lg" aria-label="সারি মুছুন">×</button>
        <p class="mo-stock col-span-12 text-[11px] text-gray-400 -mt-1 px-1"></p>
    </div>
</template>

<script>
(function () {
    const PRODUCTS = @json($productData);
    const CUSTOMERS = @json($customerData);
    const box = document.getElementById('mo-items');
    let idx = box.querySelectorAll('.mo-row').length;
    const tk = n => '৳' + Math.round(n).toLocaleString('en-US');
    const num = el => parseFloat(el && el.value) || 0;

    function fillRow(row, setPrice) {
        const p = PRODUCTS[row.querySelector('.mo-product').value];
        row.querySelector('.mo-unit').textContent = p ? p.unit : '';
        const price = row.querySelector('.mo-price');
        if (p && setPrice && !price.value) price.value = p.price || '';
        row.querySelector('.mo-stock').textContent = p ? 'স্টক: ' + p.stock + ' ' + p.unit : '';
    }
    function recalc() {
        let sub = 0;
        box.querySelectorAll('.mo-row').forEach(r => {
            const line = num(r.querySelector('.mo-qty')) * num(r.querySelector('.mo-price'));
            r.querySelector('.mo-line').textContent = tk(line);
            sub += line;
        });
        const grand = Math.max(0, sub + num(document.getElementById('mo-delivery')) - Math.min(num(document.getElementById('mo-discount')), sub));
        const paid = Math.min(num(document.getElementById('mo-paid')), grand);
        document.getElementById('mo-sub').textContent = tk(sub);
        document.getElementById('mo-grand').textContent = tk(grand);
        document.getElementById('mo-due').textContent = tk(grand - paid);
    }
    window.moAdd = function () {
        const html = document.getElementById('mo-row-tpl').innerHTML.replaceAll('__I__', idx++);
        box.insertAdjacentHTML('beforeend', html);
        box.lastElementChild.querySelector('.mo-product').focus();
        recalc();
    };
    window.moRemove = function (btn) {
        if (box.querySelectorAll('.mo-row').length > 1) btn.closest('.mo-row').remove();
        recalc();
    };
    box.addEventListener('change', e => { if (e.target.classList.contains('mo-product')) { fillRow(e.target.closest('.mo-row'), true); recalc(); } });
    document.getElementById('mo-form').addEventListener('input', recalc);

    // Known customer → fill name / email / address from the phone number.
    const phone = document.getElementById('mo-phone');
    phone.addEventListener('input', () => {
        const c = CUSTOMERS[phone.value.trim()];
        document.getElementById('mo-known').classList.toggle('hidden', !c);
        if (!c) return;
        const name = document.getElementById('mo-name'), email = document.getElementById('mo-email'), addr = document.getElementById('mo-address');
        if (!name.value) name.value = c.name || '';
        if (!email.value) email.value = c.email || '';
        if (!addr.value) addr.value = c.address || '';
    });

    box.querySelectorAll('.mo-row').forEach(r => fillRow(r, true));
    recalc();
})();
</script>
@endsection
