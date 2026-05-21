@extends('admin.layout')
@section('title', 'টিকেট #'.$supportTicket->id)

@section('content')
<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.support-tickets.index') }}" class="text-sm text-gray-500 hover:text-[#14532d]">← টিকেট তালিকা</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

        {{-- Ticket --}}
        <div class="bg-white rounded shadow p-5">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="font-bold text-gray-800">{{ $supportTicket->subject }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $supportTicket->user?->name }} · {{ $supportTicket->created_at->format('d M Y') }}</p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full font-medium shrink-0 {{ $supportTicket->statusColor() }}">{{ $supportTicket->statusLabel() }}</span>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $supportTicket->message }}</p>
            </div>
        </div>

        {{-- Reply form --}}
        <div class="bg-white rounded shadow p-5">
            <h2 class="font-bold text-gray-800 mb-4">উত্তর দিন</h2>
            <form method="POST" action="{{ route('admin.support-tickets.reply', $supportTicket->id) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">স্ট্যাটাস</label>
                    <select name="status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        @foreach(['open'=>'খোলা','replied'=>'উত্তর দেওয়া','closed'=>'বন্ধ'] as $val => $lbl)
                        <option value="{{ $val }}" {{ $supportTicket->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">উত্তর <span class="text-red-500">*</span></label>
                    <textarea name="admin_reply" required rows="5" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">{{ old('admin_reply', $supportTicket->admin_reply) }}</textarea>
                </div>
                <button type="submit" class="bg-[#14532d] hover:bg-[#0d3520] text-white text-sm px-5 py-2 rounded transition-colors">উত্তর পাঠান</button>
            </form>
        </div>
    </div>

    {{-- Sidebar info --}}
    <div>
        <div class="bg-white rounded shadow p-5">
            <h2 class="font-bold text-gray-800 mb-3">কাস্টমার তথ্য</h2>
            <dl class="space-y-1.5 text-sm">
                <div><dt class="text-xs text-gray-500">নাম</dt><dd>{{ $supportTicket->user?->name }}</dd></div>
                <div><dt class="text-xs text-gray-500">ফোন</dt><dd>{{ $supportTicket->user?->phone }}</dd></div>
                @if($supportTicket->user?->email)
                <div><dt class="text-xs text-gray-500">ইমেইল</dt><dd>{{ $supportTicket->user->email }}</dd></div>
                @endif
            </dl>
            @if($supportTicket->order)
            <hr class="my-3 border-gray-100">
            <h3 class="font-semibold text-gray-700 mb-2 text-sm">সম্পর্কিত অর্ডার</h3>
            <dl class="space-y-1.5 text-sm">
                <div><dt class="text-xs text-gray-500">নম্বর</dt><dd class="font-mono text-xs">{{ $supportTicket->order->order_number }}</dd></div>
                <div><dt class="text-xs text-gray-500">মোট</dt><dd>৳{{ number_format($supportTicket->order->grand_total, 0) }}</dd></div>
                <div><dt class="text-xs text-gray-500">স্ট্যাটাস</dt><dd>{{ $supportTicket->order->order_status }}</dd></div>
            </dl>
            @endif
        </div>
    </div>
</div>
@endsection
