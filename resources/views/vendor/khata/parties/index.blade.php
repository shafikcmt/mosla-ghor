@extends('vendor.khata.layout')
@section('title', 'পার্টি')
@section('heading', 'পার্টি')
@section('subheading', 'পাওনা ৳'.number_format($dues['receivable'], 0).' · বকেয়া ৳'.number_format($dues['payable'], 0))

@section('content')
<form method="GET" class="flex gap-2">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="পার্টি অনুসন্ধান (নাম / ফোন)…" class="flex-1 min-w-0 border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white">
    <select name="type" onchange="this.form.submit()" class="border border-gray-200 rounded-xl px-2 py-2 text-sm bg-white">
        <option value="">সকল পার্টি</option>
        <option value="customer" @selected(request('type') === 'customer')>গ্রাহক</option>
        <option value="supplier" @selected(request('type') === 'supplier')>সরবরাহকারী</option>
    </select>
    <select name="balance" onchange="this.form.submit()" class="border border-gray-200 rounded-xl px-2 py-2 text-sm bg-white">
        <option value="">সকল পেমেন্ট</option>
        <option value="receivable" @selected(request('balance') === 'receivable')>পাওনা</option>
        <option value="payable" @selected(request('balance') === 'payable')>বকেয়া</option>
    </select>
</form>

<div class="mt-3 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
    @forelse($parties as $party)
    @php $b = $party->balance(); @endphp
    <a href="{{ route('vendor.khata.parties.show', $party) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50">
        <span class="shrink-0 w-11 h-11 rounded-full {{ $party->type === 'supplier' ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-[#0f7a3e]' }} font-bold flex items-center justify-center">{{ $party->initials() }}</span>
        <div class="min-w-0 flex-1">
            <p class="font-bold truncate">{{ $party->name }}</p>
            <p class="text-xs text-gray-400">{{ $party->phone ?: 'ফোন নেই' }} · {{ \App\Models\Khata\KhataParty::TYPES[$party->type] ?? '' }}</p>
        </div>
        <div class="text-right shrink-0">
            <p class="font-bold num {{ $b > 0 ? 'text-green-700' : ($b < 0 ? 'text-red-600' : 'text-gray-400') }}">৳{{ number_format(abs($b), 0) }}</p>
            <p class="text-[11px] {{ $b > 0 ? 'text-green-700' : ($b < 0 ? 'text-red-600' : 'text-gray-400') }}">{{ $b > 0 ? 'পাওনা' : ($b < 0 ? 'বকেয়া' : 'নিষ্পত্তি') }}</p>
        </div>
    </a>
    @empty
    <div class="p-10 text-center text-gray-500">
        <p class="text-4xl mb-2">👥</p>
        <p>কোনো পার্টি পাওয়া যায়নি।</p>
    </div>
    @endforelse
</div>

<a href="{{ route('vendor.khata.parties.create') }}" class="no-print fixed right-4 bottom-24 z-20 bg-[#0f7a3e] text-white font-semibold px-5 py-3 rounded-full shadow-lg">+ নতুন পার্টি</a>
@endsection
