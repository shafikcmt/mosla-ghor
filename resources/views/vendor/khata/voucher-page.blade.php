@extends('vendor.khata.layout')
@section('title', $tx->typeLabel().($tx->number ? ' #'.$tx->number : ''))
@section('heading', $tx->typeLabel().($tx->number ? ' #'.$tx->number : ''))
@section('subheading', $tx->party?->name ?? $tx->date->format('d M Y'))
@section('back', ! \App\Support\KhataStaff::can(auth()->user(), 'ledger') ? route('vendor.khata.home') : ($tx->party_id ? route('vendor.khata.parties.show', $tx->party_id) : route('vendor.khata.transactions')))

@push('head')
<style>@media print { header, nav, main > div:not(.voucher-wrap) { display: none !important; } .voucher { border: 0 !important; box-shadow: none !important; } main { padding: 0 !important; } }</style>
@endpush

@section('content')
<div class="no-print grid grid-cols-2 sm:grid-cols-3 gap-2 mb-3" id="kh-actions" @if($whatsapp) data-wa="{{ $whatsapp }}" @endif>
    {{-- WhatsApp links carry text only, so the voucher is turned into a picture / PDF and handed to the phone's share menu. --}}
    <button type="button" data-kh-send="image" class="bg-[#25D366] hover:brightness-95 text-white font-semibold py-2.5 rounded-xl">📷 ছবি পাঠান</button>
    <button type="button" data-kh-send="pdf" class="bg-emerald-700 hover:brightness-95 text-white font-semibold py-2.5 rounded-xl">📄 PDF পাঠান</button>
    @php
        $canEdit = \App\Support\KhataStaff::canEdit(auth()->user(), $tx) && $tx->lines->every(fn ($l) => $l->item_id !== null);
    @endphp
    @if($canEdit)
        <a href="{{ route('vendor.khata.tx.edit', $tx) }}" class="text-center bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 rounded-xl">✏️ সম্পাদনা</a>
    @endif
    <button type="button" onclick="window.print()" class="bg-gray-800 text-white font-semibold py-2.5 rounded-xl">🖨 প্রিন্ট / PDF</button>
    <button type="button" id="kh-share" class="bg-blue-600 text-white font-semibold py-2.5 rounded-xl" data-url="{{ $tx->publicUrl() }}">🔗 লিংক শেয়ার</button>
    @if($tx->type === 'sale' && $tx->due() > 0 && $tx->party_id)
        <a href="{{ route('vendor.khata.amount.create', ['type' => 'payment_in', 'party' => $tx->party_id]) }}" class="text-center bg-green-600 text-white font-semibold py-2.5 rounded-xl">টাকা গ্রহণ</a>
    @elseif($tx->type === 'purchase' && $tx->due() > 0 && $tx->party_id)
        <a href="{{ route('vendor.khata.amount.create', ['type' => 'payment_out', 'party' => $tx->party_id]) }}" class="text-center bg-red-600 text-white font-semibold py-2.5 rounded-xl">টাকা পরিশোধ</a>
    @endif
</div>

@if($tx->creator?->isStaff())
<p class="no-print text-xs text-gray-500 mb-2">লিখেছেন: <span class="font-semibold text-gray-700">{{ $tx->creator->name }}</span> (স্টাফ)</p>
@endif
<p id="kh-send-msg" class="no-print hidden mb-3 text-sm rounded-xl px-4 py-3 bg-green-50 border border-green-200 text-green-800" role="status"></p>
<div class="voucher-wrap">@include('vendor.khata._voucher')</div>

<form method="POST" action="{{ route('vendor.khata.tx.destroy', $tx) }}" class="no-print mt-4"
      onsubmit="return confirm('এই {{ $tx->typeLabel() }} মুছবেন? স্টক ও পার্টির হিসাব আগের অবস্থায় ফিরবে।');">
    @csrf @method('DELETE')
    <button class="text-sm text-red-600 hover:underline">🗑 এই লেনদেন মুছুন</button>
    <span class="text-xs text-gray-400 ml-2">ভুল হলে মুছে আবার লিখুন।</span>
</form>
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
        return html2canvas(document.querySelector('.voucher'), { scale: 2, backgroundColor: '#ffffff', useCORS: true, logging: false });
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
