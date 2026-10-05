@extends('vendor.khata.layout')
@section('title', $tx->typeLabel().($tx->number ? ' #'.$tx->number : ''))
@section('heading', $tx->typeLabel().($tx->number ? ' #'.$tx->number : ''))
@section('subheading', $tx->party?->name ?? $tx->date->format('d M Y'))
@section('back', $tx->party_id ? route('vendor.khata.parties.show', $tx->party_id) : route('vendor.khata.transactions'))

@push('head')
<style>@media print { header, nav, main > div:not(.voucher-wrap) { display: none !important; } .voucher { border: 0 !important; box-shadow: none !important; } main { padding: 0 !important; } }</style>
@endpush

@section('content')
<div class="no-print grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
    @if($whatsapp)
        <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="text-center bg-[#25D366] text-white font-semibold py-2.5 rounded-xl">WhatsApp-এ পাঠান</a>
    @endif
    <button type="button" onclick="window.print()" class="bg-gray-800 text-white font-semibold py-2.5 rounded-xl">🖨 প্রিন্ট / PDF</button>
    <button type="button" id="kh-share" class="bg-blue-600 text-white font-semibold py-2.5 rounded-xl" data-url="{{ $tx->publicUrl() }}">🔗 লিংক শেয়ার</button>
    @if($tx->type === 'sale' && $tx->due() > 0 && $tx->party_id)
        <a href="{{ route('vendor.khata.amount.create', ['type' => 'payment_in', 'party' => $tx->party_id]) }}" class="text-center bg-green-600 text-white font-semibold py-2.5 rounded-xl">টাকা গ্রহণ</a>
    @elseif($tx->type === 'purchase' && $tx->due() > 0 && $tx->party_id)
        <a href="{{ route('vendor.khata.amount.create', ['type' => 'payment_out', 'party' => $tx->party_id]) }}" class="text-center bg-red-600 text-white font-semibold py-2.5 rounded-xl">টাকা পরিশোধ</a>
    @endif
</div>

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
document.getElementById('kh-share').addEventListener('click', async function () {
    const url = this.dataset.url;
    if (navigator.share) { try { await navigator.share({ title: document.title, url }); return; } catch (e) { return; } }
    try { await navigator.clipboard.writeText(url); this.textContent = '✓ লিংক কপি হয়েছে'; } catch (e) { prompt('লিংক কপি করুন:', url); }
});
</script>
@endpush
