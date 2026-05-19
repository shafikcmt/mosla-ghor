@extends('admin.layout')

@section('title', 'কম্বো সম্পাদনা: ' . $combo->name)

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.combos.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← কম্বো তালিকায় ফিরুন</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">সম্পাদনা: {{ $combo->name }}</h1>
</div>

<div class="bg-white rounded shadow">
    <form action="{{ route('admin.combos.update', $combo) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.combos._form', ['combo' => $combo])
        <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
            <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded text-sm font-medium hover:bg-gray-700 transition-colors">
                আপডেট করুন
            </button>
            <a href="{{ route('admin.combos.index') }}"
               class="bg-gray-100 text-gray-600 px-5 py-2 rounded text-sm hover:bg-gray-200 transition-colors">বাতিল</a>
        </div>
    </form>
</div>

@endsection
