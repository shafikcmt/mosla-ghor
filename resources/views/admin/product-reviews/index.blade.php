@extends('admin.layout')

@section('title', 'পণ্য রিভিউ')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">পণ্য রিভিউ ম্যানেজমেন্ট</h1>
</div>

{{-- Filters --}}
<form method="GET" class="bg-white rounded shadow px-4 py-3 mb-5 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs text-gray-500 mb-1">পণ্য</label>
        <select name="product_id" class="border border-gray-200 rounded px-3 py-1.5 text-sm bg-white min-w-[200px]">
            <option value="">— সব পণ্য —</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>{{ $p->name_bn ?: $p->name_en }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">স্ট্যাটাস</label>
        <select name="status" class="border border-gray-200 rounded px-3 py-1.5 text-sm bg-white">
            <option value="">— সব —</option>
            <option value="pending" @selected(request('status')==='pending')>পেন্ডিং</option>
            <option value="approved" @selected(request('status')==='approved')>অনুমোদিত</option>
        </select>
    </div>
    <button type="submit" class="bg-gray-800 text-white text-sm px-4 py-1.5 rounded hover:bg-gray-700 transition-colors">ফিল্টার</button>
    @if(request('product_id') || request('status'))
        <a href="{{ route('admin.product-reviews.index') }}" class="text-xs text-gray-500 hover:underline">রিসেট</a>
    @endif
</form>

@if($reviews->isEmpty())
    <div class="bg-white rounded shadow px-6 py-10 text-center text-gray-400">কোনো রিভিউ নেই।</div>
@else
<div class="bg-white rounded shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="border-b border-gray-200">
            <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">পণ্য</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-24">রেটিং</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">গ্রাহক ও মতামত</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-32">তারিখ</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-24">স্ট্যাটাস</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide w-40">অ্যাকশন</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($reviews as $review)
            <tr class="hover:bg-gray-50 align-top">
                <td class="px-5 py-3">
                    @if($review->product)
                        <a href="{{ route('products.show', $review->product->slug) }}" target="_blank"
                           class="font-medium text-gray-800 hover:underline">{{ $review->product->name_bn ?: $review->product->name_en }}</a>
                    @else
                        <span class="text-gray-400">— মুছে ফেলা পণ্য —</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-center text-amber-500 text-base tracking-tight whitespace-nowrap">
                    {{ str_repeat('★', $review->rating) }}<span class="text-gray-200">{{ str_repeat('★', 5 - $review->rating) }}</span>
                </td>
                <td class="px-5 py-3">
                    <div class="font-medium text-gray-800">{{ $review->display_name }}</div>
                    @if($review->customer_contact)
                        <div class="text-[11px] text-gray-400">{{ $review->customer_contact }}</div>
                    @endif
                    @if($review->comment)
                        <div class="text-xs text-gray-500 mt-1">{{ Str::limit($review->comment, 120) }}</div>
                    @endif
                    @if($review->image)
                        <img src="{{ asset($review->image) }}" alt="" class="mt-2 w-14 h-14 rounded object-cover border border-gray-100">
                    @endif
                </td>
                <td class="px-5 py-3 text-xs text-gray-400 whitespace-nowrap">{{ $review->created_at?->format('d M Y') }}</td>
                <td class="px-5 py-3 text-center">
                    @if($review->is_approved)
                        <span class="px-2.5 py-1 rounded text-xs font-medium bg-green-100 text-green-700">অনুমোদিত</span>
                    @else
                        <span class="px-2.5 py-1 rounded text-xs font-medium bg-amber-100 text-amber-700">পেন্ডিং</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        @if($review->is_approved)
                            <form action="{{ route('admin.product-reviews.pending', $review) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs text-amber-600 hover:underline">পেন্ডিং</button>
                            </form>
                        @else
                            <form action="{{ route('admin.product-reviews.approve', $review) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs text-green-600 hover:underline">অনুমোদন</button>
                            </form>
                        @endif
                        <form action="{{ route('admin.product-reviews.destroy', $review) }}" method="POST"
                              onsubmit="return confirm('রিভিউ মুছে ফেলবেন?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:underline">রিজেক্ট/মুছুন</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $reviews->links() }}</div>
@endif

@endsection
