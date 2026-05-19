@extends('admin.layout')

@section('title', 'নতুন রিভিউ')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.reviews.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← রিভিউ তালিকায় ফিরুন</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">নতুন রিভিউ</h1>
</div>

<div class="bg-white rounded shadow">
    <form action="{{ route('admin.reviews.store') }}" method="POST">
        @csrf
        @include('admin.reviews._form', ['review' => null])
        <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
            <button type="submit"
                    class="bg-gray-800 text-white px-6 py-2 rounded text-sm font-medium hover:bg-gray-700 transition-colors">
                সংরক্ষণ করুন
            </button>
            <a href="{{ route('admin.reviews.index') }}"
               class="bg-gray-100 text-gray-600 px-5 py-2 rounded text-sm hover:bg-gray-200 transition-colors">
                বাতিল
            </a>
        </div>
    </form>
</div>

@endsection
