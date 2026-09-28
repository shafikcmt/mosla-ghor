@extends('admin.layout')

@section('title', 'পণ্য তালিকা')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-quick-edit.css') }}?v=20260928">
@endpush

@section('content')

<div class="flex justify-between items-center mb-4">
    <h1 class="text-xl font-bold text-gray-800">পণ্য তালিকা</h1>
    <a href="{{ route('admin.products.create') }}"
       class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 transition-colors">
        + নতুন পণ্য
    </a>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="bg-white rounded shadow px-4 py-3">
        <div class="text-xs text-gray-500">মোট পণ্য</div>
        <div class="text-2xl font-bold text-gray-800" data-stat="total">{{ $stats['total'] }}</div>
    </div>
    <div class="bg-white rounded shadow px-4 py-3">
        <div class="text-xs text-gray-500">সক্রিয় পণ্য</div>
        <div class="text-2xl font-bold text-green-600" data-stat="active">{{ $stats['active'] }}</div>
    </div>
    <div class="bg-white rounded shadow px-4 py-3">
        <div class="text-xs text-gray-500">খুচরা (frontend)</div>
        <div class="text-2xl font-bold text-blue-600" data-stat="retail">{{ $stats['retail'] }}</div>
    </div>
    <div class="bg-white rounded shadow px-4 py-3">
        <div class="text-xs text-gray-500">পাইকারি</div>
        <div class="text-2xl font-bold text-orange-600" data-stat="wholesale">{{ $stats['wholesale'] }}</div>
    </div>
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
            <tr class="hover:bg-gray-50" data-qe-row data-qe='@json($quickEdit[$product->id])' data-qe-name="{{ $product->name_bn }}" data-qe-index="{{ $loop->iteration }}">
                <td class="px-4 py-3 text-gray-400" data-cell="order">{{ $product->sort_order ?: $loop->iteration }}</td>
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-900">{{ $product->name_bn }}</div>
                    @if($product->name_en)
                        <div class="text-xs text-gray-400">{{ $product->name_en }}</div>
                    @endif
                    <div class="text-xs text-gray-300 font-mono">{{ $product->slug }}</div>
                </td>
                <td class="px-4 py-3 text-gray-700 font-medium" data-cell="price">
                    ৳{{ number_format($product->retail_price_1kg, 0) }}
                </td>
                <td class="px-4 py-3 text-gray-700" data-cell="stock">
                    {{ $product->stock }}
                    @if($product->stock === 0)
                        <span class="text-xs text-red-500 ml-1">(শেষ)</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center" data-cell="status">
                    <div class="flex flex-wrap items-center justify-center gap-1">
                        @if($product->is_active)
                            <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">সক্রিয়</span>
                        @else
                            <span class="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full">নিষ্ক্রিয়</span>
                        @endif
                        @if($product->show_in_retail)
                            <span class="bg-green-50 text-green-700 border border-green-200 text-[10px] px-2 py-0.5 rounded-full">খুচরা</span>
                        @endif
                        @if($product->show_in_wholesale)
                            <span class="bg-blue-50 text-blue-700 border border-blue-200 text-[10px] px-2 py-0.5 rounded-full">পাইকারি</span>
                        @endif
                    </div>
                </td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                    <a href="{{ route('admin.products.edit', $product) }}"
                       class="text-blue-600 hover:text-blue-800 text-xs font-medium mr-3">সম্পাদনা</a>
                    <button type="button" data-qe-open
                            class="text-blue-600 hover:text-blue-800 text-xs font-medium mr-3">দ্রুত সম্পাদনা</button>
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

{{-- Quick edit dialog (one shared instance; filled from the row's data-qe JSON) --}}
<dialog class="qe-dialog" data-qe-dialog data-csrf="{{ csrf_token() }}" aria-labelledby="qe-title">
    <form class="qe-form" data-qe-form novalidate>
        <header class="qe-head">
            <div>
                <h2 id="qe-title">দ্রুত সম্পাদনা</h2>
                <p class="qe-name" data-qe-title></p>
            </div>
            <button type="button" class="qe-x" data-qe-close aria-label="বন্ধ করুন">×</button>
        </header>

        <p class="qe-alert" data-error="_general" role="alert" hidden></p>

        <div class="qe-checks">
            <label class="qe-check"><input type="checkbox" name="is_active"> সক্রিয়</label>
            <label class="qe-check"><input type="checkbox" name="show_in_retail"> খুচরা</label>
            <label class="qe-check"><input type="checkbox" name="show_in_wholesale"> পাইকারি</label>
        </div>
        <p class="qe-error" data-error="show_in_retail" hidden></p>

        <div class="qe-grid">
            <label class="qe-field" data-qe-group="price">খুচরা দাম / কেজি (৳)
                <input type="number" name="retail_price_1kg" step="0.01" min="0.01" max="99999999.99" inputmode="decimal">
                <small>সেভ করলে প্যাকের দাম আবার হিসাব হবে; ম্যানুয়াল দাম বদলাবে না।</small>
            </label>
            <p class="qe-note" data-qe-group="variant-note">ভ্যারিয়েন্টের দাম পূর্ণ সম্পাদনায় বদলান। <a data-qe-edit-link href="#">পূর্ণ সম্পাদনা →</a></p>
            <p class="qe-error qe-wide" data-error="retail_price_1kg" hidden></p>

            <label class="qe-field" data-qe-group="stock">স্টক (কেজি)
                <input type="number" name="stock" step="1" min="0" max="2147483647" inputmode="numeric">
                <span class="qe-error" data-error="stock" hidden></span>
            </label>
            <p class="qe-note" data-qe-group="unit-note">এই পণ্যের স্টক স্টক ব্যবস্থাপনা পেজ থেকে বদলান।</p>

            <label class="qe-field">কম স্টকের সীমা
                <input type="number" name="low_stock_threshold" step="0.001" min="0" max="999999999.999" inputmode="decimal">
                <span class="qe-error" data-error="low_stock_threshold" hidden></span>
            </label>
            <label class="qe-field">ক্রম (Sort order)
                <input type="number" name="sort_order" step="1" min="0" max="999999" inputmode="numeric">
                <span class="qe-error" data-error="sort_order" hidden></span>
            </label>
        </div>

        <footer class="qe-actions">
            <button type="button" class="qe-btn qe-btn-secondary" data-qe-close>বাতিল</button>
            <button type="submit" class="qe-btn qe-btn-primary" data-qe-save>সংরক্ষণ</button>
        </footer>
    </form>
</dialog>
<div class="qe-toast" data-qe-toast role="status" aria-live="polite" hidden></div>

<script src="{{ asset('js/admin-product-quick-edit.js') }}?v=20260928" defer></script>
@endsection
