@extends('admin.layout')
@section('title', 'কুরিয়ার API সেটিং')

@section('content')
<h2 class="text-lg font-bold text-gray-800 mb-5">কুরিয়ার API সেটিং</h2>

<div class="space-y-6">
    @foreach($couriers as $courier)
    <div class="bg-white rounded shadow">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h3 class="font-semibold text-gray-800">{{ $courier->name }}</h3>
                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $courier->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}">
                    {{ $courier->status === 'active' ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                </span>
                @if($courier->api_enabled)
                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700">API চালু</span>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('admin.courier-api-settings.update', $courier) }}" class="px-6 py-5">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">স্ট্যাটাস</label>
                    <select name="status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none">
                        <option value="active" {{ $courier->status === 'active' ? 'selected' : '' }}>সক্রিয়</option>
                        <option value="inactive" {{ $courier->status === 'inactive' ? 'selected' : '' }}>নিষ্ক্রিয়</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Base URL</label>
                    <input type="text" name="base_url" value="{{ $courier->base_url }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">API Key <span class="text-gray-400 normal-case">(ফাঁকা রাখলে পরিবর্তন হবে না)</span></label>
                    <input type="text" name="api_key" placeholder="••••••••••••••••"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">API Secret <span class="text-gray-400 normal-case">(ফাঁকা রাখলে পরিবর্তন হবে না)</span></label>
                    <input type="password" name="api_secret" placeholder="••••••••••••••••"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">নোট</label>
                <textarea name="notes" rows="2"
                          class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">{{ $courier->notes }}</textarea>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="api_enabled" value="1"
                           {{ $courier->api_enabled ? 'checked' : '' }}
                           class="w-4 h-4 accent-[#14532d]">
                    API সংযোগ সক্রিয় করুন
                </label>
                <button type="submit"
                        class="bg-[#14532d] text-white text-sm px-4 py-2 rounded hover:bg-[#0d3520] transition-colors">
                    সংরক্ষণ করুন
                </button>
            </div>
        </form>
    </div>
    @endforeach
</div>
@endsection
