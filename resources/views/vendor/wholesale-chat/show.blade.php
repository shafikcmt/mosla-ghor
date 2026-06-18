@extends('vendor.layout')
@section('title', 'Chat')

@section('content')
<div class="mb-4 flex items-center gap-3">
    <a href="{{ route('vendor.wholesale.enquiry.show', $enquiry->id) }}" class="text-gray-500 hover:text-gray-700 text-sm">← Enquiry</a>
    <span class="text-gray-300">|</span>
    <span class="text-gray-600 text-sm font-semibold">{{ $enquiry->productLabel() }}</span>
</div>

<div class="mb-4 bg-indigo-50 border border-indigo-200 rounded-xl p-3 text-xs text-indigo-800 leading-relaxed">
    Customer enquiry এবং quote process সুন্দরভাবে manage করার জন্য MoslaMart chatbox ব্যবহার করুন।
</div>

{{-- Chat messages --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-4">
    <div class="bg-[#14532d] px-5 py-3">
        <h3 class="text-white font-semibold text-sm">Chat — Enquiry #{{ $enquiry->id }}</h3>
    </div>

    <div class="p-5 space-y-3 min-h-[300px] max-h-[500px] overflow-y-auto" id="chat-messages">
        @if($messages->isEmpty())
        <div class="text-center py-10 text-gray-400 text-sm">
            এখনো কোনো বার্তা নেই। প্রথমে বার্তা পাঠান।
        </div>
        @else
        @foreach($messages as $msg)
        @php $isOwn = $msg->sender_type === 'vendor'; @endphp
        <div class="flex {{ $isOwn ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[80%]">
                <div class="text-xs text-gray-400 mb-0.5 {{ $isOwn ? 'text-right' : 'text-left' }}">
                    {{ $msg->senderLabel() }} · {{ $msg->created_at->format('d M, h:i A') }}
                </div>
                @if($msg->is_filtered)
                <div class="bg-amber-100 border border-amber-300 rounded-2xl px-4 py-2.5 text-sm text-amber-800 italic">
                    [বার্তাটি ফিল্টার করা হয়েছে] — MoslaMart chatbox-এর বাইরে যোগাযোগের চেষ্টা শনাক্ত হয়েছে।
                </div>
                @else
                <div class="rounded-2xl px-4 py-2.5 text-sm
                    {{ $isOwn ? 'bg-[#14532d] text-white' : ($msg->sender_type === 'admin' ? 'bg-blue-100 text-blue-900' : 'bg-gray-100 text-gray-800') }}">
                    {{ $msg->message }}
                </div>
                @endif
            </div>
        </div>
        @endforeach
        @endif
    </div>
</div>

{{-- Message input --}}
@if(in_array($enquiry->status, ['pending', 'quoted', 'accepted']))
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <form action="{{ route('vendor.wholesale.chat.store', $enquiry->id) }}" method="POST" class="flex gap-3">
        @csrf
        <textarea name="message" rows="2" required
                  placeholder="বার্তা লিখুন... (ফোন নম্বর / লিংক শেয়ার করবেন না)"
                  class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] resize-none bg-white"></textarea>
        <button type="submit"
                class="bg-[#14532d] hover:bg-[#166534] text-white font-semibold px-5 py-2 rounded-xl text-sm transition-colors self-end flex-shrink-0">
            পাঠান →
        </button>
    </form>
    <p class="text-xs text-gray-400 mt-2">⚠️ Phone number, email, বা external link শেয়ার করা নিরুৎসাহিত।</p>
</div>
@else
<div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 text-sm text-gray-500 text-center">
    এই enquiry-তে আর chat করা যাচ্ছে না (status: {{ $enquiry->statusLabel() }})।
</div>
@endif

<script>
document.getElementById('chat-messages')?.scrollTo(0, document.getElementById('chat-messages').scrollHeight);
</script>
@endsection
