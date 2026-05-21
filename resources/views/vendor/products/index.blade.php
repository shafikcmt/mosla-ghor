@extends('vendor.layout')
@section('title', 'আমার পণ্য')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-bold text-gray-800">আমার পণ্য</h2>
        <p class="text-sm text-gray-500 mt-0.5">মোট {{ $products->total() }}টি পণ্য</p>
    </div>
    @if($vendor->isApproved())
    <a href="{{ route('vendor.products.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        নতুন পণ্য
    </a>
    @endif
</div>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    @if($products->isEmpty())
        <div class="px-6 py-16 text-center text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p class="text-sm">এখনো কোনো পণ্য নেই।</p>
            @if($vendor->isApproved())
            <a href="{{ route('vendor.products.create') }}" class="mt-3 inline-block text-indigo-600 text-sm hover:underline">প্রথম পণ্য যোগ করুন</a>
            @endif
        </div>
    @else
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">পণ্য</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase hidden md:table-cell">স্টক</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase hidden md:table-cell">অনুমোদন</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">অবস্থা</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 text-xs uppercase">কার্যক্রম</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($products as $product)
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        @if($product->main_image)
                        <img src="{{ asset($product->main_image) }}" alt="" class="w-9 h-9 rounded-lg object-cover flex-shrink-0">
                        @else
                        <div class="w-9 h-9 rounded-lg bg-gray-100 flex-shrink-0"></div>
                        @endif
                        <div>
                            <p class="font-medium text-gray-800">{{ $product->name_bn }}</p>
                            @if($product->name_en)<p class="text-xs text-gray-400">{{ $product->name_en }}</p>@endif
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 hidden md:table-cell text-gray-600">{{ $product->stock }} কেজি</td>
                <td class="px-4 py-3 hidden md:table-cell">
                    <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium
                        @if($product->approval_status === 'approved') bg-green-100 text-green-700
                        @elseif($product->approval_status === 'pending') bg-yellow-100 text-yellow-700
                        @else bg-red-100 text-red-700 @endif">
                        @if($product->approval_status === 'approved') অনুমোদিত
                        @elseif($product->approval_status === 'pending') পেন্ডিং
                        @else প্রত্যাখ্যাত @endif
                    </span>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $product->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('vendor.products.edit', $product) }}"
                       class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 border border-indigo-200 hover:border-indigo-400 rounded px-2.5 py-1 transition-colors">
                        সম্পাদনা
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($products->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $products->links() }}
    </div>
    @endif
    @endif
</div>
@endsection
