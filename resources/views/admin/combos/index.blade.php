@extends('admin.layout')

@section('title', 'কম্বো তালিকা')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">ফিক্সড কম্বো</h1>
    <a href="{{ route('admin.combos.create') }}"
       class="bg-gray-800 text-white text-sm px-4 py-2 rounded hover:bg-gray-700 transition-colors">
        + নতুন কম্বো
    </a>
</div>

@if($combos->isEmpty())
    <div class="bg-white rounded shadow px-6 py-10 text-center text-gray-400">
        কোনো কম্বো নেই।
        <a href="{{ route('admin.combos.create') }}" class="text-gray-700 underline ml-1">যোগ করুন</a>
    </div>
@else
<div class="bg-white rounded shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="border-b border-gray-200">
            <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">নাম</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">পণ্য</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">বিক্রয় মূল্য</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">ক্রম</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">স্ট্যাটাস</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">অ্যাকশন</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($combos as $combo)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3">
                    <div class="font-semibold text-gray-800">{{ $combo->name }}</div>
                    @if($combo->badge_text)
                        <span class="text-[10px] text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">{{ $combo->badge_text }}</span>
                    @endif
                    <div class="text-xs text-gray-400 mt-0.5">{{ $combo->slug }}</div>
                </td>
                <td class="px-5 py-3 text-center text-gray-500">{{ $combo->items_count }}</td>
                <td class="px-5 py-3 text-right font-bold text-gray-800">৳{{ number_format($combo->sell_price, 0) }}</td>
                <td class="px-5 py-3 text-center text-gray-400">{{ $combo->sort_order }}</td>
                <td class="px-5 py-3 text-center">
                    <form action="{{ route('admin.combos.toggle', $combo) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="px-2.5 py-1 rounded text-xs font-medium {{ $combo->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-600 hover:bg-red-200' }} transition-colors">
                            {{ $combo->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                        </button>
                    </form>
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.combos.edit', $combo) }}"
                           class="text-xs text-gray-600 hover:underline">সম্পাদনা</a>
                        <form action="{{ route('admin.combos.destroy', $combo) }}" method="POST"
                              onsubmit="return confirm('কম্বো মুছে ফেলবেন?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:underline">মুছুন</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
