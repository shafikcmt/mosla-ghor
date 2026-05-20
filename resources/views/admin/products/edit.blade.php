@extends('admin.layout')

@section('title', $product->name_bn . ' — সম্পাদনা')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.products.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← তালিকায় ফিরুন</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">{{ $product->name_bn }}</h1>
    @if(! $product->is_active)
        <span class="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full">নিষ্ক্রিয়</span>
    @endif
</div>

<form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- Product fields --}}
    <div class="bg-white shadow rounded p-6 mb-5">
        <h2 class="text-base font-semibold text-gray-700 mb-4 pb-2 border-b">পণ্যের তথ্য</h2>
        @include('admin.products._form', ['product' => $product])
    </div>

    {{-- Pack price overrides --}}
    <div class="bg-white shadow rounded p-6 mb-5">
        <h2 class="text-base font-semibold text-gray-700 mb-1">প্যাক সাইজ ও দাম</h2>
        <p class="text-xs text-gray-400 mb-4">
            ম্যানুয়াল দাম চালু করলে সেই মানটি গ্রাহকের কাছে দেখানো হবে। অক্রিয় প্যাক স্টোরে দেখাবে না।
            ১ কেজির দাম পরিবর্তন করলে সব ম্যানুয়াল-অফ প্যাকের দাম স্বয়ংক্রিয়ভাবে আপডেট হবে।
        </p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">প্যাক</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">স্বয়ংক্রিয় দাম</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">ম্যানুয়াল দাম (৳)</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">ম্যানুয়াল<br>চালু?</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">সক্রিয়?</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">চূড়ান্ত দাম</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($prices as $price)
                    <tr class="{{ $price->is_manual_override ? 'bg-amber-50' : '' }} {{ ! $price->is_active ? 'opacity-50' : '' }}">
                        <td class="px-3 py-2.5">
                            <span class="font-medium">{{ $price->label }}</span>
                            <span class="text-xs text-gray-400 ml-1">({{ $price->quantity_gram }}g)</span>
                        </td>
                        <td class="px-3 py-2.5 text-gray-500 font-mono">৳{{ $price->auto_price }}</td>
                        <td class="px-3 py-2.5">
                            <input type="number"
                                   name="prices[{{ $price->id }}][manual_price]"
                                   value="{{ $price->manual_price }}"
                                   step="0.01" min="0"
                                   placeholder="—"
                                   class="border rounded px-2 py-1 w-24 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400 font-mono">
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            <input type="checkbox"
                                   name="prices[{{ $price->id }}][is_manual_override]"
                                   value="1"
                                   {{ $price->is_manual_override ? 'checked' : '' }}
                                   class="w-4 h-4 rounded cursor-pointer">
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            <input type="checkbox"
                                   name="prices[{{ $price->id }}][is_active]"
                                   value="1"
                                   {{ $price->is_active ? 'checked' : '' }}
                                   class="w-4 h-4 rounded cursor-pointer">
                        </td>
                        <td class="px-3 py-2.5 font-semibold text-gray-800 font-mono">
                            ৳{{ $price->final_price }}
                            @if($price->is_manual_override)
                                <span class="text-xs font-normal text-amber-600 ml-1">(ম্যানুয়াল)</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <div class="flex gap-3">
            <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition-colors text-sm font-medium">
                পরিবর্তন সংরক্ষণ করুন
            </button>
            <a href="{{ route('admin.products.index') }}"
               class="bg-gray-100 text-gray-600 px-5 py-2 rounded hover:bg-gray-200 transition-colors text-sm">
                বাতিল
            </a>
        </div>
    </div>

</form>

{{-- Delete form is intentionally outside the update form --}}
<form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="mt-4"
      onsubmit="return confirm('\"{{ $product->name_bn }}\" এবং এর সব প্যাক সম্পূর্ণভাবে মুছে ফেলবেন?')">
    @csrf
    @method('DELETE')
    <button type="submit"
            class="text-red-500 hover:text-red-700 text-sm underline underline-offset-2">
        পণ্য মুছুন
    </button>
</form>

@endsection
