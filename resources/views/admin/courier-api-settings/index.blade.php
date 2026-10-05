@extends('admin.layout')
@section('title', 'কুরিয়ার API সেটিং')

@php
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
    @php($configuration = $configurations[$courier->id])
    <div x-data="{ open:@js((string) request('courier', old('courier_id', old('courier_basic_id'))) === (string) $courier->id), tab:@js(request('tab', 'basic') === 'api' || old('courier_id') ? 'api' : 'basic'), replaceCreds:false }"
         class="bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col">

        {{-- Card header --}}
        <div class="px-5 py-4 border-b border-gray-100">
            <div class="flex items-start justify-between gap-2">
                <h4 class="font-semibold text-gray-800">{{ $courier->name }}</h4>
                <x-courier.badges :courier="$courier" :only="['status','type','configured']" />
            </div>
            @if($configuration && isset($configuration->fields['base_url']))
            <p class="text-xs text-gray-400 mt-2 font-mono truncate">{{ $courier->base_url ?: ($configuration->fields['base_url']['default'] ?? '—') }}</p>
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
            @if($configuration?->canTestConnection)
            <form method="POST" action="{{ route('admin.courier-api-settings.test', $courier) }}">
                @csrf
                <button class="border border-gray-200 text-gray-700 text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-gray-50">Test Connection</button>
            </form>
            @elseif(! $configuration)
            <span class="text-xs text-gray-400">ম্যানুয়াল কুরিয়ার — API নেই।</span>
            @endif
        </div>

        {{-- ── Manage slide-over ─────────────────────────────────────── --}}
        <div x-cloak x-show="open" class="fixed inset-0 z-40" role="dialog" aria-modal="true" aria-label="{{ $courier->name }} settings" @keydown.escape.window="open=false" x-transition.opacity>
            <div class="absolute inset-0 bg-black/40" @click="open=false"></div>
            <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white shadow-xl flex flex-col"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
                <div class="flex items-center justify-between px-5 py-4 border-b">
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ $courier->name }} — Manage</h3>
                        <x-courier.badges :courier="$courier" :only="['status','type','configured']" class="mt-1" />
                    </div>
                    <button @click="open=false" aria-label="Close courier settings" class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
                </div>

                <div class="flex gap-1 px-4 pt-3 border-b text-sm font-medium" role="tablist" aria-label="Courier settings">
                    @foreach(['basic' => 'Basic', 'api' => 'API Configuration'] as $key => $label)
                    <button type="button" role="tab" id="courier-{{ $courier->id }}-{{ $key }}-tab"
                            aria-controls="courier-{{ $courier->id }}-{{ $key }}" :aria-selected="tab==='{{ $key }}'"
                            @keydown.right.prevent="tab='api'; $el.nextElementSibling?.focus()" @keydown.left.prevent="tab='basic'; $el.previousElementSibling?.focus()"
                            @click="tab='{{ $key }}'" :class="tab==='{{ $key }}' ? 'border-[#14532d] text-[#14532d]' : 'border-transparent text-gray-500'"
                            class="px-3 py-3 border-b-2 whitespace-nowrap">{{ $label }}</button>
                    @endforeach
                </div>
                <div class="flex-1 overflow-y-auto">
                    <section x-show="tab==='basic'" id="courier-{{ $courier->id }}-basic" role="tabpanel" aria-labelledby="courier-{{ $courier->id }}-basic-tab" class="p-5">
                        @include('admin.couriers.manage-basic', ['courier' => $courier])
                    </section>
                    <section x-show="tab==='api'" id="courier-{{ $courier->id }}-api" role="tabpanel" aria-labelledby="courier-{{ $courier->id }}-api-tab" class="p-5 space-y-4">
                        @if(! $courier->supportsApi())
                        <p class="text-sm text-gray-600">API integration is not available for this provider. Use manual booking and enter the tracking code on the order.</p>
                        @else
                        @if($courier->courier_api_last_checked_at)
                        <p class="text-sm text-gray-600">Last API result: {{ $courier->courier_api_last_status }} · {{ $courier->courier_api_last_checked_at->format('Y-m-d H:i') }}</p>
                        @else
                        <p class="text-sm text-gray-500">Connection not tested for the current configuration.</p>
                        @endif
                        <form method="POST" action="{{ route('admin.courier-api-settings.update', $courier) }}" autocomplete="off" class="space-y-4">
                            @csrf @method('PUT')
                            <input type="hidden" name="courier_id" value="{{ $courier->id }}">
                            @if((string) old('courier_id') === (string) $courier->id && $errors->any())
                            <div class="text-sm text-red-700" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                            @endif
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="api_enabled" value="1" {{ $courier->api_enabled ? 'checked' : '' }} class="w-4 h-4 accent-[#14532d]">
                                Integration enabled
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="replace_api_credentials" value="1" x-model="replaceCreds" class="w-4 h-4 accent-[#14532d]">
                                Replace API credentials
                            </label>
                            @foreach($configuration->fields as $field => $schema)
                            <div>
                                <label for="courier-{{ $courier->id }}-{{ $field }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $schema['label'] }}</label>
                                @if($schema['secret'])
                                <input id="courier-{{ $courier->id }}-{{ $field }}" type="password" name="{{ $field }}" autocomplete="new-password"
                                       :disabled="!replaceCreds" placeholder="{{ filled($courier->{$field}) ? '••••••••' : 'Not configured' }}"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                @else
                                <select id="courier-{{ $courier->id }}-{{ $field }}" name="{{ $field === 'base_url' ? 'base_url_select' : $field }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    @foreach($schema['options'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($courier->{$field} ?: ($schema['default'] ?? null)) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if($courier->{$field} && ! array_key_exists($courier->{$field}, $schema['options']))
                                <p class="text-sm text-red-700">The saved endpoint is unsupported. Select an endpoint above and save before testing.</p>
                                @endif
                                @endif
                                @if(isset($schema['help']))<p class="text-xs text-gray-500 mt-1">{{ $schema['help'] }}</p>@endif
                            </div>
                            @endforeach
                            <p class="text-xs text-gray-500">Blank inputs keep existing credentials. Save settings before testing. Saving never books a shipment.</p>
                            <button class="bg-[#14532d] text-white text-sm px-5 py-2 rounded-lg hover:bg-[#0d3520]">Save Settings</button>
                        </form>
                        @if($configuration->canTestConnection)
                        <form method="POST" action="{{ route('admin.courier-api-settings.test', $courier) }}">
                            @csrf
                            <button class="border border-gray-300 text-gray-700 text-sm px-5 py-2 rounded-lg hover:bg-gray-50">Test Connection</button>
                        </form>
                        @endif
                        @if($diagnostics[$courier->id])
                        <details class="text-sm text-gray-600">
                            <summary class="cursor-pointer py-2">Advanced diagnostics</summary>
                            <div class="grid grid-cols-2 gap-2 mt-2">
                                @foreach(['dns' => 'DNS Test', 'ssl' => 'SSL Test', 'full' => 'Full Test'] as $type => $label)
                                <form method="POST" action="{{ route('admin.courier-api-settings.diagnose', $courier) }}">
                                    @csrf <input type="hidden" name="type" value="{{ $type }}">
                                    <button class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ $label }}</button>
                                </form>
                                @endforeach
                            </div>
                        </details>
                        @endif
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
