@extends('vendor.layout')
@section('title', 'স্টক ম্যানেজমেন্ট')

@php
    $num = fn($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
@endphp

@section('content')
<div x-data="stockApp()" class="max-w-3xl mx-auto pb-24">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800">📦 স্টক খাতা</h1>
            <p class="text-xs text-gray-500">এক ট্যাপে স্টক ইন / আউট — সব হিসাব হিস্ট্রিতে থাকবে</p>
        </div>
        <a href="{{ route('vendor.stock.history') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium whitespace-nowrap">হিস্ট্রি →</a>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
        <button type="button" @click="filter='all'" class="text-left bg-white rounded-xl border p-3" :class="filter==='all' ? 'border-indigo-400 ring-1 ring-indigo-300' : 'border-gray-100'">
            <div class="text-[11px] text-gray-500">মোট পণ্য</div>
            <div class="text-2xl font-bold text-gray-800" x-text="cards.length">{{ $summary['total'] }}</div>
        </button>
        <button type="button" @click="filter='low'" class="text-left bg-amber-50 rounded-xl border p-3" :class="filter==='low' ? 'border-amber-400 ring-1 ring-amber-300' : 'border-amber-100'">
            <div class="text-[11px] text-amber-700">স্টক কম</div>
            <div class="text-2xl font-bold text-amber-600" x-text="count('low_stock')">{{ $summary['low'] }}</div>
        </button>
        <button type="button" @click="filter='out'" class="text-left bg-red-50 rounded-xl border p-3" :class="filter==='out' ? 'border-red-400 ring-1 ring-red-300' : 'border-red-100'">
            <div class="text-[11px] text-red-700">স্টক শেষ</div>
            <div class="text-2xl font-bold text-red-600" x-text="count('out_of_stock')">{{ $summary['out'] }}</div>
        </button>
        <div class="bg-white rounded-xl border border-gray-100 p-3">
            <div class="text-[11px] text-gray-500">স্টক মূল্য (ক্রয়)</div>
            <div class="text-xl font-bold text-gray-800" x-text="'৳' + stockValue().toLocaleString('en-IN', {maximumFractionDigits: 0})">৳{{ number_format($summary['stock_value'], 0) }}</div>
        </div>
    </div>
    <div class="flex gap-2 text-xs text-gray-600 mb-4">
        <span class="bg-green-50 text-green-700 px-2 py-1 rounded-full">আজ স্টক ইন: {{ $summary['today_in'] }} বার</span>
        <span class="bg-red-50 text-red-700 px-2 py-1 rounded-full">আজ স্টক আউট: {{ $summary['today_out'] }} বার</span>
    </div>

    {{-- Search + add --}}
    <div class="sticky top-[57px] z-[5] bg-gray-100 pt-1 pb-3">
        <div class="flex gap-2">
            <div class="relative flex-1">
                <input type="search" x-model="q" placeholder="🔍 পণ্যের নাম / SKU খুঁজুন…"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
            </div>
            @if($canQuickAdd)
            <button type="button" @click="openAdd()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 rounded-xl text-sm font-semibold whitespace-nowrap">+ নতুন পণ্য</button>
            @endif
        </div>
        <div class="flex gap-1.5 mt-2 text-xs">
            <template x-for="f in [['all','সব'],['low','স্টক কম'],['out','স্টক শেষ']]" :key="f[0]">
                <button type="button" @click="filter=f[0]" x-text="f[1]"
                        class="px-3 py-1.5 rounded-full border"
                        :class="filter===f[0] ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-200'"></button>
            </template>
        </div>
    </div>

    {{-- Product cards --}}
    <div class="space-y-2">
        <template x-for="p in visible()" :key="p.id">
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden flex"
                 :class="flash === p.id ? 'ring-2 ring-green-400' : ''">
                <div class="w-1.5 flex-shrink-0"
                     :class="p.status==='out_of_stock' ? 'bg-red-500' : (p.status==='low_stock' ? 'bg-amber-400' : 'bg-green-500')"></div>
                <div class="flex-1 p-3 min-w-0">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-semibold text-gray-800 truncate" x-text="p.name"></div>
                            <div class="text-[11px] text-gray-400">
                                <span x-show="p.sku" x-text="'SKU: ' + p.sku + ' · '"></span>
                                <span x-show="p.threshold > 0" x-text="'সতর্কতা: ' + fmt(p.threshold) + ' ' + p.unit"></span>
                                <span x-show="!(p.threshold > 0)">সতর্কতা সেট নেই</span>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <div class="text-2xl font-bold leading-none"
                                 :class="p.status==='out_of_stock' ? 'text-red-600' : (p.status==='low_stock' ? 'text-amber-600' : 'text-gray-800')"
                                 x-text="fmt(p.onhand)"></div>
                            <div class="text-[11px] text-gray-500" x-text="p.unit"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-3">
                        <button type="button" @click="openMove(p, 'reduce')"
                                class="bg-red-50 hover:bg-red-100 text-red-700 font-semibold rounded-lg py-2 text-sm">− স্টক আউট</button>
                        <button type="button" @click="openMove(p, 'add')"
                                class="bg-green-50 hover:bg-green-100 text-green-700 font-semibold rounded-lg py-2 text-sm">+ স্টক ইন</button>
                        <button type="button" @click="openMove(p, 'set')"
                                class="bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-lg py-2 text-sm">⋯ আরও</button>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="visible().length === 0" class="bg-white rounded-xl border border-dashed border-gray-200 py-12 text-center text-gray-400 text-sm">
            <span x-show="cards.length === 0">এখনো কোনো পণ্য নেই।@if($canQuickAdd) "+ নতুন পণ্য" চাপুন।@endif</span>
            <span x-show="cards.length > 0">কোনো পণ্য মেলেনি।</span>
        </div>
    </div>

    {{-- Recent activity --}}
    @if($recent->isNotEmpty())
    <div class="mt-6">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-sm font-bold text-gray-700">সাম্প্রতিক লেনদেন</h2>
            <a href="{{ route('vendor.stock.history') }}" class="text-xs text-indigo-600">সব দেখুন</a>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 divide-y divide-gray-50">
            @foreach($recent as $m)
                <div class="flex items-center justify-between px-3 py-2 text-sm">
                    <div class="min-w-0">
                        <div class="text-gray-800 truncate">{{ $m->product?->name_bn ?: $m->product?->name_en ?: '—' }}</div>
                        <div class="text-[11px] text-gray-400">{{ $m->typeLabel() }} · {{ $m->created_at->diffForHumans() }}@if($m->note) · {{ \Illuminate\Support\Str::limit($m->note, 30) }}@endif</div>
                    </div>
                    <div class="text-right flex-shrink-0 ml-3">
                        <div class="font-semibold {{ $m->quantity >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $m->quantity >= 0 ? '+' : '−' }}{{ $num(abs($m->quantity)) }}</div>
                        <div class="text-[11px] text-gray-400">বাকি {{ $num($m->new_stock) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Stock move sheet ─────────────────────────────────────────── --}}
    <div x-show="sheet" x-cloak class="fixed inset-0 z-40 flex items-end sm:items-center justify-center" @keydown.escape.window="sheet=false">
        <div class="absolute inset-0 bg-black/50" @click="sheet=false"></div>
        <div class="relative bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-xl p-5 max-h-[92vh] overflow-y-auto">
            <div class="flex items-start justify-between mb-3">
                <div class="min-w-0">
                    <h3 class="text-lg font-bold text-gray-800 truncate" x-text="cur?.name"></h3>
                    <p class="text-sm text-gray-500">বর্তমান স্টক: <b x-text="fmt(cur?.onhand) + ' ' + (cur?.unit || '')"></b></p>
                </div>
                <button type="button" @click="sheet=false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
            </div>

            <div class="grid grid-cols-3 gap-1 bg-gray-100 rounded-xl p-1 mb-4 text-sm">
                <button type="button" @click="setMode('add')"    :class="mode==='add'    ? 'bg-green-600 text-white' : 'text-gray-600'" class="rounded-lg py-2 font-semibold">+ ইন</button>
                <button type="button" @click="setMode('reduce')" :class="mode==='reduce' ? 'bg-red-600 text-white'   : 'text-gray-600'" class="rounded-lg py-2 font-semibold">− আউট</button>
                <button type="button" @click="setMode('set')"    :class="mode==='set'    ? 'bg-gray-800 text-white'  : 'text-gray-600'" class="rounded-lg py-2 font-semibold">গণনা</button>
            </div>

            <form @submit.prevent="submitMove()" class="space-y-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1"
                           x-text="mode==='set' ? 'গুনে যা পেয়েছেন (মোট স্টক)' : (mode==='add' ? 'কত এসেছে?' : 'কত গেছে / বিক্রি হয়েছে?')"></label>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="bump(-1)" class="w-12 h-12 rounded-xl bg-gray-100 text-2xl font-bold text-gray-600">−</button>
                        <input type="number" inputmode="decimal" step="0.001" min="0" x-model="qty" x-ref="qty" required
                               class="flex-1 min-w-0 text-center text-3xl font-bold border-2 border-gray-200 rounded-xl py-2 focus:outline-none focus:border-indigo-500">
                        <button type="button" @click="bump(1)" class="w-12 h-12 rounded-xl bg-gray-100 text-2xl font-bold text-gray-600">+</button>
                    </div>
                    <div class="flex flex-wrap gap-1.5 mt-2" x-show="mode!=='set'">
                        <template x-for="n in [1, 5, 10, 25, 50, 100]" :key="n">
                            <button type="button" @click="bump(n)" class="px-3 py-1 rounded-full bg-gray-100 text-xs text-gray-700" x-text="'+' + n"></button>
                        </template>
                    </div>
                </div>

                <div class="rounded-xl px-4 py-3 text-sm flex items-center justify-between"
                     :class="preview() < 0 ? 'bg-red-50 text-red-700' : 'bg-indigo-50 text-indigo-800'">
                    <span>নতুন স্টক হবে</span>
                    <span class="font-bold text-lg" x-text="fmt(preview()) + ' ' + (cur?.unit || '')"></span>
                </div>

                <input type="text" x-model="note" maxlength="500"
                       :placeholder="mode==='add' ? 'নোট (যেমন: সাপ্লায়ার / চালান নং)' : (mode==='reduce' ? 'নোট (যেমন: বিক্রি / নষ্ট / ফেরত)' : 'নোট (যেমন: মাস শেষের গণনা)')"
                       class="w-full border rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">

                <p class="text-sm text-red-600" x-show="error" x-text="error"></p>

                <button type="submit" :disabled="busy"
                        class="w-full py-3 rounded-xl text-white font-bold text-base disabled:opacity-60"
                        :class="mode==='add' ? 'bg-green-600 hover:bg-green-700' : (mode==='reduce' ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-800 hover:bg-gray-900')"
                        x-text="busy ? 'সংরক্ষণ হচ্ছে…' : (mode==='add' ? 'স্টক ইন করুন' : (mode==='reduce' ? 'স্টক আউট করুন' : 'গণনা সেভ করুন'))"></button>
            </form>

            {{-- Low-stock alert level --}}
            <form @submit.prevent="submitThreshold()" class="mt-5 pt-4 border-t border-gray-100">
                <label class="block text-xs text-gray-500 mb-1">🔔 কম-স্টক সতর্কতা — স্টক এর নিচে নামলে জানাবে</label>
                <div class="flex gap-2">
                    <input type="number" inputmode="decimal" step="0.001" min="0" x-model="threshold"
                           class="flex-1 border rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
                    <button type="submit" :disabled="busy" class="px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold">সেট</button>
                </div>
            </form>

            <a :href="historyUrl + '?product_id=' + (cur?.id || '')" class="block text-center text-sm text-indigo-600 mt-4">এই পণ্যের হিস্ট্রি দেখুন →</a>
        </div>
    </div>

    {{-- ── Quick-add sheet ──────────────────────────────────────────── --}}
    @if($canQuickAdd)
    <div x-show="addSheet" x-cloak class="fixed inset-0 z-40 flex items-end sm:items-center justify-center" @keydown.escape.window="addSheet=false">
        <div class="absolute inset-0 bg-black/50" @click="addSheet=false"></div>
        <div class="relative bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-xl p-5 max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">নতুন পণ্য যোগ</h3>
                <button type="button" @click="addSheet=false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
            </div>
            <form @submit.prevent="submitAdd()" class="space-y-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">পণ্যের নাম *</label>
                    <input type="text" x-model="nw.name" x-ref="nwName" required maxlength="150" placeholder="যেমন: হলুদ গুঁড়া"
                           class="w-full border rounded-xl px-3 py-2.5 text-base focus:outline-none focus:ring-1 focus:ring-indigo-400">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">একক</label>
                    <div class="grid grid-cols-3 gap-1.5">
                        @foreach($units as $code => $label)
                            <button type="button" @click="nw.unit='{{ $code }}'"
                                    :class="nw.unit==='{{ $code }}' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-200'"
                                    class="border rounded-lg py-2 text-sm">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">শুরুর স্টক</label>
                        <input type="number" inputmode="decimal" step="0.001" min="0" x-model="nw.opening_stock" placeholder="0"
                               class="w-full border rounded-xl px-3 py-2.5 text-base focus:outline-none focus:ring-1 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">কম-স্টক সতর্কতা</label>
                        <input type="number" inputmode="decimal" step="0.001" min="0" x-model="nw.low_stock_threshold" placeholder="0"
                               class="w-full border rounded-xl px-3 py-2.5 text-base focus:outline-none focus:ring-1 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ক্রয় মূল্য (৳/একক)</label>
                        <input type="number" inputmode="decimal" step="0.01" min="0" x-model="nw.purchase_price"
                               class="w-full border rounded-xl px-3 py-2.5 text-base focus:outline-none focus:ring-1 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">বিক্রয় মূল্য (৳/একক)</label>
                        <input type="number" inputmode="decimal" step="0.01" min="0" x-model="nw.selling_price"
                               class="w-full border rounded-xl px-3 py-2.5 text-base focus:outline-none focus:ring-1 focus:ring-indigo-400">
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">SKU / কোড (ঐচ্ছিক)</label>
                    <input type="text" x-model="nw.sku" maxlength="100"
                           class="w-full border rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
                </div>
                <p class="text-[11px] text-gray-400">এখান থেকে যোগ করা পণ্য শুধু স্টকের হিসাবের জন্য — ওয়েবসাইটে দেখাবে না।</p>
                <p class="text-sm text-red-600" x-show="error" x-text="error"></p>
                <button type="submit" :disabled="busy" class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold disabled:opacity-60"
                        x-text="busy ? 'সংরক্ষণ হচ্ছে…' : 'পণ্য যোগ করুন'"></button>
            </form>
        </div>
    </div>
    @endif

    {{-- Toast --}}
    <div x-show="toast" x-cloak x-transition
         class="fixed bottom-5 left-1/2 -translate-x-1/2 z-50 bg-gray-900 text-white text-sm px-4 py-2.5 rounded-full shadow-lg"
         x-text="toast"></div>
</div>

@push('scripts')
<script>
function stockApp() {
    return {
        cards: @js($cards),
        q: '',
        filter: @js(in_array($filter, ['all', 'low', 'out'], true) ? $filter : 'all'),
        sheet: false, addSheet: false, busy: false, error: '', toast: '', flash: null,
        cur: null, mode: 'add', qty: '', note: '', threshold: '',
        nw: { name: '', unit: 'kg', opening_stock: '', low_stock_threshold: '', purchase_price: '', selling_price: '', sku: '' },
        historyUrl: @js(route('vendor.stock.history')),
        csrf: @js(csrf_token()),

        fmt(n) { n = Number(n || 0); return (Math.round((n + Number.EPSILON) * 1000) / 1000).toString(); },
        count(status) { return this.cards.filter(c => c.status === status).length; },
        stockValue() { return this.cards.reduce((s, c) => s + Math.max(0, c.onhand) * (c.price || 0), 0); },
        visible() {
            const q = this.q.trim().toLowerCase();
            return this.cards.filter(c => {
                if (this.filter === 'low' && c.status !== 'low_stock') return false;
                if (this.filter === 'out' && c.status !== 'out_of_stock') return false;
                if (!q) return true;
                return (c.name || '').toLowerCase().includes(q) || (c.sku || '').toLowerCase().includes(q);
            });
        },

        openMove(p, mode) {
            this.cur = p; this.error = ''; this.note = '';
            this.threshold = p.threshold > 0 ? this.fmt(p.threshold) : '';
            this.setMode(mode);
            this.sheet = true;
            this.$nextTick(() => this.$refs.qty && this.$refs.qty.focus());
        },
        setMode(m) { this.mode = m; this.qty = m === 'set' ? this.fmt(this.cur?.onhand) : ''; },
        bump(n) { this.qty = this.fmt(Math.max(0, Number(this.qty || 0) + n)); },
        preview() {
            const cur = Number(this.cur?.onhand || 0), q = Number(this.qty || 0);
            if (this.mode === 'add') return cur + q;
            if (this.mode === 'reduce') return cur - q;
            return q;
        },

        async post(url, body) {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify(body),
            });
            let data = {};
            try { data = await res.json(); } catch (e) {}
            if (!res.ok || data.ok === false) {
                const errs = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                throw new Error(data.message && !errs ? data.message : (errs || 'সমস্যা হয়েছে, আবার চেষ্টা করুন।'));
            }
            return data;
        },
        upsert(p) {
            const i = this.cards.findIndex(c => c.id === p.id);
            if (i >= 0) this.cards.splice(i, 1, p); else this.cards.unshift(p);
            this.flash = p.id; setTimeout(() => { if (this.flash === p.id) this.flash = null; }, 1500);
        },
        say(msg) { this.toast = msg; setTimeout(() => { if (this.toast === msg) this.toast = ''; }, 2500); },

        async submitMove() {
            if (this.busy || !this.cur) return;
            if (this.mode !== 'set' && !(Number(this.qty) > 0)) { this.error = 'পরিমাণ ০-এর বেশি দিন।'; return; }
            this.busy = true; this.error = '';
            try {
                const d = await this.post(@js(route('vendor.stock.adjust')), { product_id: this.cur.id, mode: this.mode, quantity: this.qty, note: this.note });
                this.upsert(d.product); this.sheet = false; this.say('✓ ' + d.message);
            } catch (e) { this.error = e.message; }
            this.busy = false;
        },
        async submitThreshold() {
            if (this.busy || !this.cur) return;
            this.busy = true; this.error = '';
            try {
                const d = await this.post(@js(route('vendor.stock.threshold')), { product_id: this.cur.id, low_stock_threshold: this.threshold || 0 });
                this.upsert(d.product); this.cur = d.product; this.say('✓ ' + d.message);
            } catch (e) { this.error = e.message; }
            this.busy = false;
        },

        openAdd() {
            this.error = ''; this.addSheet = true;
            this.$nextTick(() => this.$refs.nwName && this.$refs.nwName.focus());
        },
        async submitAdd() {
            if (this.busy) return;
            this.busy = true; this.error = '';
            try {
                const d = await this.post(@js(route('vendor.stock.quick-add')), this.nw);
                this.upsert(d.product); this.addSheet = false; this.say('✓ ' + d.message);
                this.nw = { name: '', unit: this.nw.unit, opening_stock: '', low_stock_threshold: '', purchase_price: '', selling_price: '', sku: '' };
                this.filter = 'all'; this.q = '';
            } catch (e) { this.error = e.message; }
            this.busy = false;
        },
    };
}
</script>
@endpush
@endsection
