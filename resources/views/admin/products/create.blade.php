@extends('admin.layout')

@section('title', 'নতুন পণ্য')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.products.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← তালিকায় ফিরুন</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">নতুন পণ্য যোগ করুন</h1>
</div>

<form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="bg-white shadow rounded p-6">
        @include('admin.products._form', ['product' => null])
    </div>

    <div class="mt-4 flex gap-3">
        <button type="submit"
                class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition-colors text-sm font-medium">
            পণ্য সংরক্ষণ করুন
        </button>
        <a href="{{ route('admin.products.index') }}"
           class="bg-gray-100 text-gray-600 px-5 py-2 rounded hover:bg-gray-200 transition-colors text-sm">
            বাতিল
        </a>
    </div>
</form>

@endsection
