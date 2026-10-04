@extends('admin.layout')

@section('title', 'নতুন অর্ডার (ফোন / WhatsApp)')

@section('content')
@php
    $oldItems = collect(old('items', []))->values();
@endphp

<div x-data="manualOrder()" x-init="init()">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-gray-800">📞 নতুন অর্ডার — ফোন / WhatsApp</h1>
            <p class="text-xs text-gray-500 mt-0.5">ফোনে বা মেসেজে আসা অর্ডার এখানে লিখুন — সেভ করলেই ইনভয়েস তৈরি হবে, এক ক্লিকে কাস্টমারের WhatsApp এ পাঠাতে পারবেন।</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-500 hover:text-gray-800">← অর্ডার তালিকা</a>
    </div>

    <form method="POST" action="{{ route('admin.orders.manual.store') }}" @submit="beforeSubmit($event)">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- ── Left ─────────────────────────────────────────────── --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Channel --}}
                <div class="bg-white rounded-xl border border-gray-100 p-5">
                    <p class="text-sm font-bold text-gray-700 mb-3">অর্ডার কোথা থেকে এসেছে?</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($channels as $key => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="order_channel" value="{{ $key }}" class="peer sr-only"
                                       {{ old('order_channel', 'phone') === $key ? 'checked' : '' }}>
                                <span class="inline-block px-3 py-1.5 rounded-full border text-sm border-gray-200 text-gray-600 peer-checked:bg-[#14532d] peer-checked:text-white peer-checked:border-[#14532d]">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Customer --}}
                <div class="bg-white rounded-xl border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-bold text-gray-700">কাস্টমার</p>
                        <span x-show="known" x-cloak class="text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded-full" x-text="'পুরনো কাস্টমার — ' + known + 'টি অর্ডার'"></span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">মোবাইল নম্বর <span class="text-red-500">*</span></label>
                            <input type="tel" name="mobile_number" x-model="phone" @change="lookup()" @blur="lookup()" required maxlength="20"
                                   placeholder="01XXXXXXXXX"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500">
                            <p class="text-[11px] text-gray-400 mt-1">নম্বর দিলে আগের অর্ডার থেকে নাম-ঠিকানা অটো বসবে।</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">নাম <span class="text-red-500">*</span></label>
                            <input type="text" name="customer_name" x-model="name" required maxlength="100"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">পূর্ণ ঠিকানা <span class="text-red-500">*</span></label>
                            <textarea name="full_address" x-model="address" rows="2" required maxlength="1000"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">জেলা</label>
                            <input type="text" name="district" x-model="district" maxlength="80"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">থানা / এলাকা</label>
                            <input type="text" name="area" x-model="area" maxlength="80"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">বিকল্প নম্বর</label>
                            <input type="tel" name="alternative_number" value="{{ old('alternative_number') }}" maxlength="20"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                </div>

                {{-- Items --}}
                <div class="bg-white rounded-xl border border-gray-100 p-5">
                    <p class="text-sm font-bold text-gray-700 mb-3">পণ্য</p>

                    <div class="relative" @click.outside="results = false">
                        <input type="search" x-model="search" @focus="results = true" @input="results = true"
                               @keydown.enter.prevent="pickFirst()"
                               placeholder="🔍 পণ্যের নাম / SKU লিখুন…"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        <div x-show="results && matches().length" x-cloak
                             class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                            <template x-for="p in matches()" :key="p.id">
                                <button type="button" @click="add(p)"
                                        class="w-full text-left px-3 py-2 hover:bg-green-50 flex items-center justify-between gap-3 border-b border-gray-50">
                                    <span class="min-w-0">
                                        <span class="block text-sm text-gray-800 truncate" x-text="p.name"></span>
                                        <span class="block text-[11px] text-gray-400">
                                            <span x-text="'৳' + money(p.price) + '/' + p.unit"></span>
                                            <span x-show="p.packs.length" x-text="' · ' + p.packs.length + 'টি প্যাক সাইজ'"></span>
                                            <span x-show="!p.active" class="text-amber-600"> · নিষ্ক্রিয়</span>
                                        </span>
                                    </span>
                                    <span class="text-[11px] flex-shrink-0" :class="p.onhand > 0 ? 'text-gray-500' : 'text-red-500'"
                                          x-text="'স্টক: ' + qtyFmt(p.onhand) + ' ' + p.unit"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <template x-for="(line, idx) in lines" :key="line.key">
                            <div class="border border-gray-100 rounded-lg p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-800 text-sm" x-text="line.name"></div>
                                        <div class="text-[11px]" :class="line.onhand > 0 ? 'text-gray-400' : 'text-red-500'"
                                             x-text="'স্টক: ' + qtyFmt(line.onhand) + ' ' + line.unit"></div>
                                    </div>
                                    <button type="button" @click="lines.splice(idx, 1)" class="text-red-400 hover:text-red-600 text-sm px-1">✕</button>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2 items-end">
                                    <div class="col-span-2 sm:col-span-1">
                                        <label class="block text-[11px] text-gray-500 mb-0.5">সাইজ</label>
                                        <select x-model="line.price_id" @change="packChanged(line)"
                                                class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm bg-white">
                                            <option value="" x-text="'খোলা (প্রতি ' + line.unit + ')'"></option>
                                            <template x-for="pk in line.packs" :key="pk.id">
                                                <option :value="String(pk.id)" x-text="pk.label + ' — ৳' + money(pk.price)" :selected="String(pk.id) === line.price_id"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] text-gray-500 mb-0.5" x-text="line.price_id ? 'কয়টি' : 'পরিমাণ (' + line.unit + ')'"></label>
                                        <div class="flex">
                                            <button type="button" @click="step(line, -1)" class="px-2 border border-r-0 border-gray-300 rounded-l-md text-gray-600">−</button>
                                            <input type="number" step="0.001" min="0.001" x-model.number="line.qty"
                                                   class="w-full min-w-0 border border-gray-300 px-2 py-1.5 text-sm text-center">
                                            <button type="button" @click="step(line, 1)" class="px-2 border border-l-0 border-gray-300 rounded-r-md text-gray-600">+</button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] text-gray-500 mb-0.5">দাম (৳)</label>
                                        <input type="number" step="0.01" min="0" x-model.number="line.price"
                                               class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm text-right">
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[11px] text-gray-500 mb-0.5">মোট</div>
                                        <div class="font-bold text-gray-800 py-1.5" x-text="'৳' + money(lineTotal(line))"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="lines.length === 0" class="border border-dashed border-gray-200 rounded-lg py-8 text-center text-sm text-gray-400">
                            উপরে সার্চ করে পণ্য যোগ করুন।
                        </div>
                    </div>

                    {{-- Hidden inputs posted with the form --}}
                    <template x-for="(line, idx) in lines" :key="'h' + line.key">
                        <div>
                            <input type="hidden" :name="'items[' + idx + '][product_id]'" :value="line.id">
                            <input type="hidden" :name="'items[' + idx + '][price_id]'" :value="line.price_id">
                            <input type="hidden" :name="'items[' + idx + '][quantity]'" :value="line.qty">
                            <input type="hidden" :name="'items[' + idx + '][unit_price]'" :value="line.price">
                        </div>
                    </template>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 p-5">
                    <label class="block text-xs font-medium text-gray-600 mb-1">অর্ডার নোট (কাস্টমারের বিশেষ অনুরোধ ইত্যাদি)</label>
                    <textarea name="order_note" rows="2" maxlength="1000"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">{{ old('order_note') }}</textarea>
                </div>
            </div>

            {{-- ── Right: totals ────────────────────────────────────── --}}
            <div>
                <div class="bg-white rounded-xl border border-gray-100 p-5 space-y-3 lg:sticky lg:top-20">
                    <p class="text-sm font-bold text-gray-700">হিসাব</p>

                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">সাবটোটাল</span>
                        <span class="font-semibold" x-text="'৳' + money(subtotal())"></span>
                    </div>

                    <div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">ডেলিভারি চার্জ</span>
                            <input type="number" name="delivery_charge" step="0.01" min="0" x-model.number="delivery"
                                   class="w-24 border border-gray-300 rounded-md px-2 py-1 text-sm text-right">
                        </div>
                        <div class="flex flex-wrap justify-end gap-1 mt-1.5 text-[11px]">
                            <button type="button" @click="delivery = {{ $inside }}" class="px-2 py-0.5 rounded bg-gray-100 text-gray-600">ঢাকার ভিতরে ৳{{ (int) $inside }}</button>
                            <button type="button" @click="delivery = {{ $outside }}" class="px-2 py-0.5 rounded bg-gray-100 text-gray-600">ঢাকার বাইরে ৳{{ (int) $outside }}</button>
                            <button type="button" @click="delivery = 0" class="px-2 py-0.5 rounded bg-gray-100 text-gray-600">ফ্রি</button>
                        </div>
                    </div>

                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">ছাড় (৳)</span>
                        <input type="number" name="discount_amount" step="0.01" min="0" x-model.number="discount"
                               class="w-24 border border-gray-300 rounded-md px-2 py-1 text-sm text-right">
                    </div>

                    <div class="flex justify-between text-base font-bold border-t pt-2">
                        <span>সর্বমোট</span>
                        <span class="text-[#14532d]" x-text="'৳' + money(total())"></span>
                    </div>

                    <div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">অগ্রিম / পরিশোধিত</span>
                            <input type="number" name="paid_amount" step="0.01" min="0" x-model.number="paid"
                                   class="w-24 border border-gray-300 rounded-md px-2 py-1 text-sm text-right">
                        </div>
                        <div class="flex justify-end gap-1 mt-1.5 text-[11px]">
                            <button type="button" @click="paid = 0" class="px-2 py-0.5 rounded bg-gray-100 text-gray-600">ক্যাশ অন ডেলিভারি</button>
                            <button type="button" @click="paid = delivery" class="px-2 py-0.5 rounded bg-gray-100 text-gray-600">শুধু ডেলিভারি চার্জ</button>
                            <button type="button" @click="paid = total()" class="px-2 py-0.5 rounded bg-gray-100 text-gray-600">পুরোটা</button>
                        </div>
                    </div>

                    <div class="flex justify-between text-sm font-semibold" :class="due() > 0 ? 'text-red-600' : 'text-green-600'">
                        <span>বাকি (COD)</span>
                        <span x-text="'৳' + money(due())"></span>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-600 mb-1">পেমেন্ট মাধ্যম</label>
                        <select name="payment_method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                            @foreach(['cash_on_delivery' => 'ক্যাশ অন ডেলিভারি', 'bkash' => 'বিকাশ', 'nagad' => 'নগদ', 'rocket' => 'রকেট', 'bank' => 'ব্যাংক', 'cash' => 'হাতে নগদ'] as $k => $v)
                                <option value="{{ $k }}" {{ old('payment_method', 'cash_on_delivery') === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="paid > 0" x-cloak>
                        <label class="block text-xs text-gray-600 mb-1">ট্রানজেকশন আইডি (ঐচ্ছিক)</label>
                        <input type="text" name="transaction_id" value="{{ old('transaction_id') }}" maxlength="100"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">অর্ডার স্ট্যাটাস</label>
                        <select name="order_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="confirmed" {{ old('order_status', 'confirmed') === 'confirmed' ? 'selected' : '' }}>নিশ্চিত (Confirmed)</option>
                            <option value="pending" {{ old('order_status') === 'pending' ? 'selected' : '' }}>অপেক্ষায় (Pending)</option>
                            <option value="processing" {{ old('order_status') === 'processing' ? 'selected' : '' }}>প্রসেসিং</option>
                        </select>
                    </div>
                    <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="hidden" name="deduct_stock" value="0">
                        <input type="checkbox" name="deduct_stock" value="1" {{ old('deduct_stock', '1') ? 'checked' : '' }} class="mt-0.5 w-4 h-4 accent-[#14532d]">
                        <span>স্টক থেকে কাটুন <span class="block text-[11px] text-gray-400">বাতিল / ফেরত হলে স্টক আবার ফিরে আসবে।</span></span>
                    </label>

                    <button type="submit" :disabled="lines.length === 0"
                            class="w-full bg-[#14532d] hover:bg-[#0d3520] disabled:opacity-50 text-white py-3 rounded-lg text-sm font-bold">
                        ✓ অর্ডার সেভ করুন ও ইনভয়েস বানান
                    </button>
                    <p class="text-[11px] text-gray-400 text-center">সেভ করার পর WhatsApp এ ইনভয়েস পাঠানোর বাটন পাবেন।</p>
                </div>
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
        lines: [], seq: 0, search: '', results: false,
        phone: @js(old('mobile_number', '')), name: @js(old('customer_name', '')), address: @js(old('full_address', '')),
        district: @js(old('district', '')), area: @js(old('area', '')), known: 0, lastLookup: '',
        delivery: {{ (float) old('delivery_charge', $inside) }},
        discount: {{ (float) old('discount_amount', 0) }},
        paid: {{ (float) old('paid_amount', 0) }},

        init() {
            // Restore lines after a validation error.
            (@js($oldItems)).forEach(r => {
                const p = this.catalog.find(c => c.id === Number(r.product_id));
                if (!p) return;
                this.add(p, false);
                const l = this.lines[this.lines.length - 1];
                l.price_id = r.price_id ? String(r.price_id) : '';
                l.qty = Number(r.quantity) || 1;
                l.price = Number(r.unit_price) || 0;
            });
        },
        money(n) { return (Math.round((Number(n) || 0) * 100) / 100).toLocaleString('en-IN', { maximumFractionDigits: 2 }); },
        qtyFmt(n) { return (Math.round((Number(n) || 0) * 1000) / 1000).toString(); },
        matches() {
            const q = this.search.trim().toLowerCase();
            const list = q ? this.catalog.filter(p => (p.name || '').toLowerCase().includes(q) || (p.sku || '').toLowerCase().includes(q)) : this.catalog;
            return list.slice(0, 30);
        },
        pickFirst() { const m = this.matches(); if (m.length) this.add(m[0]); },
        add(p, reset = true) {
            const pack = p.packs.length ? p.packs[0] : null;
            this.lines.push({
                key: ++this.seq, id: p.id, name: p.name, unit: p.unit, onhand: p.onhand, packs: p.packs, base: p.price,
                price_id: pack ? String(pack.id) : '', qty: 1, price: pack ? pack.price : p.price,
            });
            if (reset) { this.search = ''; this.results = false; }
        },
        packChanged(line) {
            const pk = line.packs.find(x => String(x.id) === line.price_id);
            line.price = pk ? pk.price : line.base;
        },
        step(line, d) { line.qty = Math.max(line.price_id ? 1 : 0.001, Math.round(((Number(line.qty) || 0) + d) * 1000) / 1000); },
        lineTotal(l) { return (Number(l.qty) || 0) * (Number(l.price) || 0); },
        subtotal() { return this.lines.reduce((s, l) => s + this.lineTotal(l), 0); },
        total() { return Math.max(0, this.subtotal() + (Number(this.delivery) || 0) - (Number(this.discount) || 0)); },
        due() { return Math.max(0, this.total() - (Number(this.paid) || 0)); },
        async lookup() {
            const digits = (this.phone || '').replace(/\D/g, '');
            if (digits.length < 10 || digits === this.lastLookup) return;
            this.lastLookup = digits;
            try {
                const res = await fetch(this.lookupUrl + '?phone=' + encodeURIComponent(digits), { headers: { 'Accept': 'application/json' } });
                const d = await res.json();
                this.known = d.found ? d.orders : 0;
                if (!d.found) return;
                if (!this.name) this.name = d.name || '';
                if (!this.address) this.address = d.address || '';
                if (!this.district) this.district = d.district || '';
                if (!this.area) this.area = d.area || '';
            } catch (e) {}
        },
        beforeSubmit(e) {
            if (this.lines.length === 0) { e.preventDefault(); alert('অন্তত একটি পণ্য যোগ করুন।'); }
        },
    };
}
</script>
@endpush
@endsection
