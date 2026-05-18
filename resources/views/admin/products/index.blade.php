@extends('admin.layout')

@section('title', 'পণ্য তালিকা')

@section('content')

<div class="flex justify-between items-center mb-4">
    <h1 class="text-xl font-bold text-gray-800">পণ্য তালিকা</h1>
    <a href="{{ route('admin.products.create') }}"
       class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 transition-colors">
        + নতুন পণ্য
    </a>
</div>

<div class="bg-white shadow rounded overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b text-gray-600 font-medium">
            <tr>
                <th class="px-4 py-3 text-left w-10">#</th>
                <th class="px-4 py-3 text-left">পণ্যের নাম</th>
                <th class="px-4 py-3 text-left">খুচরা দাম / কেজি</th>
                <th class="px-4 py-3 text-left">স্টক</th>
                <th class="px-4 py-3 text-center">স্ট্যাটাস</th>
                <th class="px-4 py-3 text-center">অ্যাকশন</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($products as $product)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-400">{{ $product->sort_order ?: $loop->iteration }}</td>
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-900">{{ $product->name_bn }}</div>
                    @if($product->name_en)
                        <div class="text-xs text-gray-400">{{ $product->name_en }}</div>
                    @endif
                    <div class="text-xs text-gray-300 font-mono">{{ $product->slug }}</div>
                </td>
                <td class="px-4 py-3 text-gray-700 font-medium">
                    ৳{{ number_format($product->retail_price_1kg, 0) }}
                </td>
                <td class="px-4 py-3 text-gray-700">
                    {{ $product->stock }}
                    @if($product->stock === 0)
                        <span class="text-xs text-red-500 ml-1">(শেষ)</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    @if($product->is_active)
                        <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">সক্রিয়</span>
                    @else
                        <span class="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full">নিষ্ক্রিয়</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                    <a href="{{ route('admin.products.edit', $product) }}"
                       class="text-blue-600 hover:text-blue-800 text-xs font-medium mr-3">সম্পাদনা</a>
                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                          class="inline"
                          onsubmit="return confirm('\"{{ $product->name_bn }}\" মুছে ফেলবেন? এটি পূর্বাবস্থায় ফেরানো যাবে না।')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">মুছুন</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                    কোনো পণ্য নেই।
                    <a href="{{ route('admin.products.create') }}" class="text-blue-600 hover:underline ml-1">প্রথম পণ্য যোগ করুন</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($products->hasPages())
    <div class="mt-4">{{ $products->links() }}</div>
@endif

@endsection
