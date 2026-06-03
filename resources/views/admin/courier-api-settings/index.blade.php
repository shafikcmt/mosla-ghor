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
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">কুরিয়ার সিলেকশন মোড</label>
                <select name="courier_selection_mode" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    @foreach(\App\Models\CourierSetting::SELECTION_MODES as $val => $label)
                    <option value="{{ $val }}" {{ $settings->courier_selection_mode === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">“শুধু অ্যাডমিন” হলে ভেন্ডর কোনো কুরিয়ার সিলেক্ট করতে পারবে না।</p>
            </div>
            <div class="space-y-2.5 md:pt-5">
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="vendor_can_select_courier" value="1" {{ $settings->vendor_can_select_courier ? 'checked' : '' }} class="w-4 h-4 accent-[#14532d]">
                    ভেন্ডর কুরিয়ার সিলেক্ট করতে পারবে
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

        <form method="POST" action="{{ route('admin.courier-api-settings.update', $courier) }}" class="px-6 py-5">
            @csrf @method('PUT')

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
                    <input type="text" name="base_url" value="{{ old('base_url', $courier->base_url) }}"
                           placeholder="https://..."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                </div>

                @if($courier->supportsApi())
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">
                        API Key
                        <span class="text-gray-400 normal-case">(ফাঁকা রাখলে পরিবর্তন হবে না)</span>
                    </label>
                    <input type="text" name="api_key" autocomplete="off"
                           placeholder="{{ $courier->api_key ? 'বর্তমান: ' . $courier->maskedKey() : 'এখনো সেট করা হয়নি' }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">
                        Secret Key
                        <span class="text-gray-400 normal-case">(ফাঁকা রাখলে পরিবর্তন হবে না)</span>
                    </label>
                    <input type="password" name="api_secret" autocomplete="new-password"
                           placeholder="{{ $courier->api_secret ? 'বর্তমান: ' . $courier->maskedSecret() : 'এখনো সেট করা হয়নি' }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
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

        {{-- Test connection (API couriers only) --}}
        @if($courier->supportsApi())
        <div class="px-6 py-3 border-t border-gray-100 bg-gray-50 flex flex-wrap items-center justify-between gap-3">
            <div class="text-xs text-gray-500">
                @if($courier->courier_api_last_status === 'failed' && $courier->courier_api_last_error)
                    <span class="text-red-600">সর্বশেষ ত্রুটি: {{ \Illuminate\Support\Str::limit($courier->courier_api_last_error, 120) }}</span>
                @else
                    সংযোগ যাচাই করতে নিচের বোতামে ক্লিক করুন (credential সংরক্ষণ করার পর)।
                @endif
            </div>
            <form method="POST" action="{{ route('admin.courier-api-settings.test', $courier) }}">
                @csrf
                <button type="submit"
                        class="bg-blue-600 text-white text-xs font-semibold px-3 py-1.5 rounded hover:bg-blue-700 transition-colors">
                    🔌 Test Connection
                </button>
            </form>
        </div>
        @endif
    </div>
    @endforeach
</div>
@endsection
