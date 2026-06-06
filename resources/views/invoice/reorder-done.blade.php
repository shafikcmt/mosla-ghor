@extends('invoice.layout')
@section('title', 'অর্ডার পাঠানো হয়েছে')

@section('content')
<div class="card bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
    <div class="w-14 h-14 rounded-full bg-green-100 text-green-600 flex items-center justify-center mx-auto mb-4 text-2xl">✓</div>
    <h1 class="text-lg font-bold text-gray-800 mb-1">অর্ডার পাঠানো হয়েছে!</h1>
    <p class="text-sm text-gray-500 mb-2">আপনার নতুন অর্ডার নম্বর</p>
    <div class="font-mono text-base font-bold text-[#0f3d22] mb-5">{{ $order->order_number }}</div>
    <p class="text-sm text-gray-500">দোকান শীঘ্রই আপনার সাথে যোগাযোগ করবে।</p>
    <a href="{{ route('invoice.show', $order->invoice_token) }}" class="inline-block mt-5 text-sm text-indigo-600 hover:underline">নতুন ইনভয়েস দেখুন →</a>
</div>
@endsection
