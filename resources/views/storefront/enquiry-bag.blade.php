@extends('storefront.layout')
@section('title', 'পাইকারি Enquiry Bag')

@php
    // Autofill from the logged-in customer profile (editable).
    $bc    = auth()->check() && auth()->user()->role === 'customer' ? auth()->user()->customer : null;
    $bName = $bc->name ?? (auth()->check() ? auth()->user()->name : '');
    $bPhone = $bc->mobile_number ?? '';
    $bAddr  = $bc->last_full_address ?? '';
@endphp

@section('content')

<nav class="text-xs text-gray-400 mb-4 flex flex-wrap items-center gap-1.5">
    <a href="/" class="hover:text-[#14532d]">হোম</a>
    <span>/</span>
    <a href="/?tab=wholesale#products" class="hover:text-[#14532d]">পাইকারি পণ্য</a>
    <span>/</span>
    <span class="text-gray-700 font-medium">Enquiry Bag</span>
</nav>

<h1 class="font-serif-bn text-2xl font-bold text-[#14532d] mb-1">পাইকারি Enquiry Bag</h1>
<p class="text-sm text-gray-500 mb-5">একাধিক পাইকারি পণ্য একসাথে enquiry পাঠান। MoslaMart team আপনাকে quote জানাবে।</p>

{{-- Empty state --}}
<div id="bag-empty" class="hidden bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
    <p class="text-gray-500 text-sm mb-4">আপনার Enquiry Bag খালি।</p>
    <a href="/?tab=wholesale#products" class="inline-block bg-[#14532d] text-white font-semibold text-sm px-5 py-2.5 rounded-xl hover:bg-[#0d3520] transition-colors">পাইকারি পণ্য দেখুন</a>
</div>

