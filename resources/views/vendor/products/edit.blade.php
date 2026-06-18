@extends('vendor.layout')
@section('title', $product->name_bn . ' — সম্পাদনা')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('vendor.products.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← তালিকায় ফিরুন</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">{{ $product->name_bn }}</h1>
    @if(! $product->is_active)
        <span class="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full">নিষ্ক্রিয়</span>
    @endif
    @if($product->approval_status === 'pending')
        <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full">অনুমোদন অপেক্ষায়</span>
    @endif
</div>

<form action="{{ route('vendor.products.update', $product) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="bg-white shadow rounded p-6 mb-5">
        <h2 class="text-base font-semibold text-gray-700 mb-4 pb-2 border-b">পণ্যের তথ্য</h2>
        @include('vendor.products._form', ['product' => $product])
    </div>

    {{-- Retail price overrides --}}
    @if($retailPrices->isNotEmpty())
    <div class="bg-white shadow rounded p-6 mb-5">
        <h2 class="text-base font-semibold text-gray-700 mb-1">খুচরা প্যাক সাইজ ও দাম</h2>
        <p class="text-xs text-gray-400 mb-4">ম্যানুয়াল দাম চালু করলে সেটি গ্রাহকের কাছে দেখানো হবে।</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">প্যাক</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">স্বয়ংক্রিয় দাম</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">ম্যানুয়াল দাম (৳)</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">ম্যানুয়াল?</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">সক্রিয়?</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">চূড়ান্ত দাম</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($retailPrices as $price)
                    <tr class="{{ $price->is_manual_override ? 'bg-amber-50' : '' }} {{ ! $price->is_active ? 'opacity-50' : '' }}">
                        <td class="px-3 py-2.5 font-medium">{{ $price->label }} <span class="text-xs text-gray-400">({{ $price->quantity_gram }}g)</span></td>
                        <td class="px-3 py-2.5 text-gray-500 font-mono">৳{{ $price->auto_price }}</td>
                        <td class="px-3 py-2.5">
                            <input type="number" name="prices[{{ $price->id }}][manual_price]"
                                   value="{{ $price->manual_price }}" step="0.01" min="0" placeholder="—"
                                   class="border rounded px-2 py-1 w-24 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-indigo-400">
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            <input type="checkbox" name="prices[{{ $price->id }}][is_manual_override]" value="1"
                                   {{ $price->is_manual_override ? 'checked' : '' }}>
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            <input type="checkbox" name="prices[{{ $price->id }}][is_active]" value="1"
                                   {{ $price->is_active ? 'checked' : '' }}>
                        </td>
                        <td class="px-3 py-2.5 font-mono font-semibold text-green-700">৳{{ $price->final_price }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="flex gap-3 items-center">
        <button type="submit"
                class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 transition-colors text-sm font-medium">
            পণ্য আপডেট করুন
        </button>
        <a href="{{ route('vendor.products.index') }}"
           class="bg-gray-100 text-gray-600 px-5 py-2 rounded hover:bg-gray-200 transition-colors text-sm">
            বাতিল
        </a>
        <form method="POST" action="{{ route('vendor.products.destroy', $product) }}"
              onsubmit="return confirm('সত্যিই এই পণ্যটি মুছে ফেলবেন?')"
              class="ml-auto">
            @csrf @method('DELETE')
            <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded text-sm transition-colors">
                পণ্য মুছুন
            </button>
        </form>
    </div>
</form>

@endsection
