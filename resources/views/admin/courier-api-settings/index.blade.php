@extends('admin.layout')
@section('title', 'কুরিয়ার API সেটিং')

@php
    $known = \App\Services\SteadfastService::KNOWN_BASE_URLS;
    $mode  = $settings->mode();
@endphp

@section('content')
<div class="mb-4">
    <h2 class="text-lg font-bold text-gray-800">কুরিয়ার API সেটিং ও কন্ট্রোল</h2>
    <p class="text-xs text-gray-500 mt-0.5">API credential, ডায়াগনস্টিক ও ভেন্ডর পারমিশন — সব এক জায়গায়। API Key / Secret কখনো ভেন্ডরকে দেখানো হয় না।</p>
</div>

{{-- ── Easy process stepper ───────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
    <div class="flex flex-col md:flex-row md:items-center gap-3 md:gap-0">
        @foreach([
            '১' => 'কুরিয়ার Active করুন',
            '২' => 'API / Manual টাইপ ঠিক করুন',
            '৩' => 'Base URL ও Credential দিন',
            '৪' => 'Test Connection করুন',
            '৫' => 'Vendor Permission সেট করুন',
            '৬' => 'Order Parcel ব্যবহার করুন',
        ] as $n => $label)
        <div class="flex items-center gap-2 md:flex-1">
            <span class="flex-shrink-0 w-7 h-7 rounded-full bg-[#14532d] text-white text-xs font-bold flex items-center justify-center">{{ $n }}</span>
            <span class="text-xs text-gray-600 leading-tight">{{ $label }}</span>
            @if(! $loop->last)<div class="hidden md:block flex-1 h-px bg-gray-200 mx-2"></div>@endif
        </div>
        @endforeach
    </div>
</div>

{{-- ── Vendor courier control ─────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800 text-sm">Vendor Courier Control</h3>
        <p class="text-xs text-gray-400 mt-0.5">ভেন্ডর/মার্চেন্ট কী কী করতে পারবে তা নির্ধারণ করুন।</p>
    </div>
    <form method="POST" action="{{ route('admin.courier-api-settings.permissions') }}" class="px-5 py-5">
        @csrf

        {{-- Mode choice cards --}}
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">কুরিয়ার সিলেকশন মোড</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
            @foreach([
                'admin_only'         => ['🛡️', 'শুধু অ্যাডমিন', 'ভেন্ডর পার্সেল করতে পারবে না। অ্যাডমিন কুরিয়ার সামলাবেন।'],
                'vendor_can_request' => ['📝', 'ভেন্ডর রিকোয়েস্ট', 'ভেন্ডর কুরিয়ার বেছে রিকোয়েস্ট দেবে, অ্যাডমিন পাঠাবেন।'],
                'vendor_can_parcel'  => ['🚚', 'ভেন্ডর নিজে পার্সেল', 'ভেন্ডর admin API দিয়ে নিজেই পার্সেল করবে (credential দেখা যাবে না)।'],
            ] as $val => $info)
            <label class="cursor-pointer">
                <input type="radio" name="vendor_courier_mode" value="{{ $val }}" class="peer sr-only" {{ $mode === $val ? 'checked' : '' }}>
                <div class="h-full border-2 border-gray-200 rounded-xl p-3 transition-colors peer-checked:border-[#14532d] peer-checked:bg-green-50 hover:border-gray-300">
                    <div class="text-xl mb-1">{{ $info[0] }}</div>
                    <div class="text-sm font-semibold text-gray-800">{{ $info[1] }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $info[2] }}</div>
                </div>
            </label>
            @endforeach
        </div>

        {{-- Permission toggles --}}
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">পারমিশন</p>
        <div class="divide-y divide-gray-100 border border-gray-100 rounded-lg px-4 mb-4">
            <x-ui.toggle-row name="vendor_can_setup_pickup_address" label="ভেন্ডর পিকআপ অ্যাড্রেস সেটআপ করতে পারবে"
                             help="নিজের পিকআপ পয়েন্ট তৈরি/সম্পাদনা।" :checked="$settings->vendor_can_setup_pickup_address" />
            <x-ui.toggle-row name="vendor_can_select_courier" label="ভেন্ডর কুরিয়ার সিলেক্ট করতে পারবে"
                             help="অ্যাডমিন-অনুমোদিত কুরিয়ার থেকে।" :checked="$settings->vendor_can_select_courier" />
            <x-ui.toggle-row name="vendor_can_create_parcel" label="ভেন্ডর নিজে পার্সেল তৈরি করতে পারবে"
                             help="শুধু “নিজে পার্সেল” মোডে কার্যকর।" :checked="$settings->vendor_can_create_parcel" />
            <x-ui.toggle-row name="vendor_can_update_tracking" label="ভেন্ডর ট্র্যাকিং নম্বর আপডেট করতে পারবে"
                             :checked="$settings->vendor_can_update_tracking" />
            <x-ui.toggle-row name="vendor_can_mark_handover" label="ভেন্ডর “কুরিয়ারে দেওয়া হয়েছে” চিহ্নিত করতে পারবে"
                             :checked="$settings->vendor_can_mark_handover" />
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-gray-800 text-white text-sm px-5 py-2 rounded-lg hover:bg-gray-700 transition-colors">পারমিশন সংরক্ষণ করুন</button>
        </div>
    </form>
</div>

{{-- ── Courier cards ──────────────────────────────────────────────── --}}
<h3 class="font-semibold text-gray-800 text-sm mb-3">কুরিয়ারসমূহ</h3>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    @foreach($couriers as $courier)
    @php
        $currentUrl = $courier->base_url;
        $isCustom   = $currentUrl && ! in_array($currentUrl, $known, true);
        $baseSel    = $isCustom ? 'custom' : ($currentUrl ?: ($known[0] ?? ''));
    @endphp
    <div x-data="{ open:false, tab:'basic', replaceCreds:false, baseSel:@js($baseSel) }"
         class="bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col">

        {{-- Card header --}}
        <div class="px-5 py-4 border-b border-gray-100">
            <div class="flex items-start justify-between gap-2">
                <h4 class="font-semibold text-gray-800">{{ $courier->name }}</h4>
                <x-courier.badges :courier="$courier" :only="['status','type','configured']" />
            </div>
            @if($courier->supportsApi())
            <p class="text-xs text-gray-400 mt-2 font-mono truncate">{{ $courier->base_url ?: ($known[0] ?? '—') }}</p>
            @endif
        </div>

        {{-- Last test result --}}
        @if($courier->supportsApi() && $courier->courier_api_last_message)
        <div class="px-5 py-2.5 text-xs border-b border-gray-50
            {{ $courier->courier_api_last_status === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
            <div class="flex items-start gap-1.5">
                <span>{{ $courier->courier_api_last_status === 'success' ? '✓' : '✗' }}</span>
                <span class="flex-1">{{ \Illuminate\Support\Str::limit($courier->courier_api_last_message, 120) }}</span>
            </div>
            @if($courier->courier_api_last_error)
            <details class="mt-1"><summary class="cursor-pointer text-[11px] opacity-70">টেকনিক্যাল ডিটেইল</summary>
                <pre class="mt-1 text-[10px] whitespace-pre-wrap opacity-80">{{ \Illuminate\Support\Str::limit($courier->courier_api_last_error, 300) }}</pre>
            </details>
            @endif
        </div>
        @endif

        {{-- Card actions --}}
        <div class="px-5 py-3 mt-auto flex items-center gap-2">
            <button @click="open=true; tab='basic'"
                    class="bg-[#14532d] text-white text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-[#0d3520]">Manage</button>
            @if($courier->supportsApi())
            <form method="POST" action="{{ route('admin.courier-api-settings.diagnose', $courier) }}">
                @csrf <input type="hidden" name="type" value="full">
                <button class="border border-gray-200 text-gray-700 text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-gray-50">🔌 Test</button>
            </form>
            <button @click="open=true; tab='diag'"
                    class="border border-gray-200 text-gray-700 text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-gray-50">Diagnostics</button>
            @else
            <span class="text-xs text-gray-400">ম্যানুয়াল কুরিয়ার — API নেই।</span>
            @endif
        </div>

        {{-- ── Manage slide-over ─────────────────────────────────────── --}}
        <div x-cloak x-show="open" class="fixed inset-0 z-40" x-transition.opacity>
            <div class="absolute inset-0 bg-black/40" @click="open=false"></div>
            <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white shadow-xl flex flex-col"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
                <div class="flex items-center justify-between px-5 py-4 border-b">
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ $courier->name }} — Manage</h3>
                        <x-courier.badges :courier="$courier" :only="['status','type','configured']" class="mt-1" />
                    </div>
                    <button @click="open=false" class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
                </div>

                {{-- Tabs --}}
                <div class="flex gap-1 px-4 pt-3 border-b text-xs font-medium overflow-x-auto">
                    @php
                        $tabs = ['basic' => 'Basic'];
                        if ($courier->supportsApi()) { $tabs['credentials'] = 'Credentials'; $tabs['diag'] = 'Diagnostics'; }
                        $tabs['notes'] = 'Notes';
                    @endphp
                    @foreach($tabs as $key => $label)
                    <button type="button" @click="tab='{{ $key }}'"
                            :class="tab==='{{ $key }}' ? 'border-[#14532d] text-[#14532d]' : 'border-transparent text-gray-500'"
                            class="px-3 py-2 border-b-2 -mb-px whitespace-nowrap">{{ $label }}</button>
                    @endforeach
                </div>

                <div class="flex-1 overflow-y-auto">
                    {{-- Settings form: basic / credentials / notes --}}
                    <form method="POST" action="{{ route('admin.courier-api-settings.update', $courier) }}" autocomplete="off"
                          x-show="tab==='basic' || tab==='credentials' || tab==='notes'" class="p-5 space-y-4">
                        @csrf @method('PUT')
                        <input type="text" name="fake_username" autocomplete="username" tabindex="-1" aria-hidden="true" style="display:none">
                        <input type="password" name="fake_password" autocomplete="new-password" tabindex="-1" aria-hidden="true" style="display:none">

                        {{-- BASIC --}}
                        <div x-show="tab==='basic'" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">স্ট্যাটাস</label>
                                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                                    <option value="active" {{ $courier->status === 'active' ? 'selected' : '' }}>সক্রিয়</option>
                                    <option value="inactive" {{ $courier->status === 'inactive' ? 'selected' : '' }}>নিষ্ক্রিয়</option>
                                </select>
                            </div>
                            @if($courier->supportsApi())
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="api_enabled" value="1" {{ old('api_enabled', $courier->api_enabled) ? 'checked' : '' }} class="w-4 h-4 accent-[#14532d]">
                                API সংযোগ সক্রিয় করুন
                            </label>
                            @endif
                            <div class="text-xs text-gray-400 bg-gray-50 rounded-lg p-3">
                                ভেন্ডর অ্যাক্সেস (vendor allowed) ও বেসিক তথ্য
                                <a href="{{ route('admin.couriers.index') }}" class="text-indigo-600 hover:underline">কুরিয়ার ম্যানেজমেন্ট</a> পেজ থেকে।
                            </div>
                        </div>

                        {{-- CREDENTIALS --}}
                        @if($courier->supportsApi())
                        <div x-show="tab==='credentials'" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Base URL</label>
                                <select name="base_url_select" x-model="baseSel"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                                    @foreach($known as $url)
                                    <option value="{{ $url }}">{{ $url }}</option>
                                    @endforeach
                                    <option value="custom">কাস্টম URL…</option>
                                </select>
                                <input type="text" name="base_url_custom" x-show="baseSel==='custom'"
                                       value="{{ $isCustom ? $currentUrl : '' }}" placeholder="https://your-endpoint/api/v1"
                                       class="mt-2 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                            </div>

                            <div class="flex items-start gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                                <input type="checkbox" name="replace_api_credentials" value="1" x-model="replaceCreds" class="mt-0.5 w-4 h-4 accent-[#14532d]">
                                <div class="text-sm text-gray-700">
                                    আমি API Key / Secret পরিবর্তন করতে চাই
                                    <span class="block text-xs text-gray-400">
                                        বর্তমান:
                                        <span class="font-medium {{ $courier->isConfigured() ? 'text-emerald-600' : 'text-red-600' }}">
                                            {{ $courier->isConfigured() ? 'Configured' : 'Not configured' }}</span>
                                        @if($courier->api_key) · Key: {{ $courier->maskedKey() }} @endif
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">API Key <span class="normal-case text-gray-400">(ফাঁকা রাখলে পরিবর্তন হবে না)</span></label>
                                <input type="text" name="api_key" autocomplete="new-password" :disabled="!replaceCreds" :readonly="!replaceCreds"
                                       :class="replaceCreds ? '' : 'bg-gray-100'"
                                       placeholder="{{ $courier->api_key ? 'বর্তমান: ' . $courier->maskedKey() : 'এখনো সেট করা হয়নি' }}"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Secret Key <span class="normal-case text-gray-400">(ফাঁকা রাখলে পরিবর্তন হবে না)</span></label>
                                <input type="password" name="api_secret" autocomplete="new-password" :disabled="!replaceCreds" :readonly="!replaceCreds"
                                       :class="replaceCreds ? '' : 'bg-gray-100'"
                                       placeholder="{{ $courier->api_secret ? 'বর্তমান: ' . $courier->maskedSecret() : 'এখনো সেট করা হয়নি' }}"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                            </div>
                            @if($courier->api_enabled && ! $courier->isConfigured())
                            <div class="text-xs bg-red-50 border border-red-200 text-red-700 rounded-lg px-3 py-2">
                                ⚠ API চালু আছে কিন্তু credential নেই। অর্ডার পাঠানোর আগে credential দিন।
                            </div>
                            @endif
                        </div>
                        @endif

                        {{-- NOTES --}}
                        <div x-show="tab==='notes'" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">নোট</label>
                                <textarea name="notes" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">{{ old('notes', $courier->notes) }}</textarea>
                            </div>
                        </div>

                        <div class="pt-2 border-t flex gap-2" x-show="tab!=='diag'">
                            <button type="submit" class="bg-[#14532d] text-white text-sm px-5 py-2 rounded-lg hover:bg-[#0d3520]">সংরক্ষণ করুন</button>
                            <button type="button" @click="open=false" class="text-sm text-gray-500 px-5 py-2 border border-gray-300 rounded-lg">বন্ধ করুন</button>
                        </div>
                    </form>

                    {{-- DIAGNOSTICS (separate forms, not nested) --}}
                    @if($courier->supportsApi())
                    <div x-show="tab==='diag'" class="p-5 space-y-4">
                        <p class="text-xs text-gray-500">DNS / SSL / API ব্যালেন্স আলাদাভাবে অথবা একসাথে যাচাই করুন। ফলাফল উপরে দেখানো হবে।</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach(['dns' => '🌐 DNS Test', 'ssl' => '🔒 SSL / cURL', 'balance' => '💰 API Balance', 'full' => '🔌 Full Test'] as $type => $label)
                            <form method="POST" action="{{ route('admin.courier-api-settings.diagnose', $courier) }}">
                                @csrf <input type="hidden" name="type" value="{{ $type }}">
                                <button class="w-full {{ $type === 'full' ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }} text-xs font-semibold px-3 py-2 rounded-lg transition-colors">{{ $label }}</button>
                            </form>
                            @endforeach
                        </div>
                        <details class="text-xs text-gray-500">
                            <summary class="cursor-pointer text-indigo-600 hover:underline">টার্মিনাল কমান্ড (ম্যানুয়াল চেক)</summary>
                            <pre class="mt-2 bg-gray-900 text-gray-100 rounded-lg p-3 overflow-x-auto leading-relaxed text-[11px]">php -r "echo gethostbyname('portal.steadfast.com.bd').PHP_EOL;"
curl -Iv https://portal.steadfast.com.bd/api/v1/get_balance
curl -Iv https://portal.packzy.com/api/v1/get_balance</pre>
                            <p class="mt-1 text-gray-400">Local-এ resolve না হলে কিন্তু live-এ হলে → local DNS issue। Live-এ DNS ঠিক কিন্তু SSL mismatch → Steadfast support থেকে সঠিক base URL confirm করুন।</p>
                        </details>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
