@extends('customer.layout')
@section('title', 'রিটার্ন / রিফান্ড')

@section('content')
<h1 class="text-xl font-bold text-gray-800 mb-5">রিটার্ন / রিফান্ড রিকোয়েস্ট</h1>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    @forelse($returns as $return)
    <div class="px-5 py-4 border-b border-gray-50 last:border-0 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-medium text-gray-800">{{ $return->order?->order_number ?? '#'.$return->order_id }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ $return->reason }}</p>
            <p class="text-xs text-gray-400">{{ $return->created_at->format('d M Y') }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $return->statusColor() }}">{{ $return->statusLabel() }}</span>
            <a href="{{ route('customer.returns.show', $return->id) }}" class="text-xs text-[#14532d] hover:underline">দেখুন</a>
        </div>
    </div>
    @empty
    <div class="py-16 text-center text-gray-400">
        <p class="text-3xl mb-2">↩️</p>
        <p class="text-sm">কোনো রিটার্ন রিকোয়েস্ট নেই।</p>
    </div>
    @endforelse
</div>

@if($returns->hasPages())
<div class="mt-4">{{ $returns->links() }}</div>
@endif
@endsection
