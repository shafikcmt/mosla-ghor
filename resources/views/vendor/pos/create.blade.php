@extends('vendor.layout')
@section('title', 'নতুন বিক্রয় (POS)')

@php
    use App\Support\VendorSettings;
    $canDiscount = VendorSettings::vendorCanGiveDiscount();
    $canDue      = VendorSettings::vendorCanAllowDue();
    $maxDiscPct  = VendorSettings::vendorMaxDiscountPercent();

    // Catalog payload for the Alpine line builder.
    $catalog = $products->map(fn($p) => [
        'id'     => $p->id,
        'name'   => $p->name_bn ?: $p->name_en,
        'sku'    => $p->sku,
        'unit'   => $p->stockUnit(),
        'price'  => (float) ($p->selling_price ?: $p->retail_price_1kg ?: 0),
        'onhand' => (float) $p->onHand(),
    ])->values();

    $preselect = (int) request('customer', 0);
@endphp

@section('content')
<div x-data="pos()" x-init="init()">
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-bold text-gray-800">নতুন বিক্রয় (POS)</h1>
        <a href="{{ route('vendor.pos.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">← বিক্রয় তালিকা</a>
    </div>

    <form method="POST" action="{{ route('vendor.pos.store') }}" @submit="syncBeforeSubmit">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- ── Left: items ─────────────────────────────────────────── --}}
            <div class="lg:col-span-2 space-y-5">
                <div class="bg-white rounded-xl border border-gray-100 p-5">
                    <p class="text-sm font-bold text-gray-700 mb-3">পণ্য যোগ করুন</p>
                    <div class="flex gap-2">
                        <select x-model.number="picker" class="flex-1 border rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="0">— পণ্য নির্বাচন করুন —</option>
                            <template x-for="p in catalog" :key="p.id">
                                <option :value="p.id" x-text="p.name + ' (স্টক: ' + fmt(p.onhand) + ' ' + p.unit + ')'"></option>
                            </template>
                        </select>
                        <button type="button" @click="addLine()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm whitespace-nowrap">যোগ করুন</button>
                    </div>

                    <div class="overflow-x-auto mt-4">
                        <table class="w-full text-sm">
                            <thead class="text-gray-400 text-xs uppercase border-b">
                                <tr>
                                    <th class="text-left py-2">পণ্য</th>
                                    <th class="text-right py-2 w-24">পরিমাণ</th>
                                    <th class="text-right py-2 w-24">দাম</th>
                                    <th class="text-right py-2 w-24">ছাড়</th>
                                    <th class="text-right py-2 w-24">মোট</th>
                                    <th class="py-2 w-8"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(line, idx) in lines" :key="idx">
                                    <tr class="border-b border-gray-50">
                                        <td class="py-2">
                                            <div class="font-medium text-gray-800" x-text="line.name"></div>
                                            <div class="text-xs" :class="line.qty > line.onhand ? 'text-red-500' : 'text-gray-400'"
                                                 x-text="'স্টক: ' + fmt(line.onhand) + ' ' + line.unit"></div>
                                        </td>
                                        <td class="py-2 text-right">
                                            <input type="number" step="0.001" min="0.001" x-model.number="line.qty"
                                                   class="w-20 border rounded px-2 py-1 text-sm text-right">
                                        </td>
                                        <td class="py-2 text-right">
                                            <input type="number" step="0.01" min="0" x-model.number="line.price"
                                                   class="w-20 border rounded px-2 py-1 text-sm text-right">
                                        </td>
                                        <td class="py-2 text-right">
                                            <input type="number" step="0.01" min="0" x-model.number="line.discount"
                                                   class="w-20 border rounded px-2 py-1 text-sm text-right">
                                        </td>
                                        <td class="py-2 text-right font-semibold text-gray-800" x-text="'৳' + fmt2(lineTotal(line))"></td>
                                        <td class="py-2 text-right">
                                            <button type="button" @click="lines.splice(idx,1)" class="text-red-400 hover:text-red-600">✕</button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="lines.length === 0">
                                    <td colspan="6" class="py-8 text-center text-gray-400">এখনো কোনো পণ্য যোগ করা হয়নি।</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Hidden item inputs synced from Alpine on submit --}}
                <template x-for="(line, idx) in lines" :key="'h'+idx">
                    <div>
                        <input type="hidden" :name="'items['+idx+'][product_id]'" :value="line.id">
                        <input type="hidden" :name="'items['+idx+'][quantity]'"   :value="line.qty">
                        <input type="hidden" :name="'items['+idx+'][unit_price]'" :value="line.price">
                        <input type="hidden" :name="'items['+idx+'][discount]'"   :value="line.discount || 0">
                    </div>
                </template>

                <div class="bg-white rounded-xl border border-gray-100 p-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">অর্ডার নোট</label>
                    <textarea name="order_note" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('order_note') }}</textarea>
                </div>
            </div>

            {{-- ── Right: customer + payment ───────────────────────────── --}}
            <div class="space-y-5">
                <div class="bg-white rounded-xl border border-gray-100 p-5">
                    <p class="text-sm font-bold text-gray-700 mb-3">কাস্টমার</p>

                    <div class="flex gap-2 mb-3 text-xs">
                        <button type="button" @click="mode='existing'" :class="mode==='existing' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600'" class="px-3 py-1.5 rounded-lg">তালিকা থেকে</button>
                        <button type="button" @click="mode='walkin'"  :class="mode==='walkin'  ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600'" class="px-3 py-1.5 rounded-lg">নতুন / ওয়াক-ইন</button>
                    </div>

                    {{-- Existing customer --}}
                    <div x-show="mode==='existing'">
                        <select name="vendor_customer_id" x-ref="vc" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="">— কাস্টমার নির্বাচন —</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ $preselect === $c->id ? 'selected' : '' }}>{{ $c->name }} — {{ $c->phone }}</option>
                            @endforeach
                        </select>
                        <a href="{{ route('vendor.customers.create') }}" class="text-xs text-indigo-600 hover:underline mt-2 inline-block">+ নতুন কাস্টমার যোগ করুন</a>
                    </div>

                    {{-- Walk-in --}}
                    <div x-show="mode==='walkin'" class="space-y-2">
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="নাম *"
                               class="w-full border rounded-lg px-3 py-2 text-sm" :disabled="mode!=='walkin'">
                        <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="ফোন"
                               class="w-full border rounded-lg px-3 py-2 text-sm" :disabled="mode!=='walkin'">
                        <input type="text" name="customer_address" value="{{ old('customer_address') }}" placeholder="ঠিকানা"
                               class="w-full border rounded-lg px-3 py-2 text-sm" :disabled="mode!=='walkin'">
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 p-5 space-y-3">
                    <p class="text-sm font-bold text-gray-700">পেমেন্ট</p>

                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">সাবটোটাল</span>
                        <span class="font-semibold" x-text="'৳' + fmt2(subtotal())"></span>
                    </div>

                    @if($canDiscount)
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">অর্ডার ছাড় (৳)</span>
                        <input type="number" name="order_discount" step="0.01" min="0" x-model.number="orderDiscount"
                               class="w-24 border rounded px-2 py-1 text-sm text-right">
                    </div>
                    <p class="text-[11px] text-gray-400 -mt-1">সর্বোচ্চ {{ $maxDiscPct }}%</p>
                    @endif

                    <div class="flex justify-between text-base font-bold border-t pt-2">
                        <span>সর্বমোট</span>
                        <span class="text-indigo-700" x-text="'৳' + fmt2(grandTotal())"></span>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">পরিশোধিত (৳)</label>
                        <input type="number" name="paid_amount" step="0.01" min="0" x-model.number="paid"
                               class="w-full border rounded-lg px-3 py-2 text-sm text-right">
                        <div class="flex gap-2 mt-2 text-xs">
                            <button type="button" @click="paid = grandTotal()" class="px-2 py-1 bg-gray-100 rounded">সম্পূর্ণ</button>
                            <button type="button" @click="paid = 0" class="px-2 py-1 bg-gray-100 rounded">বাকি</button>
                        </div>
                    </div>

                    <div class="flex justify-between text-sm font-semibold" :class="due() > 0 ? 'text-red-600' : 'text-green-600'">
                        <span>বাকি</span>
                        <span x-text="'৳' + fmt2(due())"></span>
                    </div>
                    @unless($canDue)
                    <p class="text-[11px] text-amber-600" x-show="due() > 0">বাকিতে বিক্রির অনুমতি নেই — সম্পূর্ণ টাকা নিন।</p>
                    @endunless

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">পেমেন্ট মাধ্যম</label>
                        <select name="payment_method" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="cash">নগদ (Cash)</option>
                            <option value="bkash">বিকাশ</option>
                            <option value="nagad">নগদ (Mobile)</option>
                            <option value="bank">ব্যাংক</option>
                            <option value="other">অন্যান্য</option>
                        </select>
                    </div>

                    <button type="submit" :disabled="lines.length === 0"
                            class="w-full bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white py-2.5 rounded-lg text-sm font-bold">
                        বিক্রয় সম্পন্ন করুন
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function pos() {
    return {
        catalog: @js($catalog),
        lines: [],
        picker: 0,
        mode: '{{ $preselect ? 'existing' : 'walkin' }}',
        orderDiscount: 0,
        paid: 0,
        init() {},
        fmt(n)  { return (Math.round((n + Number.EPSILON) * 1000) / 1000).toString(); },
        fmt2(n) { return (Math.round((n + Number.EPSILON) * 100) / 100).toFixed(2); },
        addLine() {
            if (!this.picker) return;
            const p = this.catalog.find(x => x.id === this.picker);
            if (!p) return;
            const ex = this.lines.find(l => l.id === p.id);
            if (ex) { ex.qty += 1; }
            else { this.lines.push({ id: p.id, name: p.name, unit: p.unit, onhand: p.onhand, qty: 1, price: p.price, discount: 0 }); }
            this.picker = 0;
        },
        lineTotal(l) { return Math.max(0, (l.qty || 0) * (l.price || 0) - (l.discount || 0)); },
        subtotal() { return this.lines.reduce((s, l) => s + this.lineTotal(l), 0); },
        grandTotal() { return Math.max(0, this.subtotal() - (this.orderDiscount || 0)); },
        due() { return Math.max(0, this.grandTotal() - (this.paid || 0)); },
        syncBeforeSubmit(e) {
            if (this.lines.length === 0) { e.preventDefault(); alert('অন্তত একটি পণ্য যোগ করুন।'); return; }
            // Clear the inactive customer-mode input so validation is unambiguous.
            if (this.mode === 'existing') {
                this.$root.querySelectorAll('[name=customer_name]').forEach(i => i.value = '');
            } else {
                this.$refs.vc.value = '';
            }
        }
    };
}
</script>
@endpush
@endsection
