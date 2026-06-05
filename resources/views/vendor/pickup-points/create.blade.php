@extends('vendor.layout')
@section('title', 'নতুন পিকআপ পয়েন্ট')

@section('content')
<div class="mb-5">
    <a href="{{ route('vendor.pickup-points.index') }}" class="text-sm text-gray-500 hover:text-gray-800">← পিকআপ পয়েন্ট তালিকা</a>
</div>

<div class="bg-white rounded shadow p-6 max-w-2xl">
    <h2 class="text-base font-bold text-gray-800 mb-5">নতুন পিকআপ পয়েন্ট</h2>

    <form method="POST" action="{{ route('vendor.pickup-points.store') }}" class="space-y-4">
        @csrf
        @include('vendor.pickup-points._form')

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-[#4338ca] text-white text-sm px-5 py-2 rounded hover:bg-[#3730a3] transition-colors">সংরক্ষণ করুন</button>
            <a href="{{ route('vendor.pickup-points.index') }}" class="text-sm text-gray-500 hover:text-gray-800 px-5 py-2 border border-gray-300 rounded">বাতিল</a>
        </div>
    </form>
</div>
@endsection
