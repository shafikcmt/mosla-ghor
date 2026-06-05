@extends('admin.layout')
@section('title', 'কুরিয়ার ম্যানেজমেন্ট')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h2 class="text-lg font-bold text-gray-800">কুরিয়ার ম্যানেজমেন্ট</h2>
    <a href="{{ route('admin.couriers.create') }}"
       class="bg-[#14532d] text-white text-sm px-4 py-2 rounded hover:bg-[#0d3520] transition-colors">
        + নতুন কুরিয়ার
    </a>
</div>

<div class="bg-white rounded shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">নাম</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Slug</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">স্ট্যাটাস</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">টাইপ</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">ভেন্ডর</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">ডিফল্ট</th>
                <th class="text-right px-4 py-3 font-semibold text-gray-600">অ্যাকশন</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($couriers as $courier)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $courier->name }}</td>
                <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $courier->slug }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $courier->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $courier->status === 'active' ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    @if($courier->supportsApi())
                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $courier->api_enabled ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400' }}">
                            API {{ $courier->api_enabled ? 'চালু' : 'বন্ধ' }}
                        </span>
                    @else
                        <span class="px-2 py-1 rounded text-xs font-semibold bg-amber-100 text-amber-700">ম্যানুয়াল</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    @if($courier->vendor_allowed)
                        <span class="px-2 py-1 rounded text-xs font-semibold bg-emerald-100 text-emerald-700">অনুমোদিত</span>
                    @else
                        <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-400">বন্ধ</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    @if($courier->is_default)
                        <span class="px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-700">ডিফল্ট</span>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.couriers.edit', $courier) }}"
                           class="text-xs text-blue-600 hover:underline">সম্পাদনা</a>
                        @if($courier->supportsApi())
                        <a href="{{ route('admin.courier-api-settings.index') }}"
                           class="text-xs text-indigo-600 hover:underline">API সেটিং</a>
                        @endif
                        <form method="POST" action="{{ route('admin.couriers.toggle', $courier) }}">
                            @csrf
                            <button class="text-xs {{ $courier->status === 'active' ? 'text-orange-500' : 'text-green-600' }} hover:underline">
                                {{ $courier->status === 'active' ? 'নিষ্ক্রিয়' : 'সক্রিয়' }} করুন
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.couriers.destroy', $courier) }}"
                              onsubmit="return confirm('এই কুরিয়ার মুছে ফেলবেন?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-500 hover:underline">মুছুন</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-400">কোনো কুরিয়ার নেই।</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
