@extends('vendor.khata.layout')
@section('title', $tx->typeLabel().($tx->number ? ' #'.$tx->number : ''))
@section('heading', $tx->typeLabel().($tx->number ? ' #'.$tx->number : ''))
@section('subheading', $tx->party?->name ?? $tx->date->format('d M Y'))
@section('back', ! \App\Support\KhataStaff::can(auth()->user(), 'ledger') ? route('vendor.khata.home') : ($tx->party_id ? route('vendor.khata.parties.show', $tx->party_id) : route('vendor.khata.transactions')))

@push('head')
<style>@media print { header, nav, main > div > aside, .no-print { display: none !important; } main > div { display: block !important; } .voucher { border: 0 !important; box-shadow: none !important; } main { padding: 0 !important; } .voucher, .voucher * { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }</style>
@endpush

@section('content')
@php
    $canEdit = \App\Support\KhataStaff::canEdit(auth()->user(), $tx) && $tx->lines->every(fn ($l) => $l->item_id !== null);
    $canDelete = \App\Support\KhataStaff::can(auth()->user(), 'delete');
    $due = $tx->due();
    $payRoute = $tx->party_id && $due > 0 && in_array($tx->type, ['sale', 'purchase'], true)
        ? route('vendor.khata.amount.create', ['type' => $tx->type === 'sale' ? 'payment_in' : 'payment_out', 'party' => $tx->party_id]) : null;
    $sec = 'flex items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 hover:border-gray-300 hover:bg-gray-50';
@endphp
<div class="lg:grid lg:grid-cols-3 lg:gap-6 lg:items-start">
    {{-- Actions: above the voucher on phones, a sticky side panel on desktop --}}
    <aside class="no-print lg:order-2 lg:sticky lg:top-20 space-y-3 mb-4 lg:mb-0" id="kh-actions" @if($whatsapp) data-wa="{{ $whatsapp }}" @endif>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-3 space-y-2">
            <p class="text-xs font-semibold text-gray-500 px-1">WhatsApp-এ পাঠান</p>
            {{-- WhatsApp links carry text only, so the voucher is turned into a picture / PDF and handed to the phone's share menu. --}}
            <div class="grid grid-cols-2 gap-2">
                <button type="button" data-kh-send="image" class="flex items-center justify-center gap-2 rounded-xl bg-[#25D366] hover:brightness-95 text-white font-bold py-3 shadow-sm">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm5.3 14.2c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .6l-.4.6-.4.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.7-.1l1-1.2c.2-.3.4-.2.7-.1l2 .9c.3.2.5.2.6.4.1.1.1.7-.1 1.3z"/></svg>
                    ছবি পাঠান
                </button>
                <button type="button" data-kh-send="pdf" class="flex items-center justify-center gap-2 rounded-xl bg-[#0f7a3e] hover:bg-[#0c6533] text-white font-bold py-3 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6"/></svg>
                    PDF পাঠান
                </button>
            </div>
            <p id="kh-send-msg" class="hidden text-sm rounded-xl px-3 py-2 bg-green-50 border border-green-200 text-green-800" role="status"></p>
        </div>

        @if($payRoute)
        <a href="{{ $payRoute }}" class="flex items-center justify-between rounded-2xl px-4 py-3 shadow-sm text-white {{ $tx->type === 'sale' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }}">
            <span><span class="block text-xs opacity-90">বাকি ৳{{ number_format($due, 0) }}</span><span class="block font-bold">{{ $tx->type === 'sale' ? 'টাকা গ্রহণ করুন' : 'টাকা পরিশোধ করুন' }}</span></span>
            <span class="text-xl" aria-hidden="true">→</span>
        </a>
        @endif

        <div class="grid grid-cols-3 lg:grid-cols-1 gap-2">
            @if($canEdit)
            <a href="{{ route('vendor.khata.tx.edit', $tx) }}" class="{{ $sec }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.2 5.2l3.6 3.6M4 20l4.2-1 10.6-10.6a1.5 1.5 0 000-2.1l-1.1-1.1a1.5 1.5 0 00-2.1 0L5 15.8 4 20z"/></svg>
                সম্পাদনা
            </a>
            @endif
            <button type="button" onclick="window.print()" class="{{ $sec }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 9V3h10v6M7 17H5a2 2 0 01-2-2v-4a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2h-2M7 14h10v7H7z"/></svg>
                প্রিন্ট
            </button>
            <button type="button" id="kh-share" class="{{ $sec }}" data-url="{{ $tx->publicUrl() }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7l-1 1M14 10a4 4 0 00-5.7 0l-3 3a4 4 0 005.7 5.7l1-1"/></svg>
                লিংক
            </button>
        </div>

        @if($tx->creator?->isStaff())
        <p class="text-xs text-gray-500 px-1">লিখেছেন: <span class="font-semibold text-gray-700">{{ $tx->creator->name }}</span> (স্টাফ)</p>
        @endif
    </aside>

    <div class="lg:col-span-2 lg:order-1">
        <div class="voucher-wrap">@include('vendor.khata._voucher')</div>

        @if($canDelete)
        <form method="POST" action="{{ route('vendor.khata.tx.destroy', $tx) }}" class="no-print mt-4 flex items-center justify-between gap-3 rounded-xl border border-red-100 bg-red-50/50 px-4 py-3"
              onsubmit="return confirm('এই {{ $tx->typeLabel() }} মুছবেন? স্টক ও পার্টির হিসাব আগের অবস্থায় ফিরবে।');">
            @csrf @method('DELETE')
            <span class="text-xs text-gray-500">ভুল হলে {{ $canEdit ? 'সম্পাদনা করুন, অথবা' : '' }} মুছে আবার লিখুন।</span>
            <button class="shrink-0 text-sm font-semibold text-red-600 hover:underline">🗑 মুছুন</button>
        </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
// Voucher → PNG (html2canvas) or PDF (jsPDF), then the phone's share menu (pick WhatsApp).
// Rendering in the browser keeps Bangla text shaped correctly. Without file sharing
// (most desktops) the file is downloaded and WhatsApp opens so it can be attached.
(function () {
    const WA = document.getElementById('kh-actions').dataset.wa || null, NAME = @json(\Illuminate\Support\Str::slug($tx->type).'-'.($tx->number ?: $tx->id));
    const CAPTION = @json($vendor->shop_name.' — '.$tx->typeLabel().($tx->number ? ' #'.$tx->number : '').' — মোট ৳'.number_format((float) $tx->total, 0));
    const msg = document.getElementById('kh-send-msg');
    const say = t => { msg.textContent = t; msg.classList.toggle('hidden', !t); };
    const load = src => new Promise((ok, bad) => {
        if (document.querySelector('script[src="' + src + '"]')) return ok();
        const s = document.createElement('script'); s.src = src; s.onload = ok; s.onerror = bad; document.head.appendChild(s);
    });
    async function render() {
        await load('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js');
        await document.fonts.ready;
        return html2canvas(document.querySelector('.voucher'), {
            scale: 2, backgroundColor: '#ffffff', useCORS: true, logging: false, windowWidth: 820,
            // Same picture from any device: render the desktop (sm+) layout at a fixed width.
            onclone: doc => {
                const v = doc.querySelector('.voucher'); v.style.width = '760px'; v.style.boxShadow = 'none';
                // html2canvas places Bangla text low in tall line boxes; tighter lines keep it centred.
                const st = doc.createElement('style');
                st.textContent = '.voucher *{line-height:1.3!important}.voucher span[class*="rounded"]{display:inline-flex;align-items:center;padding-top:.3rem;padding-bottom:.4rem}';
                doc.head.appendChild(st);
            },
        });
    }
    async function makeFile(kind) {
        const canvas = await render();
        if (kind === 'image') {
            const blob = await new Promise(r => canvas.toBlob(r, 'image/png'));
            return new File([blob], NAME + '.png', { type: 'image/png' });
        }
        await load('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');
        const { jsPDF } = window.jspdf;
        const w = 210, h = Math.min(297 * 3, canvas.height * w / canvas.width); // A4 width, page grows with the voucher
        const pdf = new jsPDF({ unit: 'mm', format: [w, Math.max(h, 100)] });
        pdf.addImage(canvas.toDataURL('image/jpeg', 0.92), 'JPEG', 0, 0, w, h);
        return new File([pdf.output('blob')], NAME + '.pdf', { type: 'application/pdf' });
    }
    document.querySelectorAll('[data-kh-send]').forEach(btn => btn.addEventListener('click', async () => {
        const label = btn.textContent;
        btn.disabled = true; btn.textContent = 'তৈরি হচ্ছে…'; say('');
        try {
            const file = await makeFile(btn.dataset.khSend);
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                try { await navigator.share({ files: [file], text: CAPTION }); } catch (e) { /* closed the share menu */ }
            } else {
                const a = document.createElement('a');
                a.href = URL.createObjectURL(file); a.download = file.name; a.click();
                setTimeout(() => URL.revokeObjectURL(a.href), 4000);
                if (WA) window.open(WA, '_blank', 'noopener');
                say((btn.dataset.khSend === 'image' ? 'ছবি' : 'PDF') + ' ডাউনলোড হয়েছে — WhatsApp-এ 📎 চেপে ফাইলটি যুক্ত করুন।');
            }
        } catch (e) {
            say('ফাইল তৈরি করা যায়নি — ইন্টারনেট চেক করে আবার চেষ্টা করুন, অথবা প্রিন্ট / PDF ব্যবহার করুন।');
        } finally {
            btn.disabled = false; btn.textContent = label;
        }
    }));
})();

document.getElementById('kh-share').addEventListener('click', async function () {
    const url = this.dataset.url;
    if (navigator.share) { try { await navigator.share({ title: document.title, url }); return; } catch (e) { return; } }
    try { await navigator.clipboard.writeText(url); this.textContent = '✓ লিংক কপি হয়েছে'; } catch (e) { prompt('লিংক কপি করুন:', url); }
});
</script>
@endpush
