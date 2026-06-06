@extends('invoice.layout')
@section('title', 'পেমেন্ট গৃহীত')

@section('content')
<div class="card bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
    <div class="w-14 h-14 rounded-full bg-green-100 text-green-600 flex items-center justify-center mx-auto mb-4 text-2xl">✓</div>
    @if($already ?? false)
        <h1 class="text-lg font-bold text-gray-800 mb-1">এই ইনভয়েসে কোনো বাকি নেই</h1>
        <p class="text-sm text-gray-500">ইনভয়েস #{{ $order->order_number }} সম্পূর্ণ পরিশোধিত।</p>
    @else
        <h1 class="text-lg font-bold text-gray-800 mb-1">ধন্যবাদ!</h1>
        <p class="text-sm text-gray-500">আপনার পেমেন্ট তথ্য পাওয়া গেছে। দোকান যাচাই করে নিশ্চিত করবে।</p>
    @endif
    <a href="{{ route('invoice.show', $order->invoice_token) }}" class="inline-block mt-5 text-sm text-indigo-600 hover:underline">ইনভয়েসে ফিরুন →</a>
</div>
@endsection
