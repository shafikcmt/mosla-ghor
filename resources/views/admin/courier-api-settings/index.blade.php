@extends('admin.layout')
@section('title', 'কুরিয়ার API সেটিং')

@section('content')
<h2 class="text-lg font-bold text-gray-800 mb-1">কুরিয়ার API সেটিং</h2>
<p class="text-sm text-gray-500 mb-5">কুরিয়ার API credential, স্ট্যাটাস ও ভেন্ডর পারমিশন এখান থেকে নিয়ন্ত্রণ করুন। API Key / Secret কখনো ভেন্ডরকে দেখানো হয় না।</p>

{{-- ── Vendor courier permission settings ─────────────────────────── --}}
<div class="bg-white rounded shadow mb-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">ভেন্ডর কুরিয়ার পারমিশন</h3>
        <p class="text-xs text-gray-400 mt-0.5">ভেন্ডর/মার্চেন্ট কী কী করতে পারবে তা নির্ধারণ করুন।</p>
    </div>
    <form method="POST" action="{{ route('admin.courier-api-settings.permissions') }}" class="px-6 py-5">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">ভেন্ডর কুরিয়ার মোড</label>
                <select name="vendor_courier_mode" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    @foreach(\App\Models\CourierSetting::VENDOR_COURIER_MODES as $val => $label)
                    <option value="{{ $val }}" {{ $settings->mode() === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">
                    <b>শুধু অ্যাডমিন</b>: ভেন্ডর পার্সেল করতে পারবে না।
                    <b>রিকোয়েস্ট</b>: ভেন্ডর কুরিয়ার বেছে রিকোয়েস্ট দেবে, অ্যাডমিন পাঠাবে।
                    <b>নিজে পার্সেল</b>: ভেন্ডর admin API দিয়ে নিজেই পার্সেল করবে (credential দেখা যাবে না)।
                </p>
            </div>
            <div class="space-y-2.5 md:pt-5">
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="vendor_can_setup_pickup_address" value="1" {{ $settings->vendor_can_setup_pickup_address ? 'checked' : '' }} class="w-4 h-4 accent-[#14532d]">
                    ভেন্ডর নিজের পিকআপ অ্যাড্রেস সেটআপ করতে পারবে
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="vendor_can_select_courier" value="1" {{ $settings->vendor_can_select_courier ? 'checked' : '' }} class="w-4 h-4 accent-[#14532d]">
                    ভেন্ডর কুরিয়ার সিলেক্ট করতে পারবে
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="vendor_can_create_parcel" value="1" {{ $settings->vendor_can_create_parcel ? 'checked' : '' }} class="w-4 h-4 accent-[#14532d]">
                    ভেন্ডর নিজে পার্সেল তৈরি করতে পারবে (মোড: নিজে পার্সেল)
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="vendor_can_update_tracking" value="1" {{ $settings->vendor_can_update_tracking ? 'checked' : '' }} class="w-4 h-4 accent-[#14532d]">
                    ভেন্ডর ট্র্যাকিং নম্বর আপডেট করতে পারবে
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="vendor_can_mark_handover" value="1" {{ $settings->vendor_can_mark_handover ? 'checked' : '' }} class="w-4 h-4 accent-[#14532d]">
                    ভেন্ডর “কুরিয়ারে দেওয়া হয়েছে” চিহ্নিত করতে পারবে
                </label>
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="bg-gray-800 text-white text-sm px-4 py-2 rounded hover:bg-gray-700 transition-colors">পারমিশন সংরক্ষণ করুন</button>
        </div>
    </form>
</div>

{{-- ── Per-courier API cards ──────────────────────────────────────── --}}
<div class="space-y-6">
    @foreach($couriers as $courier)
    <div class="bg-white rounded shadow">
        <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="font-semibold text-gray-800">{{ $courier->name }}</h3>

                {{-- Status badge --}}
                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $courier->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}">
                    {{ $courier->status === 'active' ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                </span>

                {{-- API support / enabled badge --}}
                @if($courier->supportsApi())
                    <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $courier->api_enabled ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                        API {{ $courier->api_enabled ? 'চালু' : 'বন্ধ' }}
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700">ম্যানুয়াল</span>
                @endif

                {{-- Configured badge --}}
                @if($courier->supportsApi())
                    <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $courier->isConfigured() ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                        {{ $courier->isConfigured() ? 'Configured' : 'Not configured' }}
                    </span>
                @endif
            </div>

            {{-- Last API check result --}}
            @if($courier->supportsApi() && $courier->courier_api_last_checked_at)
            <div class="text-xs text-gray-400">
                সর্বশেষ চেক:
                <span class="font-medium {{ $courier->courier_api_last_status === 'success' ? 'text-green-600' : 'text-red-600' }}">
                    {{ $courier->courier_api_last_status === 'success' ? 'সফল' : 'ব্যর্থ' }}
                </span>
                · {{ $courier->courier_api_last_checked_at->format('d M Y, h:i A') }}
            </div>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.courier-api-settings.update', $courier) }}" class="px-6 py-5" autocomplete="off">
            @csrf @method('PUT')

            {{-- Decoy fields: absorb browser autofill so login email/password never lands in API Key/Secret. --}}
            <input type="text" name="fake_username" autocomplete="username" tabindex="-1" aria-hidden="true" class="hidden" style="display:none">
            <input type="password" name="fake_password" autocomplete="new-password" tabindex="-1" aria-hidden="true" class="hidden" style="display:none">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">স্ট্যাটাস</label>
                    <select name="status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                        <option value="active" {{ $courier->status === 'active' ? 'selected' : '' }}>সক্রিয়</option>
                        <option value="inactive" {{ $courier->status === 'inactive' ? 'selected' : '' }}>নিষ্ক্রিয়</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Base URL</label>
                    @if($courier->supportsApi())
                        @php
                            $known      = \App\Services\SteadfastService::KNOWN_BASE_URLS;
                            $currentUrl = $courier->base_url;
                            $isCustom   = $currentUrl && ! in_array($currentUrl, $known, true);
                        @endphp
                        <select name="base_url_select" data-courier="{{ $courier->id }}"
                                class="js-baseurl-select w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                            @foreach($known as $url)
                            <option value="{{ $url }}" {{ $currentUrl === $url ? 'selected' : '' }}>{{ $url }}</option>
                            @endforeach
                            <option value="custom" {{ $isCustom ? 'selected' : '' }}>কাস্টম URL…</option>
                        </select>
                        <input type="text" name="base_url_custom" data-courier="{{ $courier->id }}"
                               value="{{ $isCustom ? $currentUrl : '' }}"
                               placeholder="https://your-custom-endpoint/api/v1"
                               class="js-baseurl-custom mt-2 w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none {{ $isCustom ? '' : 'hidden' }}">
                    @else
                        <input type="text" name="base_url" value="{{ old('base_url', $courier->base_url) }}"
                               placeholder="https://..."
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                    @endif
                </div>

                @if($courier->supportsApi())
                {{-- Credential gate: fields stay locked (and unsubmitted) until the admin
                     opts in, so browser autofill cannot silently overwrite stored keys. --}}
                <div class="md:col-span-2 flex items-start gap-2 bg-gray-50 border border-gray-200 rounded px-3 py-2">
                    <input type="checkbox" id="replace_creds_{{ $courier->id }}" name="replace_api_credentials" value="1"
                           data-courier="{{ $courier->id }}"
                           class="mt-0.5 w-4 h-4 accent-[#14532d] js-replace-creds">
                    <label for="replace_creds_{{ $courier->id }}" class="text-sm text-gray-700 cursor-pointer">
                        API Key / Secret পরিবর্তন করব
                        <span class="block text-xs text-gray-400">
                            বর্তমান:
                            <span class="font-medium {{ $courier->isConfigured() ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $courier->isConfigured() ? 'Configured' : 'Not configured' }}
                            </span>
                            @if($courier->api_key) · Key: {{ $courier->maskedKey() }} @endif
                        </span>
                    </label>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">
                        API Key
                        <span class="text-gray-400 normal-case">(ফাঁকা রাখলে পরিবর্তন হবে না)</span>
                    </label>
                    <input type="text" name="api_key" autocomplete="new-password" readonly disabled
                           data-courier="{{ $courier->id }}"
                           placeholder="{{ $courier->api_key ? 'বর্তমান: ' . $courier->maskedKey() : 'এখনো সেট করা হয়নি' }}"
                           class="js-cred-field w-full border border-gray-300 rounded px-3 py-2 text-sm bg-gray-100 focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">
                        Secret Key
                        <span class="text-gray-400 normal-case">(ফাঁকা রাখলে পরিবর্তন হবে না)</span>
                    </label>
                    <input type="password" name="api_secret" autocomplete="new-password" readonly disabled
                           data-courier="{{ $courier->id }}"
                           placeholder="{{ $courier->api_secret ? 'বর্তমান: ' . $courier->maskedSecret() : 'এখনো সেট করা হয়নি' }}"
                           class="js-cred-field w-full border border-gray-300 rounded px-3 py-2 text-sm bg-gray-100 focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                </div>
                @endif
            </div>

            {{-- Warning when API on but not configured --}}
            @if($courier->supportsApi() && $courier->api_enabled && ! $courier->isConfigured())
            <div class="mb-4 text-xs bg-red-50 border border-red-200 text-red-700 rounded px-3 py-2">
                ⚠ API চালু আছে কিন্তু API Key / Secret Key দেওয়া নেই। অর্ডার পাঠানোর আগে credential দিন।
            </div>
            @endif

            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">নোট</label>
                <textarea name="notes" rows="2"
                          class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">{{ old('notes', $courier->notes) }}</textarea>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                @if($courier->supportsApi())
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="api_enabled" value="1"
                           {{ old('api_enabled', $courier->api_enabled) ? 'checked' : '' }}
                           class="w-4 h-4 accent-[#14532d]">
                    API সংযোগ সক্রিয় করুন
                </label>
                @else
                <span class="text-xs text-gray-400">এই কুরিয়ারের জন্য API ইন্টিগ্রেশন নেই — ম্যানুয়াল বুকিং।</span>
                @endif

                <button type="submit"
                        class="bg-[#14532d] text-white text-sm px-4 py-2 rounded hover:bg-[#0d3520] transition-colors">
                    সংরক্ষণ করুন
                </button>
            </div>
        </form>

        {{-- Diagnostics (API couriers only) --}}
        @if($courier->supportsApi())
        <div class="px-6 py-3 border-t border-gray-100 bg-gray-50 space-y-3">
            <div class="text-xs text-gray-500">
                @if($courier->courier_api_last_message)
                    <span class="{{ $courier->courier_api_last_status === 'success' ? 'text-green-600' : 'text-red-600' }}">
                        সর্বশেষ ফলাফল: {{ \Illuminate\Support\Str::limit($courier->courier_api_last_message, 160) }}
                    </span>
                @else
                    DNS / SSL / API ব্যালেন্স আলাদাভাবে অথবা একসাথে যাচাই করুন (credential সংরক্ষণ করার পর)।
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @foreach(['dns' => '🌐 DNS টেস্ট', 'ssl' => '🔒 SSL/cURL টেস্ট', 'balance' => '💰 API Balance', 'full' => '🔌 Full Test'] as $type => $label)
                <form method="POST" action="{{ route('admin.courier-api-settings.diagnose', $courier) }}">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    <button type="submit"
                            class="{{ $type === 'full' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-700 hover:bg-gray-800' }} text-white text-xs font-semibold px-3 py-1.5 rounded transition-colors">
                        {{ $label }}
                    </button>
                </form>
                @endforeach
            </div>

            {{-- Terminal help: run these from the server SSH/cPanel terminal --}}
            <details class="text-xs text-gray-500">
                <summary class="cursor-pointer text-indigo-600 hover:underline">টার্মিনাল কমান্ড (DNS/SSL ম্যানুয়াল চেক)</summary>
                <pre class="mt-2 bg-gray-900 text-gray-100 rounded p-3 overflow-x-auto leading-relaxed">php -r "echo gethostbyname('portal.steadfast.com.bd').PHP_EOL;"
curl -Iv https://portal.steadfast.com.bd/api/v1/get_balance
curl -Iv https://portal.packzy.com/api/v1/get_balance</pre>
                <p class="mt-1 text-gray-400">Local-এ resolve না হলে কিন্তু live server-এ হলে — সেটি local network/DNS issue. Server-এ DNS ঠিক কিন্তু SSL hostname mismatch হলে — Steadfast support থেকে সঠিক base URL confirm করুন।</p>
            </details>
        </div>
        @endif
    </div>
    @endforeach
</div>

<script>
    // Unlock API Key/Secret only when the admin ticks "I will change credentials".
    // Disabled fields are not submitted, so autofill cannot overwrite stored keys.
    document.querySelectorAll('.js-replace-creds').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var id = cb.dataset.courier;
            document.querySelectorAll('.js-cred-field[data-courier="' + id + '"]').forEach(function (f) {
                f.disabled = cb.checked ? false : true;
                f.readOnly = cb.checked ? false : true;
                f.classList.toggle('bg-gray-100', !cb.checked);
                if (!cb.checked) { f.value = ''; }
            });
        });
    });

    // Show the custom Base URL field only when "কাস্টম URL…" is chosen.
    document.querySelectorAll('.js-baseurl-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var id = sel.dataset.courier;
            var custom = document.querySelector('.js-baseurl-custom[data-courier="' + id + '"]');
            if (custom) { custom.classList.toggle('hidden', sel.value !== 'custom'); }
        });
    });
</script>
@endsection
