@extends('admin.layout')

@section('title', 'নতুন অর্ডার (ফোন / WhatsApp)')

@section('content')
@php
    $oldItems = collect(old('items', []))->values();
    $input = 'w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500';
@endphp

<div x-data="manualOrder()" x-init="init()" class="max-w-2xl mx-auto pb-40">

    <div class="flex items-center justify-between gap-3 mb-4">
        <h1 class="text-xl font-bold text-gray-800">📞 নতুন অর্ডার</h1>
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-500 hover:text-gray-800">← অর্ডার তালিকা</a>
    </div>

    <form method="POST" action="{{ route('admin.orders.manual.store') }}" x-ref="form" @submit="beforeSubmit($event)" class="space-y-4">
        @csrf

        {{-- ── ১. কাস্টমার ───────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <p class="text-sm font-bold text-gray-700 mb-3"><span class="inline-flex w-6 h-6 rounded-full bg-[#14532d] text-white text-xs items-center justify-center mr-1">১</span> কাস্টমারের মোবাইল নম্বর</p>

            <input type="tel" name="mobile_number" x-model="phone" @input.debounce.500ms="lookup()" required maxlength="20" autofocus
                   placeholder="01XXXXXXXXX" inputmode="tel"
                   class="w-full border-2 border-gray-300 rounded-xl px-4 py-3 text-xl font-mono tracking-wider focus:outline-none focus:border-green-600">

            {{-- Account status --}}
            <div class="mt-2 text-xs min-h-[1.5rem]">
                <template x-if="status === 'registered'">
                    <span class="inline-block bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full" x-text="'👤 রেজিস্টার্ড কাস্টমার' + (known ? ' · ' + known + 'টি আগের অর্ডার' : '')"></span>
                </template>
                <template x-if="status === 'old'">
                    <span class="inline-block bg-green-50 text-green-700 px-2.5 py-1 rounded-full" x-text="'✓ আগে অর্ডার করেছেন (' + known + 'টি) — তথ্য বসানো হয়েছে · অ্যাকাউন্ট নেই, লিংক যাবে'"></span>
                </template>
                <template x-if="status === 'new'">
                    <span class="inline-block bg-amber-50 text-amber-700 px-2.5 py-1 rounded-full">🆕 নতুন কাস্টমার — নাম/ঠিকানা না জানলে ফাঁকা রাখুন, কাস্টমার WhatsApp লিংক থেকে নিজে দিবেন</span>
                </template>
            </div>

            <div class="grid grid-cols-1 gap-3 mt-2">
                <input type="text" name="customer_name" x-model="name" maxlength="100" placeholder="নাম (ঐচ্ছিক)" class="{{ $input }}">
                <textarea name="full_address" x-model="address" rows="2" maxlength="1000" placeholder="ঠিকানা (ঐচ্ছিক — না দিলে কাস্টমার লিংকে দিবেন)" class="{{ $input }}"></textarea>
            </div>
        </div>

        {{-- ── ২. পণ্য ───────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <p class="text-sm font-bold text-gray-700 mb-3"><span class="inline-flex w-6 h-6 rounded-full bg-[#14532d] text-white text-xs items-center justify-center mr-1">২</span> পণ্য বেছে নিন</p>

            <input type="search" x-model="search" @keydown.enter.prevent="pickFirst()"
                   placeholder="🔍 পণ্যের নাম লিখুন…" class="{{ $input }}">

            {{-- Tap-to-add list --}}
            <div class="flex flex-wrap gap-1.5 mt-3 max-h-40 overflow-y-auto">
                <template x-for="p in matches()" :key="p.id">
                    <button type="button" @click="add(p)"
                            class="px-3 py-1.5 rounded-full border text-sm transition-colors"
                            :class="inCart(p.id) ? 'bg-green-600 text-white border-green-600' : (p.onhand > 0 ? 'bg-white text-gray-700 border-gray-200 hover:border-green-500' : 'bg-gray-50 text-gray-400 border-gray-200')">
                        <span x-text="(inCart(p.id) ? '✓ ' : '+ ') + p.name"></span>
                    </button>
                </template>
                <p x-show="matches().length === 0" class="text-sm text-gray-400">কোনো পণ্য মেলেনি।</p>
            </div>

            {{-- Cart --}}
            <div class="mt-4 divide-y divide-gray-100 border-t border-gray-100" x-show="lines.length">
                <template x-for="(line, idx) in lines" :key="line.key">
                    <div class="py-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="font-medium text-gray-800 text-sm min-w-0 truncate" x-text="line.name"></div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="font-bold text-gray-800" x-text="'৳' + money(lineTotal(line))"></span>
                                <button type="button" @click="lines.splice(idx, 1)" class="w-7 h-7 rounded-full text-red-400 hover:bg-red-50">✕</button>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            {{-- Size chips --}}
                            <template x-for="pk in line.packs" :key="pk.id">
                                <button type="button" @click="setPack(line, String(pk.id))"
                                        class="px-2.5 py-1 rounded-lg border text-xs"
                                        :class="line.price_id === String(pk.id) ? 'bg-[#14532d] text-white border-[#14532d]' : 'bg-white text-gray-600 border-gray-200'"
                                        x-text="pk.label + ' ৳' + money(pk.price)"></button>
                            </template>
                            <button type="button" @click="setPack(line, '')"
                                    class="px-2.5 py-1 rounded-lg border text-xs"
                                    :class="line.price_id === '' ? 'bg-[#14532d] text-white border-[#14532d]' : 'bg-white text-gray-600 border-gray-200'"
                                    x-text="'খোলা ৳' + money(line.base) + '/' + line.unit"></button>

                            <div class="flex items-center ml-auto">
                                <button type="button" @click="step(line, -1)" class="w-9 h-9 rounded-l-lg border border-gray-300 text-lg text-gray-600">−</button>
                                <input type="number" step="0.001" min="0.001" x-model.number="line.qty"
                                       class="w-16 h-9 border-y border-gray-300 text-center text-sm font-semibold">
                                <button type="button" @click="step(line, 1)" class="w-9 h-9 rounded-r-lg border border-gray-300 text-lg text-gray-600">+</button>
                                <span class="text-xs text-gray-500 ml-1.5 w-8" x-text="line.price_id ? 'টি' : line.unit"></span>
                            </div>
                        </div>
                        <div class="text-[11px] mt-1" :class="line.onhand > 0 ? 'text-gray-400' : 'text-red-500'">
                            <span x-text="'স্টক: ' + qtyFmt(line.onhand) + ' ' + line.unit"></span>
                            · <button type="button" @click="line.editPrice = !line.editPrice" class="underline">দাম বদলান</button>
                            <input x-show="line.editPrice" type="number" step="0.01" min="0" x-model.number="line.price"
                                   class="ml-1 w-20 border border-gray-300 rounded px-1.5 py-0.5 text-xs text-right">
                        </div>
                    </div>
                </template>
            </div>

            <template x-for="(line, idx) in lines" :key="'h' + line.key">
                <div>
                    <input type="hidden" :name="'items[' + idx + '][product_id]'" :value="line.id">
                    <input type="hidden" :name="'items[' + idx + '][price_id]'" :value="line.price_id">
                    <input type="hidden" :name="'items[' + idx + '][quantity]'" :value="line.qty">
                    <input type="hidden" :name="'items[' + idx + '][unit_price]'" :value="line.price">
                </div>
            </template>
        </div>

        {{-- ── আরও অপশন (collapsed) ───────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-100">
            <button type="button" @click="more = !more" class="w-full flex items-center justify-between px-5 py-3.5 text-sm text-gray-700">
                <span>⚙️ আরও অপশন <span class="text-xs text-gray-400">(ছাড়, অগ্রিম পেমেন্ট, নোট, জেলা…)</span></span>
                <span class="text-gray-400" x-text="more ? '▲' : '▼'"></span>
            </button>
            <div x-show="more" x-cloak class="px-5 pb-5 space-y-4 border-t border-gray-100 pt-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">অর্ডার কোথা থেকে এসেছে</label>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($channels as $key => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="order_channel" value="{{ $key }}" class="peer sr-only" {{ old('order_channel', 'phone') === $key ? 'checked' : '' }}>
                                <span class="inline-block px-3 py-1 rounded-full border text-xs border-gray-200 text-gray-600 peer-checked:bg-[#14532d] peer-checked:text-white peer-checked:border-[#14532d]">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">ছাড় (৳)</label>
                        <input type="number" name="discount_amount" step="0.01" min="0" x-model.number="discount" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">অগ্রিম পেয়েছেন (৳)</label>
                        <input type="number" name="paid_amount" step="0.01" min="0" x-model.number="paid" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">পেমেন্ট মাধ্যম</label>
                        <select name="payment_method" class="{{ $input }} bg-white">
                            @foreach(['cash_on_delivery' => 'ক্যাশ অন ডেলিভারি', 'bkash' => 'বিকাশ', 'nagad' => 'নগদ', 'rocket' => 'রকেট', 'bank' => 'ব্যাংক', 'cash' => 'হাতে নগদ'] as $k => $v)
                                <option value="{{ $k }}" {{ old('payment_method', 'cash_on_delivery') === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">ট্রানজেকশন আইডি</label>
                        <input type="text" name="transaction_id" value="{{ old('transaction_id') }}" maxlength="100" class="{{ $input }} font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">জেলা</label>
                        <input type="text" name="district" x-model="district" maxlength="80" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">থানা / এলাকা</label>
                        <input type="text" name="area" x-model="area" maxlength="80" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">বিকল্প নম্বর</label>
                        <input type="tel" name="alternative_number" value="{{ old('alternative_number') }}" maxlength="20" class="{{ $input }} font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">অর্ডার স্ট্যাটাস</label>
                        <select name="order_status" class="{{ $input }} bg-white">
                            <option value="confirmed" {{ old('order_status', 'confirmed') === 'confirmed' ? 'selected' : '' }}>নিশ্চিত</option>
                            <option value="pending" {{ old('order_status') === 'pending' ? 'selected' : '' }}>অপেক্ষায়</option>
                            <option value="processing" {{ old('order_status') === 'processing' ? 'selected' : '' }}>প্রসেসিং</option>
                        </select>
                    </div>
                </div>
                <textarea name="order_note" rows="2" maxlength="1000" placeholder="অর্ডার নোট" class="{{ $input }}">{{ old('order_note') }}</textarea>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="hidden" name="deduct_stock" value="0">
                    <input type="checkbox" name="deduct_stock" value="1" {{ old('deduct_stock', '1') ? 'checked' : '' }} class="w-4 h-4 accent-[#14532d]">
                    স্টক থেকে কাটুন <span class="text-[11px] text-gray-400">(বাতিল/ফেরত হলে ফিরে আসবে)</span>
                </label>
            </div>
        </div>

        {{-- ── ৩. Sticky bottom bar: delivery + total + save ─────────────── --}}
        <div class="fixed bottom-0 inset-x-0 lg:left-60 z-20 bg-white border-t border-gray-200 shadow-[0_-4px_12px_rgba(0,0,0,0.06)]">
            <div class="max-w-2xl mx-auto px-4 py-3">
                <div class="flex items-center gap-1.5 text-xs mb-2.5">
                    <span class="text-gray-500 mr-1">ডেলিভারি:</span>
                    @foreach([['ঢাকার ভিতরে', $inside], ['ঢাকার বাইরে', $outside], ['ফ্রি', 0]] as [$label, $amt])
                        <button type="button" @click="delivery = {{ (float) $amt }}"
                                class="px-2.5 py-1.5 rounded-lg border"
                                :class="Number(delivery) === {{ (float) $amt }} ? 'bg-[#14532d] text-white border-[#14532d]' : 'bg-white text-gray-600 border-gray-200'">
                            {{ $label }}@if($amt) ৳{{ (int) $amt }}@endif
                        </button>
                    @endforeach
                    <input type="number" name="delivery_charge" step="1" min="0" x-model.number="delivery"
                           class="ml-auto w-16 border border-gray-300 rounded-lg px-2 py-1.5 text-right" title="নিজে লিখুন">
                </div>
                <div class="flex items-center gap-3">
                    <div class="min-w-0">
                        <div class="text-[11px] text-gray-500" x-text="lines.length + 'টি পণ্য' + (paid > 0 ? ' · বাকি ৳' + money(due()) : '')"></div>
                        <div class="text-2xl font-bold text-[#14532d] leading-tight" x-text="'৳' + money(total())"></div>
                    </div>
                    <button type="submit" :disabled="lines.length === 0 || busy"
                            class="ml-auto flex-1 max-w-xs bg-[#25D366] hover:bg-[#1da851] disabled:bg-gray-300 text-white py-3.5 rounded-xl text-sm sm:text-base font-bold">
                        ✓ সেভ করে ইনভয়েস পাঠান →
                    </button>
                </div>
                <p x-show="error" x-cloak class="text-xs text-red-600 mt-1" x-text="error"></p>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function manualOrder() {
    return {
        catalog: @js($catalog),
        lookupUrl: @js(route('admin.orders.manual.lookup')),
        lines: [], seq: 0, search: '', more: {{ $errors->any() && collect(['discount_amount','paid_amount','district','area'])->contains(fn($k) => old($k)) ? 'true' : 'false' }},
        phone: @js(old('mobile_number', '')), name: @js(old('customer_name', '')), address: @js(old('full_address', '')),
        district: @js(old('district', '')), area: @js(old('area', '')),
        status: '', known: 0, lastLookup: '', busy: false, error: '',
        delivery: {{ (float) old('delivery_charge', $inside) }},
        discount: {{ (float) old('discount_amount', 0) }},
        paid: {{ (float) old('paid_amount', 0) }},

        init() {
            (@js($oldItems)).forEach(r => {
                const p = this.catalog.find(c => c.id === Number(r.product_id));
                if (!p) return;
                this.add(p, false);
                const l = this.lines[this.lines.length - 1];
                l.price_id = r.price_id ? String(r.price_id) : '';
                l.qty = Number(r.quantity) || 1;
                l.price = Number(r.unit_price) || 0;
            });
            if (this.phone) this.lookup();
        },
        money(n) { return (Math.round((Number(n) || 0) * 100) / 100).toLocaleString('en-IN', { maximumFractionDigits: 2 }); },
        qtyFmt(n) { return (Math.round((Number(n) || 0) * 1000) / 1000).toString(); },
        inCart(id) { return this.lines.some(l => l.id === id); },
        matches() {
            const q = this.search.trim().toLowerCase();
            const list = q
                ? this.catalog.filter(p => (p.name || '').toLowerCase().includes(q) || (p.sku || '').toLowerCase().includes(q))
                : this.catalog.filter(p => p.active);
            return list.slice(0, q ? 40 : 24);
        },
        pickFirst() { const m = this.matches(); if (m.length) this.add(m[0]); },
        add(p, reset = true) {
            const existing = this.lines.find(l => l.id === p.id);
            if (existing && reset) { this.step(existing, 1); this.search = ''; return; }
            const pack = p.packs.length ? p.packs[0] : null;
            this.lines.push({
                key: ++this.seq, id: p.id, name: p.name, unit: p.unit, onhand: p.onhand, packs: p.packs, base: p.price,
                price_id: pack ? String(pack.id) : '', qty: 1, price: pack ? pack.price : p.price, editPrice: false,
            });
            if (reset) this.search = '';
        },
        setPack(line, id) {
            line.price_id = id;
            const pk = line.packs.find(x => String(x.id) === id);
            line.price = pk ? pk.price : line.base;
        },
        step(line, d) {
            // Packs/pieces move by 1, loose kg by ½ kg, loose grams by 50 g.
            const loose = !line.price_id;
            const inc = loose && line.unit === 'kg' ? 0.5 : (loose && line.unit === 'gram' ? 50 : 1);
            const min = loose && line.unit === 'kg' ? 0.25 : 1;
            line.qty = Math.max(min, Math.round(((Number(line.qty) || 0) + d * inc) * 1000) / 1000);
        },
        lineTotal(l) { return (Number(l.qty) || 0) * (Number(l.price) || 0); },
        subtotal() { return this.lines.reduce((s, l) => s + this.lineTotal(l), 0); },
        total() { return Math.max(0, this.subtotal() + (Number(this.delivery) || 0) - (Number(this.discount) || 0)); },
        due() { return Math.max(0, this.total() - (Number(this.paid) || 0)); },
        async lookup() {
            const digits = (this.phone || '').replace(/\D/g, '');
            if (digits.length < 11) { this.status = ''; return; }
            if (digits === this.lastLookup) return;
            this.lastLookup = digits;
            try {
                const res = await fetch(this.lookupUrl + '?phone=' + encodeURIComponent(digits), { headers: { 'Accept': 'application/json' } });
                const d = await res.json();
                this.known = d.orders || 0;
                this.status = d.registered ? 'registered' : (d.found ? 'old' : 'new');
                if (!this.name && d.name) this.name = d.name;
                if (!this.address && d.address) this.address = d.address;
                if (!this.district && d.district) this.district = d.district;
                if (!this.area && d.area) this.area = d.area;
            } catch (e) {}
        },
        beforeSubmit(e) {
            this.error = '';
            if ((this.phone || '').replace(/\D/g, '').length < 11) { e.preventDefault(); this.error = 'সঠিক মোবাইল নম্বর দিন।'; return; }
            if (this.lines.length === 0) { e.preventDefault(); this.error = 'অন্তত একটি পণ্য যোগ করুন।'; return; }
            this.busy = true;
        },
    };
}
</script>
@endpush
@endsection
