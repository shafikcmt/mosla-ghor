@extends('admin.layout')

@section('title', 'ক্যাটাগরি সম্পাদনা')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.categories.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← ক্যাটাগরি তালিকায় ফিরুন</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">ক্যাটাগরি সম্পাদনা</h1>
</div>

<div class="bg-white rounded shadow max-w-3xl">
    <form action="{{ route('admin.categories.update', $category) }}" method="POST">
        @csrf @method('PUT')
        @include('admin.categories._form', ['category' => $category])
        <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
            <button type="submit"
                    class="bg-[#14532d] text-white px-6 py-2 rounded text-sm font-medium hover:bg-[#0d3520] transition-colors">
                আপডেট করুন
            </button>
            <a href="{{ route('admin.categories.index') }}"
               class="bg-gray-100 text-gray-600 px-5 py-2 rounded text-sm hover:bg-gray-200 transition-colors">
                বাতিল
            </a>
        </div>
    </form>
</div>

@endsection
