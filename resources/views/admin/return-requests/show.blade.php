@extends('admin.layout')
@section('title', 'রিটার্ন #'.$returnRequest->id)

@section('content')
<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.return-requests.index') }}" class="text-sm text-gray-500 hover:text-[#14532d]">← রিটার্ন তালিকা</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

        {{-- Details --}}
        <div class="bg-white rounded shadow p-5">
            <h2 class="font-bold text-gray-800 mb-4">রিটার্ন রিকোয়েস্ট #{{ $returnRequest->id }}</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex gap-3"><dt class="w-28 text-gray-500 shrink-0">কাস্টমার</dt><dd>{{ $returnRequest->user?->name }} ({{ $returnRequest->user?->phone }})</dd></div>
                <div class="flex gap-3"><dt class="w-28 text-gray-500 shrink-0">অর্ডার</dt><dd>{{ $returnRequest->order?->order_number }}</dd></div>
                @if($returnRequest->orderItem)
                <div class="flex gap-3"><dt class="w-28 text-gray-500 shrink-0">পণ্য</dt><dd>{{ $returnRequest->orderItem->product_name }}</dd></div>
                @endif
                <div class="flex gap-3"><dt class="w-28 text-gray-500 shrink-0">কারণ</dt><dd>{{ $returnRequest->reason }}</dd></div>
                @if($returnRequest->details)
                <div class="flex gap-3"><dt class="w-28 text-gray-500 shrink-0">বিস্তারিত</dt><dd class="text-gray-600">{{ $returnRequest->details }}</dd></div>
                @endif
                <div class="flex gap-3"><dt class="w-28 text-gray-500 shrink-0">তারিখ</dt><dd>{{ $returnRequest->created_at->format('d M Y') }}</dd></div>
            </dl>
            @if($returnRequest->image)
            <div class="mt-4">
                <img src="{{ asset('storage/'.$returnRequest->image) }}" alt="return" class="max-w-xs rounded border">
            </div>
            @endif
        </div>

        {{-- Update form --}}
        <div class="bg-white rounded shadow p-5">
            <h2 class="font-bold text-gray-800 mb-4">স্ট্যাটাস আপডেট</h2>
            <form method="POST" action="{{ route('admin.return-requests.update', $returnRequest->id) }}" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">স্ট্যাটাস</label>
                    <select name="status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        @foreach(['pending'=>'অপেক্ষায়','approved'=>'অনুমোদিত','rejected'=>'প্রত্যাখ্যাত','completed'=>'সম্পন্ন'] as $val => $lbl)
                        <option value="{{ $val }}" {{ $returnRequest->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">অ্যাডমিন নোট</label>
                    <textarea name="admin_note" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">{{ old('admin_note', $returnRequest->admin_note) }}</textarea>
                </div>
                <button type="submit" class="bg-[#14532d] hover:bg-[#0d3520] text-white text-sm px-5 py-2 rounded transition-colors">আপডেট করুন</button>
            </form>
        </div>
    </div>

    {{-- Order summary --}}
    <div>
        <div class="bg-white rounded shadow p-5">
            <h2 class="font-bold text-gray-800 mb-3">অর্ডার সারসংক্ষেপ</h2>
            @if($returnRequest->order)
            <dl class="space-y-1.5 text-sm">
                <div><dt class="text-xs text-gray-500">মোট</dt><dd class="font-semibold">৳{{ number_format($returnRequest->order->grand_total, 0) }}</dd></div>
                <div><dt class="text-xs text-gray-500">স্ট্যাটাস</dt><dd>{{ $returnRequest->order->order_status }}</dd></div>
                <div><dt class="text-xs text-gray-500">মোবাইল</dt><dd>{{ $returnRequest->order->mobile_number }}</dd></div>
            </dl>
            @endif
        </div>
    </div>
</div>
@endsection
