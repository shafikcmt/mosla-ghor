@extends('vendor.layout')
@section('title', 'পিকআপ পয়েন্ট')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-lg font-bold text-gray-800">পিকআপ পয়েন্ট</h2>
        <p class="text-xs text-gray-500 mt-0.5">কুরিয়ার পার্সেল তৈরির আগে অন্তত একটি ডিফল্ট পিকআপ পয়েন্ট দরকার।</p>
    </div>
    <a href="{{ route('vendor.pickup-points.create') }}"
       class="bg-[#4338ca] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#3730a3] transition-colors">
        + নতুন পিকআপ পয়েন্ট
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">নাম</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">যোগাযোগ</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">ঠিকানা</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">স্ট্যাটাস</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">ডিফল্ট</th>
                <th class="text-right px-4 py-3 font-semibold text-gray-600">অ্যাকশন</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($pickupPoints as $p)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $p->pickup_name }}</td>
                <td class="px-4 py-3 text-gray-600">
                    {{ $p->contact_person_name }}<br>
                    <span class="text-xs text-gray-400">{{ $p->phone }}</span>
                </td>
                <td class="px-4 py-3 text-gray-600 text-xs">
                    {{ $p->address }}, {{ $p->city }}, {{ $p->district }}
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $p->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $p->status === 'active' ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    @if($p->is_default)
                        <span class="px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-700">ডিফল্ট</span>
                    @else
                        <form method="POST" action="{{ route('vendor.pickup-points.default', $p) }}" class="inline">
                            @csrf
                            <button class="text-xs text-indigo-600 hover:underline">ডিফল্ট করুন</button>
                        </form>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('vendor.pickup-points.edit', $p) }}" class="text-xs text-blue-600 hover:underline">সম্পাদনা</a>
                        <form method="POST" action="{{ route('vendor.pickup-points.destroy', $p) }}"
                              onsubmit="return confirm('এই পিকআপ পয়েন্ট মুছে ফেলবেন?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-500 hover:underline">মুছুন</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">কোনো পিকআপ পয়েন্ট নেই। প্রথমটি যোগ করুন।</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
