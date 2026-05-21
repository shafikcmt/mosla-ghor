@extends('customer.layout')
@section('title', 'রিটার্ন রিকোয়েস্ট')

@section('content')
<div class="max-w-lg">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('customer.returns.index') }}" class="text-sm text-gray-500 hover:text-[#14532d]">← রিটার্ন তালিকা</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="font-bold text-gray-800">রিটার্ন রিকোয়েস্ট #{{ $returnRequest->id }}</h1>
            <span class="text-xs px-2 py-1 rounded-full font-medium {{ $returnRequest->statusColor() }}">{{ $returnRequest->statusLabel() }}</span>
        </div>

        <dl class="space-y-2 text-sm">
            <div><dt class="text-xs text-gray-500">অর্ডার</dt><dd>{{ $returnRequest->order?->order_number }}</dd></div>
            @if($returnRequest->orderItem)
            <div><dt class="text-xs text-gray-500">পণ্য</dt><dd>{{ $returnRequest->orderItem->product_name }}</dd></div>
            @endif
            <div><dt class="text-xs text-gray-500">কারণ</dt><dd>{{ $returnRequest->reason }}</dd></div>
            @if($returnRequest->details)
            <div><dt class="text-xs text-gray-500">বিস্তারিত</dt><dd class="text-gray-600">{{ $returnRequest->details }}</dd></div>
            @endif
            @if($returnRequest->image)
            <div>
                <dt class="text-xs text-gray-500 mb-1">ছবি</dt>
                <dd><img src="{{ asset('storage/'.$returnRequest->image) }}" alt="return" class="max-w-xs rounded-lg border"></dd>
            </div>
            @endif
            <div><dt class="text-xs text-gray-500">তারিখ</dt><dd>{{ $returnRequest->created_at->format('d M Y') }}</dd></div>
        </dl>

        @if($returnRequest->admin_note)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-xs font-semibold text-blue-700 mb-1">অ্যাডমিনের নোট</p>
            <p class="text-sm text-blue-800">{{ $returnRequest->admin_note }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
