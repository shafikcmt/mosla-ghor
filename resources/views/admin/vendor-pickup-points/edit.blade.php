@extends('admin.layout')
@section('title', 'ভেন্ডর পিকআপ পয়েন্ট সম্পাদনা')

@section('content')
<div class="mb-5">
    <a href="{{ route('admin.vendor-pickup-points.index') }}" class="text-sm text-gray-500 hover:text-gray-800">← পিকআপ পয়েন্ট তালিকা</a>
</div>

<div class="bg-white rounded shadow p-6 max-w-2xl">
    <h2 class="text-base font-bold text-gray-800 mb-5">পিকআপ পয়েন্ট সম্পাদনা: {{ $pickupPoint->pickup_name }}</h2>

    <form method="POST" action="{{ route('admin.vendor-pickup-points.update', $pickupPoint) }}" class="space-y-4">
        @csrf @method('PUT')
        @include('admin.vendor-pickup-points._form')

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-[#14532d] text-white text-sm px-5 py-2 rounded hover:bg-[#0d3520] transition-colors">আপডেট করুন</button>
            <a href="{{ route('admin.vendor-pickup-points.index') }}" class="text-sm text-gray-500 hover:text-gray-800 px-5 py-2 border border-gray-300 rounded">বাতিল</a>
        </div>
    </form>
</div>
@endsection
