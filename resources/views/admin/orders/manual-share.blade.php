@extends('admin.layout')
@section('title', 'ভাউচার — #'.$order->order_number)

@section('content')
@php $tk = fn ($n) => '৳'.number_format((float) $n, 0); @endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800">ভাউচার — #{{ $order->order_number }}</h1>
        <p class="text-xs text-gray-500 mt-0.5">{{ $order->customer_name }} · {{ $order->mobile_number }} · {{ $order->created_at->format('d M Y, h:i A') }}</p>
    </div>
    <div class="flex gap-2 text-sm">
        <a href="{{ route('admin.orders.show', $order) }}" class="border border-gray-200 text-gray-600 hover:bg-gray-50 px-3 py-2 rounded-lg">অর্ডার বিস্তারিত</a>
        <a href="{{ route('admin.orders.manual.create') }}" class="bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold px-3 py-2 rounded-lg">+ নতুন অর্ডার</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

    {{-- Share actions --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h2 class="text-sm font-bold text-gray-800 mb-3">ক্রেতার কাছে পাঠান</h2>

            {{-- Mobile: share the actual PDF file (WhatsApp, Messenger…) via the system share sheet. --}}
            <button type="button" id="share-pdf" hidden
                    class="w-full mb-2 inline-flex items-center justify-center gap-2 bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold py-3 rounded-xl">
                📤 PDF ভাউচার শেয়ার করুন
            </button>

            @if($whatsappUrl)
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
               class="w-full inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#1ebe5b] text-white font-semibold py-3 rounded-xl">
                WhatsApp-এ ভাউচার পাঠান
            </a>
            <p class="text-[11px] text-gray-400 mt-1.5">অর্ডারের বিবরণ, PDF লিংক{{ $setPasswordUrl ? ' ও অ্যাকাউন্ট চালুর লিংক' : '' }}সহ মেসেজ লেখা থাকবে — শুধু Send চাপুন।</p>
            @endif

            <form method="POST" action="{{ route('admin.orders.manual.email', $order) }}" class="mt-4 flex gap-2">
                @csrf
                <input type="email" name="email" value="{{ old('email', $email) }}" required placeholder="ক্রেতার ইমেইল"
                       class="flex-1 min-w-0 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-white text-sm font-semibold px-4 rounded-lg whitespace-nowrap">ইমেইল করুন</button>
            </form>
            <p class="text-[11px] text-gray-400 mt-1.5">ইমেইলে PDF সংযুক্ত থাকবে{{ $setPasswordUrl ? ', সাথে অ্যাকাউন্ট চালুর লিংক' : '' }}।</p>

            <div class="grid grid-cols-2 gap-2 mt-4">
                <a href="{{ $order->invoicePdfUrl() }}" target="_blank" rel="noopener"
                   class="text-center border border-gray-200 hover:bg-gray-50 text-sm font-semibold text-gray-700 py-2.5 rounded-lg">⬇ PDF ডাউনলোড</a>
                <button type="button" data-copy="{{ $order->invoiceUrl() }}"
                        class="copy-btn border border-gray-200 hover:bg-gray-50 text-sm font-semibold text-gray-700 py-2.5 rounded-lg">🔗 লিংক কপি</button>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h2 class="text-sm font-bold text-gray-800 mb-2">অ্যাকাউন্ট / রেজিস্ট্রেশন লিংক</h2>
            @if($setPasswordUrl)
                <p class="text-xs text-gray-500 mb-3">এই নম্বরে কাস্টমার অ্যাকাউন্ট তৈরি আছে, কিন্তু password এখনো সেট হয়নি। লিংকে ঢুকে password দিলেই অর্ডার ট্র্যাক ও পরের অর্ডার করতে পারবে (৭ দিন বৈধ)।</p>
                <button type="button" data-copy="{{ $setPasswordUrl }}"
                        class="copy-btn w-full border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-800 text-sm font-semibold py-2.5 rounded-lg">🔐 রেজিস্ট্রেশন লিংক কপি</button>
            @else
                <p class="text-xs text-green-700 bg-green-50 border border-green-100 rounded-lg px-3 py-2">✓ এই কাস্টমারের অ্যাকাউন্ট আগে থেকেই চালু — নম্বর দিয়ে লগইন করতে পারবে।</p>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">সর্বমোট</span><span class="font-bold">{{ $tk($order->grand_total) }}</span></div>
            <div class="flex justify-between mt-1"><span class="text-gray-500">পরিশোধিত</span><span>{{ $tk($order->paid_amount) }}</span></div>
            <div class="flex justify-between mt-1"><span class="text-gray-500">বাকি</span><span class="font-semibold {{ (float) $order->due_amount > 0 ? 'text-red-600' : 'text-green-700' }}">{{ $tk($order->due_amount) }}</span></div>
        </div>
    </div>

    {{-- Voucher preview (the same public page the customer opens) --}}
    <div class="lg:col-span-3 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-2 border-b border-gray-100 text-xs text-gray-500 flex justify-between">
            <span>ক্রেতা যা দেখবে</span>
            <a href="{{ $order->invoiceUrl() }}" target="_blank" rel="noopener" class="text-[#14532d] font-semibold hover:underline">নতুন ট্যাবে খুলুন ↗</a>
        </div>
        <iframe src="{{ $order->invoiceUrl() }}" title="ভাউচার" class="w-full" style="height: 720px; border: 0;"></iframe>
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('.copy-btn').forEach(b => b.addEventListener('click', async () => {
        const old = b.textContent;
        try { await navigator.clipboard.writeText(b.dataset.copy); b.textContent = '✓ কপি হয়েছে'; }
        catch (e) { window.prompt('কপি করুন:', b.dataset.copy); }
        setTimeout(() => b.textContent = old, 1500);
    }));

    // Share the real PDF file where the browser supports file sharing (most phones).
    const btn = document.getElementById('share-pdf');
    const probe = new File([''], 'x.pdf', { type: 'application/pdf' });
    if (navigator.canShare && navigator.canShare({ files: [probe] })) {
        btn.hidden = false;
        btn.addEventListener('click', async () => {
            btn.disabled = true; btn.textContent = 'তৈরি হচ্ছে...';
            try {
                const res = await fetch(@json($order->invoicePdfUrl()));
                const file = new File([await res.blob()], 'voucher-{{ $order->order_number }}.pdf', { type: 'application/pdf' });
                await navigator.share({ files: [file], title: 'অর্ডার #{{ $order->order_number }}',
                    text: @json('অর্ডার #'.$order->order_number.' — মোট '.$tk($order->grand_total).($setPasswordUrl ? "\nঅ্যাকাউন্ট চালু করুন: ".$setPasswordUrl : '')) });
            } catch (e) { /* cancelled */ }
            btn.disabled = false; btn.textContent = '📤 PDF ভাউচার শেয়ার করুন';
        });
    }
})();
</script>
@endsection
