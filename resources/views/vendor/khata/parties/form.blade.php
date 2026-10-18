@extends('vendor.khata.layout')
@section('title', $party->exists ? 'পার্টি সম্পাদনা' : 'নতুন পার্টি')
@section('heading', $party->exists ? 'পার্টি সম্পাদনা' : 'নতুন পার্টি')
@section('back', $party->exists ? route('vendor.khata.parties.show', $party) : ($back ?: route('vendor.khata.parties.index')))

@section('content')
@php
    $inp  = 'w-full border border-gray-200 rounded-xl px-3 py-3 text-base bg-white focus:outline-none focus:ring-2 focus:ring-[#0f7a3e]';
    $ob   = (float) $party->opening_balance;
    $side = old('opening_side', $ob < 0 ? 'payable' : 'receivable');
    $more = old('opening_amount', $ob) || old('address', $party->address) || old('note', $party->note);
@endphp
<form method="POST" action="{{ $party->exists ? route('vendor.khata.parties.update', $party) : route('vendor.khata.parties.store') }}"
      class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 space-y-4 lg:max-w-2xl">
    @csrf
    @if($party->exists) @method('PUT') @endif
    @if($back)<input type="hidden" name="back" value="{{ $back }}">@endif

    <div class="grid grid-cols-2 gap-2">
        @foreach(\App\Models\Khata\KhataParty::TYPES as $k => $l)
        <label class="cursor-pointer">
            <input type="radio" name="type" value="{{ $k }}" class="peer sr-only" @checked(old('type', $party->type) === $k)>
            <span class="block text-center font-semibold py-2.5 rounded-xl border border-gray-200 text-gray-600 peer-checked:bg-[#0f7a3e] peer-checked:text-white peer-checked:border-[#0f7a3e]">{{ $l }}</span>
        </label>
        @endforeach
    </div>

    <div>
        <label class="block text-sm font-semibold mb-1">নাম <span class="text-red-500">*</span></label>
        <input name="name" value="{{ old('name', $party->name) }}" required maxlength="150" autocomplete="name" @unless($party->exists) autofocus @endunless class="{{ $inp }}" data-contact-name>
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1" for="party-phone">ফোন নম্বর</label>
        <div class="flex gap-2">
            <input id="party-phone" type="tel" name="phone" value="{{ old('phone', $party->phone) }}" inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX" class="{{ $inp }}" data-contact-phone>
            {{-- Shown only where the browser supports the Contact Picker (Android Chrome). --}}
            <button type="button" hidden data-contact-pick
                    class="shrink-0 px-3 rounded-xl border border-[#0f7a3e] text-[#0f7a3e] font-semibold text-sm hover:bg-green-50"
                    aria-label="ফোনের কন্টাক্ট থেকে নাম ও নম্বর নিন">📇 কন্টাক্ট</button>
        </div>
        <select hidden data-contact-numbers aria-label="কোন নম্বরটি নেবেন" class="{{ $inp }} mt-2"></select>
        <p hidden data-contact-msg class="text-xs text-gray-500 mt-1"></p>
    </div>

    <details class="group rounded-xl border border-gray-100 bg-gray-50" @if($more) open @endif>
        <summary class="cursor-pointer list-none px-3 py-2.5 text-sm font-semibold text-[#0f7a3e] flex items-center justify-between">
            আগের বাকি / ঠিকানা (ঐচ্ছিক)
            <span class="transition group-open:rotate-180">⌄</span>
        </summary>
        <div class="px-3 pb-3 space-y-3">
            <div>
                <label class="block text-sm font-semibold mb-1">আগের হিসাব</label>
                <input type="number" name="opening_amount" step="0.01" min="0" inputmode="decimal" placeholder="৳ 0" value="{{ old('opening_amount', $ob ? abs($ob) : '') }}" class="{{ $inp }}">
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="opening_side" value="receivable" class="peer sr-only" @checked($side === 'receivable')>
                        <span class="block text-center text-sm font-semibold py-2 rounded-xl border border-gray-200 bg-white peer-checked:bg-green-600 peer-checked:text-white peer-checked:border-green-600">পাওনা (সে দেবে)</span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="opening_side" value="payable" class="peer sr-only" @checked($side === 'payable')>
                        <span class="block text-center text-sm font-semibold py-2 rounded-xl border border-gray-200 bg-white peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600">বকেয়া (আমি দেবো)</span>
                    </label>
                </div>
                <input type="hidden" name="opening_date" value="{{ old('opening_date', optional($party->opening_date)->toDateString() ?? now()->toDateString()) }}">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">ঠিকানা</label>
                <input name="address" value="{{ old('address', $party->address) }}" maxlength="300" class="{{ $inp }}">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">নোট</label>
                <input name="note" value="{{ old('note', $party->note) }}" maxlength="500" class="{{ $inp }}">
            </div>
        </div>
    </details>

    <button class="w-full bg-[#0f7a3e] hover:bg-[#0c6533] text-white font-bold py-3.5 rounded-xl">{{ $party->exists ? 'সেভ করুন' : 'পার্টি যোগ করুন' }}</button>
