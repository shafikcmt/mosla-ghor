@extends('vendor.layout')
@section('title', $combo->name . ' — সম্পাদনা')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('vendor.combos.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← তালিকায় ফিরুন</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">{{ $combo->name }}</h1>
</div>

<form action="{{ route('vendor.combos.update', $combo) }}" method="POST">
    @csrf @method('PUT')

    <div class="bg-white shadow rounded p-6 mb-5">
        @include('vendor.combos._form', ['combo' => $combo])
    </div>

    <div class="bg-white shadow rounded p-6 mb-5">
        <h2 class="text-base font-semibold text-gray-700 mb-4 pb-2 border-b">কম্বো আইটেম</h2>
        @include('vendor.combos._items', ['combo' => $combo, 'products' => $products])
    </div>

    <div class="flex gap-3 items-center">
        <button type="submit"
                class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 transition-colors text-sm font-medium">
            কম্বো আপডেট করুন
        </button>
        <a href="{{ route('vendor.combos.index') }}"
           class="bg-gray-100 text-gray-600 px-5 py-2 rounded hover:bg-gray-200 transition-colors text-sm">বাতিল</a>
        <form method="POST" action="{{ route('vendor.combos.destroy', $combo) }}"
              onsubmit="return confirm('সত্যিই মুছে ফেলবেন?')" class="ml-auto">
            @csrf @method('DELETE')
            <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded text-sm">কম্বো মুছুন</button>
        </form>
    </div>
</form>

@endsection
