@extends('customer.layout')
@section('title', 'উইশলিস্ট')

@section('content')
<h1 class="text-xl font-bold text-gray-800 mb-5">উইশলিস্ট</h1>

@if($items->isEmpty())
<div class="bg-white rounded-xl border border-gray-100 p-16 text-center text-gray-400 shadow-sm">
    <p class="text-4xl mb-3">❤️</p>
    <p class="text-sm">উইশলিস্ট খালি।</p>
    <a href="/" class="mt-3 inline-block text-sm text-[#14532d] font-semibold hover:underline">পণ্য দেখুন →</a>
</div>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($items as $item)
    @if($item->product)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        @if($item->product->main_image)
        <img src="{{ asset($item->product->main_image) }}" alt="{{ $item->product->name_bn }}" class="w-full h-32 object-cover">
        @else
        <div class="w-full h-32 bg-gray-50 flex items-center justify-center">
            <span class="text-4xl">🌿</span>
        </div>
        @endif
        <div class="p-4">
            <h3 class="text-sm font-semibold text-gray-800 mb-1">{{ $item->product->name_bn }}</h3>
            @if($item->product->activePrices->isNotEmpty())
            <p class="text-sm text-[#14532d] font-bold mb-3">
                ৳{{ number_format($item->product->activePrices->first()->final_price, 0) }} থেকে
            </p>
            @endif
            <div class="flex gap-2">
                <a href="/#products" class="flex-1 text-center text-xs bg-[#14532d] hover:bg-[#0d3520] text-white py-1.5 rounded-lg transition-colors">
                    অর্ডার করুন
                </a>
                <form method="POST" action="{{ route('customer.wishlist.destroy', $item->product->id) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs border border-red-300 text-red-500 hover:bg-red-50 px-2 py-1.5 rounded-lg transition-colors">✕</button>
                </form>
            </div>
        </div>
    </div>
    @endif
    @endforeach
</div>
@if($items->hasPages())<div class="mt-4">{{ $items->links() }}</div>@endif
@endif
@endsection
