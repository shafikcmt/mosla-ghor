@extends('customer.layout')
@section('title', 'আমার ঠিকানা')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">আমার ঠিকানা</h1>
    <a href="{{ route('customer.addresses.create') }}"
       class="text-sm bg-[#14532d] hover:bg-[#0d3520] text-white px-4 py-2 rounded-lg transition-colors">
        + নতুন ঠিকানা
    </a>
</div>

@forelse($addresses as $address)
<div class="bg-white rounded-xl border {{ $address->is_default ? 'border-[#14532d]' : 'border-gray-100' }} shadow-sm p-5 mb-3">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-sm font-semibold bg-gray-100 text-gray-700 px-2 py-0.5 rounded">{{ $address->label }}</span>
                @if($address->is_default)
                <span class="text-xs bg-[#14532d] text-white px-2 py-0.5 rounded font-medium">ডিফল্ট</span>
                @endif
            </div>
            <p class="text-sm font-medium text-gray-800">{{ $address->name }} · {{ $address->phone }}</p>
            <p class="text-sm text-gray-600 mt-0.5">{{ $address->full_address }}</p>
            @if($address->district_name)
            <p class="text-xs text-gray-400 mt-0.5">
                {{ implode(', ', array_filter([$address->division_name, $address->district_name, $address->upazila_name])) }}
            </p>
            @endif
        </div>
        <div class="flex flex-col gap-1.5 shrink-0">
            <a href="{{ route('customer.addresses.edit', $address->id) }}"
               class="text-xs border border-gray-300 text-gray-600 hover:bg-gray-50 px-3 py-1 rounded-lg text-center">সম্পাদনা</a>
            @if(! $address->is_default)
            <form method="POST" action="{{ route('customer.addresses.setDefault', $address->id) }}">
                @csrf
                <button type="submit" class="w-full text-xs border border-[#14532d] text-[#14532d] hover:bg-green-50 px-3 py-1 rounded-lg">ডিফল্ট করুন</button>
            </form>
            <form method="POST" action="{{ route('customer.addresses.destroy', $address->id) }}"
                  onsubmit="return confirm('এই ঠিকানা মুছে ফেলবেন?')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full text-xs border border-red-300 text-red-500 hover:bg-red-50 px-3 py-1 rounded-lg">মুছুন</button>
            </form>
            @endif
        </div>
    </div>
</div>
@empty
<div class="bg-white rounded-xl border border-gray-100 p-12 text-center text-gray-400 shadow-sm">
    <p class="text-3xl mb-2">📍</p>
    <p class="text-sm">কোনো ঠিকানা নেই।</p>
    <a href="{{ route('customer.addresses.create') }}" class="mt-3 inline-block text-sm text-[#14532d] font-semibold hover:underline">ঠিকানা যোগ করুন →</a>
</div>
@endforelse
@endsection