{{-- Bag + form --}}
<div id="bag-form-wrap" class="hidden grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Items --}}
    <div class="lg:col-span-2 space-y-3">
        <div id="bag-items" class="space-y-3"></div>
        <a href="/?tab=wholesale#products" class="inline-flex items-center gap-1 text-sm font-semibold text-[#14532d] hover:underline">+ আরও পণ্য যোগ করুন</a>
    </div>

    {{-- Enquiry form --}}
    <div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sticky top-20">
            <h2 class="text-sm font-bold text-gray-800 mb-3">আপনার তথ্য</h2>
            <form id="bag-form" action="{{ route('paykari-combo.enquiry.store') }}" method="POST" class="space-y-3">
                @csrf
                <div id="bag-items-hidden"></div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">নাম <span class="text-red-500">*</span></label>
                    <input type="text" name="customer_name" value="{{ old('customer_name', $bName) }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">ফোন / WhatsApp <span class="text-red-500">*</span></label>
                    <input type="tel" name="customer_phone" value="{{ old('customer_phone', $bPhone) }}" required placeholder="01XXXXXXXXX"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">ডেলিভারি ঠিকানা / এলাকা <span class="text-red-500">*</span></label>
                    <input type="text" name="delivery_location" value="{{ old('delivery_location', $bAddr) }}" required placeholder="যেমন: ঢাকা, চট্টগ্রাম"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">ব্যবসার ধরন <span class="text-gray-300">(ঐচ্ছিক)</span></label>
                    <select name="business_type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                        <option value="">— বেছে নিন —</option>
                        <option value="shop">শপ / দোকান</option>
                        <option value="restaurant">রেস্তোরাঁ</option>
                        <option value="dealer">ডিলার</option>
                        <option value="retailer">রিটেইলার</option>
                        <option value="other">অন্যান্য</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">বার্তা <span class="text-gray-300">(ঐচ্ছিক)</span></label>
                    <textarea name="message" rows="2" placeholder="বিশেষ প্রয়োজনীয়তা..."
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] resize-none">{{ old('message') }}</textarea>
                </div>

                <button type="submit" class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold text-sm py-3 rounded-xl transition-colors">
                    Enquiry পাঠান →
                </button>
                @guest
                <p class="text-[11px] text-gray-400 text-center">লগইন ছাড়াই enquiry পাঠাতে পারবেন।</p>
                @endguest
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function bagImg(it) {
        return it.image || ('https://placehold.co/80x80/f1f5f3/14532d?text=' + encodeURIComponent((it.name || '').slice(0, 6)));
    }
    function renderBag() {
        const items = msBagGet();
        const wrap  = document.getElementById('bag-items');
        const empty = document.getElementById('bag-empty');
        const formWrap = document.getElementById('bag-form-wrap');
        if (!items.length) {
            empty.classList.remove('hidden');
            formWrap.classList.add('hidden');
            if (wrap) wrap.innerHTML = '';
            return;
        }
        empty.classList.add('hidden');
        formWrap.classList.remove('hidden');
        const units = ['kg', 'bag', 'carton', 'piece'];
        wrap.innerHTML = items.map(function (it, i) {
            const opts = units.map(function (u) {
                return '<option value="' + u + '"' + (it.unit === u ? ' selected' : '') + '>' + u + '</option>';
            }).join('');
            return '' +
            '<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 flex items-center gap-3">' +
                '<img src="' + bagImg(it) + '" alt="" class="w-16 h-16 rounded-lg object-cover border border-gray-100 flex-shrink-0">' +
                '<div class="flex-1 min-w-0">' +
                    '<a href="/wholesale/products/' + it.slug + '" class="text-sm font-semibold text-gray-800 hover:underline truncate block">' + (it.name || '') + '</a>' +
                    '<div class="flex items-center gap-2 mt-2">' +
                        '<input type="number" min="0.1" step="0.1" value="' + it.quantity + '" onchange="bagSetQty(' + i + ', this.value)" class="w-20 border border-gray-200 rounded-lg px-2 py-1 text-sm">' +
                        '<select onchange="bagSetUnit(' + i + ', this.value)" class="border border-gray-200 rounded-lg px-2 py-1 text-sm bg-white">' + opts + '</select>' +
                    '</div>' +
                '</div>' +
                '<button type="button" onclick="bagRemove(' + i + ')" class="text-red-500 hover:text-red-700 text-sm font-semibold flex-shrink-0">মুছুন</button>' +
            '</div>';
        }).join('');
    }
    function bagSetQty(i, val) { const items = msBagGet(); if (items[i]) { items[i].quantity = Math.max(0.1, parseFloat(val) || 0.1); msBagSave(items); } }
    function bagSetUnit(i, val) { const items = msBagGet(); if (items[i]) { items[i].unit = val; msBagSave(items); } }
    function bagRemove(i) { const items = msBagGet(); items.splice(i, 1); msBagSave(items); renderBag(); }

    document.addEventListener('DOMContentLoaded', function () {
        renderBag();
        const form = document.getElementById('bag-form');
        if (form) form.addEventListener('submit', function (e) {
            const items = msBagGet();
            if (!items.length) { e.preventDefault(); return; }
            // Basic client-side required-field check before clearing the bag.
            const name = form.querySelector('[name="customer_name"]').value.trim();
            const phone = form.querySelector('[name="customer_phone"]').value.trim();
            const loc = form.querySelector('[name="delivery_location"]').value.trim();
            if (!name || !phone || !loc) { return; } // let HTML5 required handle it
            const hidden = document.getElementById('bag-items-hidden');
            hidden.innerHTML = items.map(function (it, i) {
                return '<input type="hidden" name="items[' + i + '][product_id]" value="' + it.product_id + '">' +
                       '<input type="hidden" name="items[' + i + '][quantity_kg]" value="' + it.quantity + '">' +
                       '<input type="hidden" name="items[' + i + '][quantity_unit]" value="' + (it.unit || 'kg') + '">';
            }).join('');
            // Items are now serialized into the form; clear the bag so it isn't resubmitted.
            try { localStorage.removeItem(MS_BAG_KEY); } catch (e) {}
        });
    });
</script>
@endsection
