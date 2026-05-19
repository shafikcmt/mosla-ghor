@extends('admin.layout')

@section('title', 'রিভিউ তালিকা')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">গ্রাহক রিভিউ</h1>
    <a href="{{ route('admin.reviews.create') }}"
       class="bg-gray-800 text-white text-sm px-4 py-2 rounded hover:bg-gray-700 transition-colors">
        + নতুন রিভিউ
    </a>
</div>

@if($reviews->isEmpty())
    <div class="bg-white rounded shadow px-6 py-10 text-center text-gray-400">
        কোনো রিভিউ নেই।
        <a href="{{ route('admin.reviews.create') }}" class="text-gray-700 underline ml-1">যোগ করুন</a>
    </div>
@else
<div class="bg-white rounded shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="border-b border-gray-200">
            <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">গ্রাহক</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-24">রেটিং</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">রিভিউ</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-16">ক্রম</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-24">স্ট্যাটাস</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide w-32">অ্যাকশন</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($reviews as $review)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3">
                    <div class="font-medium text-gray-800">{{ $review->customer_name }}</div>
                    @if($review->customer_location)
                        <div class="text-xs text-gray-400">{{ $review->customer_location }}</div>
                    @endif
                </td>
                <td class="px-5 py-3 text-center text-amber-500 text-base tracking-tight">
                    {{ str_repeat('★', $review->rating) }}<span class="text-gray-200">{{ str_repeat('★', 5 - $review->rating) }}</span>
                </td>
                <td class="px-5 py-3 text-gray-500 text-xs">{{ Str::limit($review->review_text, 80) }}</td>
                <td class="px-5 py-3 text-center text-gray-400">{{ $review->sort_order }}</td>
                <td class="px-5 py-3 text-center">
                    <form action="{{ route('admin.reviews.toggle', $review) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="px-2.5 py-1 rounded text-xs font-medium transition-colors
                                       {{ $review->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-600 hover:bg-red-200' }}">
                            {{ $review->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                        </button>
                    </form>
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.reviews.edit', $review) }}"
                           class="text-xs text-gray-600 hover:underline">সম্পাদনা</a>
                        <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST"
                              onsubmit="return confirm('রিভিউ মুছে ফেলবেন?')">
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
