@extends('admin.layout')

@section('title', 'ক্যাটাগরি')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">ক্যাটাগরি</h1>
    <a href="{{ route('admin.categories.create') }}"
       class="bg-[#14532d] text-white text-sm px-4 py-2 rounded hover:bg-[#0d3520] transition-colors">
        + নতুন ক্যাটাগরি
    </a>
</div>

@if($parents->isEmpty())
    <div class="bg-white rounded shadow px-6 py-10 text-center text-gray-400">
        কোনো ক্যাটাগরি নেই।
        <a href="{{ route('admin.categories.create') }}" class="text-[#14532d] underline ml-1">যোগ করুন</a>
    </div>
@else
<div class="bg-white rounded shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="border-b border-gray-200">
            <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">নাম</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Slug</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-20">পণ্য</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-16">ক্রম</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-24">স্ট্যাটাস</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide w-32">অ্যাকশন</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($parents as $parent)
                @include('admin.categories._row', ['category' => $parent, 'child' => false])
                @foreach($parent->children as $child)
                    @include('admin.categories._row', ['category' => $child, 'child' => true])
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
