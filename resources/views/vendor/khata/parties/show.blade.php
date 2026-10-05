@extends('vendor.khata.layout')
@section('title', $party->name)
@section('heading', $party->name)
@section('subheading', ($party->phone ?: '').' · '.(\App\Models\Khata\KhataParty::TYPES[$party->type] ?? ''))
@section('back', route('vendor.khata.parties.index'))

@section('content')
<div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm {{ $balance > 0 ? 'text-green-700' : ($balance < 0 ? 'text-red-600' : 'text-gray-500') }}">{{ $balance > 0 ? 'পাওনা' : ($balance < 0 ? 'বকেয়া' : 'নিষ্পত্তি করা') }}</p>
            <p class="text-3xl font-bold num {{ $balance > 0 ? 'text-green-700' : ($balance < 0 ? 'text-red-600' : 'text-gray-900') }}">৳{{ number_format(abs($balance), 0) }}</p>
        </div>
        <a href="{{ route('vendor.khata.parties.statement', $party) }}" class="text-sm font-semibold text-[#0f7a3e] border border-green-200 rounded-xl px-3 py-2">📄 রিপোর্ট দেখুন</a>
    </div>
    <div class="grid grid-cols-4 gap-2 mt-4 text-center text-xs text-gray-600">
        @if($party->phone)
        <a href="tel:{{ $party->phone }}" class="rounded-xl bg-gray-50 py-2.5">📞<br>ফোন</a>
        <a href="sms:{{ $party->phone }}" class="rounded-xl bg-gray-50 py-2.5">💬<br>বার্তা</a>
        @else
        <span class="rounded-xl bg-gray-50 py-2.5 opacity-40">📞<br>ফোন</span>
        <span class="rounded-xl bg-gray-50 py-2.5 opacity-40">💬<br>বার্তা</span>
        @endif
        @if($reminder)
        <a href="{{ $reminder }}" target="_blank" rel="noopener" class="rounded-xl bg-green-50 text-green-700 py-2.5">⏰<br>WhatsApp রিমাইন্ডার</a>
        @else
        <span class="rounded-xl bg-gray-50 py-2.5 opacity-40">⏰<br>রিমাইন্ডার</span>
        @endif
        <a href="{{ route('vendor.khata.parties.edit', $party) }}" class="rounded-xl bg-gray-50 py-2.5">✎<br>সম্পাদনা</a>
    </div>
</div>

<div class="mt-3 flex gap-2 overflow-x-auto text-xs">
    @foreach(['' => 'সব', 'sale' => 'বিক্রি', 'purchase' => 'ক্রয়', 'payment_in' => 'পেমেন্ট ইন', 'payment_out' => 'পেমেন্ট আউট'] as $k => $l)
    <a href="{{ route('vendor.khata.parties.show', [$party] + ($k ? ['type' => $k] : [])) }}"
       class="shrink-0 px-3 py-1.5 rounded-full border font-semibold {{ request('type', '') === $k ? 'bg-[#0f7a3e] text-white border-[#0f7a3e]' : 'bg-white border-gray-200 text-gray-600' }}">{{ $l }}</a>
    @endforeach
</div>

<div class="mt-3 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
    @forelse($txs as $tx)
        @include('vendor.khata._tx-row')
    @empty
        <p class="px-4 py-10 text-center text-gray-400 text-sm">এই পার্টির সাথে এখনো লেনদেন হয়নি।</p>
    @endforelse
</div>
<div class="mt-3">{{ $txs->links() }}</div>

<div class="no-print fixed inset-x-0 bottom-20 z-20 px-4">
    <div class="max-w-5xl mx-auto flex gap-2 justify-end">
        @if($party->type === 'supplier')
            <a href="{{ route('vendor.khata.amount.create', ['type' => 'payment_out', 'party' => $party->id]) }}" class="bg-red-600 text-white font-semibold px-4 py-3 rounded-full shadow-lg">পেমেন্ট আউট</a>
            <a href="{{ route('vendor.khata.trade.create', ['type' => 'purchase', 'party' => $party->id]) }}" class="bg-orange-500 text-white font-semibold px-4 py-3 rounded-full shadow-lg">নতুন ক্রয়</a>
        @else
            <a href="{{ route('vendor.khata.amount.create', ['type' => 'payment_in', 'party' => $party->id]) }}" class="bg-green-600 text-white font-semibold px-4 py-3 rounded-full shadow-lg">পেমেন্ট ইন</a>
            <a href="{{ route('vendor.khata.trade.create', ['type' => 'sale', 'party' => $party->id]) }}" class="bg-blue-600 text-white font-semibold px-4 py-3 rounded-full shadow-lg">নতুন বিক্রি</a>
        @endif
    </div>
</div>
@endsection
