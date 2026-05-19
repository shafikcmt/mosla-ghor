@extends('admin.layout')

@section('title', 'নতুন ডেলিভারি জোন')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.delivery-zones.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← জোন তালিকায় ফিরুন</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">নতুন ডেলিভারি জোন</h1>
</div>

<div class="bg-white rounded shadow">
    <form action="{{ route('admin.delivery-zones.store') }}" method="POST">
        @csrf
        @include('admin.delivery-zones._form', ['zone' => null])
        <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
            <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded text-sm font-medium hover:bg-gray-700 transition-colors">
                সংরক্ষণ করুন
            </button>
            <a href="{{ route('admin.delivery-zones.index') }}"
               class="bg-gray-100 text-gray-600 px-5 py-2 rounded text-sm hover:bg-gray-200 transition-colors">
                বাতিল
            </a>
        </div>
    </form>
</div>

@endsection
