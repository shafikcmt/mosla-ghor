@extends('admin.layout')
@section('title', 'Chat Monitor — Enquiry')

@section('content')
<div class="mb-4 flex items-center gap-3">
    <a href="{{ route('admin.wholesale.chat.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Chat Monitor</a>
    <span class="text-gray-300">|</span>
    <a href="{{ route('admin.wholesale.enquiry.show', $enquiry->id) }}" class="text-gray-500 hover:text-gray-700 text-sm">Enquiry #{{ $enquiry->id }}</a>
    <span class="text-gray-300">|</span>
    <span class="text-gray-600 text-sm font-semibold">{{ $enquiry->productLabel() }}</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <div class="lg:col-span-3 space-y-4">
        {{-- Chat messages --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="bg-gray-800 px-5 py-3 flex items-center justify-between">
                <h3 class="text-white font-semibold text-sm">Chat — Enquiry #{{ $enquiry->id }}</h3>
                <span class="text-gray-400 text-xs">{{ $messages->count() }} বার্তা</span>
            </div>

            <div class="p-5 space-y-3 min-h-[350px] max-h-[550px] overflow-y-auto" id="chat-messages">
                @if($messages->isEmpty())
                <div class="text-center py-10 text-gray-400 text-sm">
                    এখনো কোনো বার্তা নেই।
                </div>
                @else
                @foreach($messages as $msg)
                <div class="flex {{ $msg->sender_type === 'admin' ? 'justify-center' : ($msg->sender_type === 'vendor' ? 'justify-end' : 'justify-start') }}">
                    <div class="max-w-[75%]">
                        <div class="text-xs text-gray-400 mb-0.5 {{ $msg->sender_type === 'vendor' ? 'text-right' : 'text-left' }}">
                            {{ $msg->senderLabel() }} · {{ $msg->created_at->format('d M, h:i A') }}
                            @if($msg->is_filtered)<span class="text-red-400 ml-1">[ফিল্টার্ড]</span>@endif
                        </div>
                        @if($msg->is_filtered)
                        <div class="bg-red-50 border border-red-300 rounded-2xl px-4 py-2.5 text-sm text-red-700">
                            <p class="font-semibold text-xs mb-1">⚠ ফিল্টার্ড বার্তা ({{ $msg->filter_reason }})</p>
                            <p class="italic text-red-500">{{ $msg->message }}</p>
                        </div>
                        @else
                        <div class="rounded-2xl px-4 py-2.5 text-sm
                            {{ $msg->sender_type === 'customer' ? 'bg-gray-100 text-gray-800'
                               : ($msg->sender_type === 'vendor' ? 'bg-[#14532d] text-white'
                               : 'bg-blue-100 text-blue-900 border border-blue-200') }}">
                            {{ $msg->message }}
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>

        {{-- Admin reply form --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-blue-700 uppercase tracking-wider mb-3">Admin বার্তা পাঠান</p>
            <form action="{{ route('admin.wholesale.chat.store', $enquiry->id) }}" method="POST" class="flex gap-3">
                @csrf
                <textarea name="message" rows="2" required
                          placeholder="Admin হিসেবে বার্তা পাঠান..."
                          class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none bg-white"></textarea>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-xl text-sm transition-colors self-end flex-shrink-0">
                    পাঠান →
                </button>
            </form>
        </div>
    </div>

    {{-- Sidebar info --}}
    <div class="space-y-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-sm">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Enquiry তথ্য</p>
            <div class="space-y-2">
                <div><span class="text-gray-400 text-xs">Customer:</span> <span class="font-semibold text-gray-800 block">{{ $enquiry->customer_name }}</span></div>
                <div><span class="text-gray-400 text-xs">Phone:</span> <span class="font-semibold text-gray-800 block">{{ $enquiry->customer_phone }}</span></div>
                <div><span class="text-gray-400 text-xs">Vendor:</span> <span class="font-semibold text-gray-800 block">{{ $enquiry->vendor?->shop_name ?? $enquiry->vendor?->name ?? '—' }}</span></div>
                <div><span class="text-gray-400 text-xs">Status:</span> <span class="font-semibold text-gray-800 block">{{ $enquiry->statusLabel() }}</span></div>
            </div>
        </div>

        @php $filteredCount = $messages->where('is_filtered', true)->count(); @endphp
        @if($filteredCount > 0)
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 text-xs text-red-700">
            <p class="font-bold mb-1">⚠ {{ $filteredCount }}টি ফিল্টার্ড বার্তা</p>
            <p>কেউ contact sharing-এর চেষ্টা করেছেন।</p>
        </div>
        @endif
    </div>
</div>

<script>
document.getElementById('chat-messages')?.scrollTo(0, document.getElementById('chat-messages').scrollHeight);
</script>
@endsection
