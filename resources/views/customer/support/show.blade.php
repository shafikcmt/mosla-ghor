@extends('customer.layout')
@section('title', 'সাপোর্ট টিকেট')

@section('content')
<div class="max-w-lg">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('customer.support.index') }}" class="text-sm text-gray-500 hover:text-[#14532d]">← সাপোর্ট তালিকা</a>
    </div>

    <div class="space-y-4">
        {{-- Ticket info --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h1 class="font-bold text-gray-800">{{ $supportTicket->subject }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $supportTicket->created_at->format('d M Y, h:i A') }}</p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full font-medium shrink-0 {{ $supportTicket->statusColor() }}">{{ $supportTicket->statusLabel() }}</span>
            </div>

            @if($supportTicket->order)
            <div class="mb-3 text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
                অর্ডার: {{ $supportTicket->order->order_number }}
            </div>
            @endif

            <div class="bg-green-50 border border-green-100 rounded-lg p-4">
                <p class="text-xs font-semibold text-green-700 mb-1">আপনার বার্তা</p>
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $supportTicket->message }}</p>
            </div>
        </div>

        {{-- Admin reply --}}
        @if($supportTicket->admin_reply)
        <div class="bg-white rounded-xl border border-blue-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-blue-700">অ্যাডমিনের উত্তর</p>
                @if($supportTicket->replied_at)
                <p class="text-xs text-gray-400">{{ $supportTicket->replied_at->format('d M Y') }}</p>
                @endif
            </div>
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $supportTicket->admin_reply }}</p>
        </div>
        @else
        <div class="bg-gray-50 rounded-xl border border-dashed border-gray-200 p-6 text-center">
            <p class="text-sm text-gray-400">উত্তরের অপেক্ষায়...</p>
        </div>
        @endif
    </div>
</div>
@endsection
