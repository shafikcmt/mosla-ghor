@extends('vendor.layout')
@section('title', 'আমার কম্বো')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-bold text-gray-800">আমার কম্বো</h2>
        <p class="text-sm text-gray-500 mt-0.5">মোট {{ $combos->count() }}টি কম্বো</p>
    </div>
    @if($vendor->isApproved())
    <a href="{{ route('vendor.combos.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        নতুন কম্বো
    </a>
    @endif
</div>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    @if($combos->isEmpty())
        <div class="px-6 py-16 text-center text-gray-400 text-sm">এখনো কোনো কম্বো নেই।</div>
    @else
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">কম্বো</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase hidden md:table-cell">ধরন</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase hidden md:table-cell">দাম</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">অবস্থা</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 text-xs uppercase">কার্যক্রম</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($combos as $combo)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $combo->name }}</p>
                    <p class="text-xs text-gray-400">{{ $combo->items_count }} আইটেম</p>
                </td>
                <td class="px-4 py-3 hidden md:table-cell">
                    <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $combo->sell_type === 'retail' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700' }}">
                        {{ $combo->sell_type === 'retail' ? 'খুচরা' : 'পাইকারি' }}
                    </span>
                </td>
                <td class="px-4 py-3 hidden md:table-cell font-mono font-semibold text-gray-800">৳{{ number_format($combo->sell_price, 0) }}</td>
                <td class="px-4 py-3">
                    <form method="POST" action="{{ route('vendor.combos.toggle', $combo) }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-block text-xs px-2 py-0.5 rounded-full font-medium cursor-pointer
                                    {{ $combo->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                            {{ $combo->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                        </button>
                    </form>
                </td>
                <td class="px-4 py-3 text-right flex items-center justify-end gap-2">
                    <a href="{{ route('vendor.combos.edit', $combo) }}"
                       class="text-xs text-indigo-600 hover:text-indigo-800 border border-indigo-200 rounded px-2.5 py-1">সম্পাদনা</a>
                    <form method="POST" action="{{ route('vendor.combos.destroy', $combo) }}"
                          onsubmit="return confirm('মুছে ফেলবেন?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700 border border-red-200 rounded px-2.5 py-1">মুছুন</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
