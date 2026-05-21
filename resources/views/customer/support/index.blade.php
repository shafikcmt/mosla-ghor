@extends('customer.layout')
@section('title', 'সাপোর্ট')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">সাপোর্ট টিকেট</h1>
    <a href="{{ route('customer.support.create') }}"
       class="text-sm bg-[#14532d] hover:bg-[#0d3520] text-white px-4 py-2 rounded-lg transition-colors">
        + নতুন টিকেট
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    @forelse($tickets as $ticket)
    <div class="px-5 py-4 border-b border-gray-50 last:border-0 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-medium text-gray-800">{{ $ticket->subject }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $ticket->created_at->format('d M Y') }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $ticket->statusColor() }}">{{ $ticket->statusLabel() }}</span>
            <a href="{{ route('customer.support.show', $ticket->id) }}" class="text-xs text-[#14532d] hover:underline">দেখুন</a>
        </div>
    </div>
    @empty
    <div class="py-16 text-center text-gray-400">
        <p class="text-3xl mb-2">💬</p>
        <p class="text-sm">কোনো সাপোর্ট টিকেট নেই।</p>
        <a href="{{ route('customer.support.create') }}" class="mt-3 inline-block text-sm text-[#14532d] font-semibold hover:underline">নতুন টিকেট করুন →</a>
    </div>
    @endforelse
</div>
@if($tickets->hasPages())<div class="mt-4">{{ $tickets->links() }}</div>@endif
@endsection