</form>

@if($party->exists)
<form method="POST" action="{{ route('vendor.khata.parties.destroy', $party) }}" class="mt-4 text-center lg:max-w-2xl" onsubmit="return confirm('পার্টি মুছবেন?');">
    @csrf @method('DELETE')
    <button class="text-sm text-red-600 hover:underline">পার্টি মুছুন</button>
</form>
@endif
@endsection

@push('scripts')
<script>
// Pick a party from the phone's contacts (Contact Picker API — Android Chrome, HTTPS).
// The browser opens the phone's own contact list; nothing is read without the user's choice.
(function () {
    const btn = document.querySelector('[data-contact-pick]');
    if (!btn || !('contacts' in navigator) || !('ContactsManager' in window)) return;
    const nameEl = document.querySelector('[data-contact-name]');
    const phoneEl = document.querySelector('[data-contact-phone]');
    const list = document.querySelector('[data-contact-numbers]');
    const msg = document.querySelector('[data-contact-msg]');
    const bn = '০১২৩৪৫৬৭৮৯';
    const say = (t) => { msg.textContent = t; msg.hidden = !t; };
    // +880 1XXXXXXXXX / 8801… / Bangla digits → 01XXXXXXXXX
    const clean = (n) => {
        let s = String(n || '').replace(/[০-৯]/g, d => bn.indexOf(d)).replace(/[^\d+]/g, '');
        if (s.startsWith('+880')) s = '0' + s.slice(4);
        else if (s.startsWith('880')) s = '0' + s.slice(3);
        return s;
    };
    btn.hidden = false;
    btn.addEventListener('click', async () => {
        say('');
        try {
            const [c] = await navigator.contacts.select(['name', 'tel'], { multiple: false });
            if (!c) return;
            const name = (c.name || []).find(Boolean);
            const nums = [...new Set((c.tel || []).map(clean).filter(Boolean))];
            if (name) nameEl.value = name.trim().slice(0, 150);
            list.innerHTML = '';
            list.hidden = nums.length < 2;
            if (nums.length > 1) {
                nums.forEach(n => list.add(new Option(n, n)));
                say('এই কন্টাক্টে একাধিক নম্বর আছে — নিচ থেকে বেছে নিন।');
            } else if (!nums.length) {
                say('এই কন্টাক্টে কোনো নম্বর নেই।');
            }
            phoneEl.value = nums[0] || phoneEl.value;
        } catch (e) {
            say('কন্টাক্ট খোলা যায়নি — নম্বর হাতে লিখুন।');
        }
    });
    list.addEventListener('change', () => { phoneEl.value = list.value; });
})();
</script>
@endpush
