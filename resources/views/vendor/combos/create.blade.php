@extends('vendor.layout')
@section('title', 'নতুন কম্বো')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('vendor.combos.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← তালিকায় ফিরুন</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">নতুন কম্বো তৈরি করুন</h1>
</div>

<form action="{{ route('vendor.combos.store') }}" method="POST">
    @csrf

    <div class="bg-white shadow rounded p-6 mb-5">
        @include('vendor.combos._form', ['combo' => null])
    </div>

    <div class="bg-white shadow rounded p-6 mb-5">
        <h2 class="text-base font-semibold text-gray-700 mb-4 pb-2 border-b">কম্বো আইটেম</h2>
        @include('vendor.combos._items', ['combo' => null, 'products' => $products])
    </div>

    <div class="flex gap-3">
        <button type="submit"
                class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 transition-colors text-sm font-medium">
            কম্বো সংরক্ষণ করুন
        </button>
        <a href="{{ route('vendor.combos.index') }}"
           class="bg-gray-100 text-gray-600 px-5 py-2 rounded hover:bg-gray-200 transition-colors text-sm">বাতিল</a>
    </div>
</form>

@endsection
