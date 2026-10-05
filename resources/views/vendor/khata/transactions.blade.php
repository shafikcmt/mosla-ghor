@extends('vendor.khata.layout')
@section('title', 'লেনদেন')
@section('heading', 'লেনদেন')
@section('subheading', $from->format('d M Y').' — '.$to->format('d M Y'))

@section('content')
@include('vendor.khata._period')

<form method="GET" class="mt-3 flex gap-2">
    <input type="hidden" name="period" value="{{ $period }}">
    @if($period === 'custom')<input type="hidden" name="from" value="{{ $from->toDateString() }}"><input type="hidden" name="to" value="{{ $to->toDateString() }}">@endif
    <input type="search" name="q" value="{{ request('q') }}" placeholder="পার্টি, চালান নং বা নোট খুঁজুন…" class="flex-1 min-w-0 border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white">
    <select name="type" onchange="this.form.submit()" class="border border-gray-200 rounded-xl px-2 py-2 text-sm bg-white">
        <option value="">সব ধরন</option>
        @foreach(\App\Models\Khata\KhataTransaction::TYPES as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach
    </select>
</form>

<div class="mt-3 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
    @forelse($transactions as $tx)
        @include('vendor.khata._tx-row')
    @empty
        <p class="px-4 py-10 text-center text-gray-400 text-sm">এই সময়ে কোনো লেনদেন নেই।</p>
    @endforelse
</div>
<div class="mt-3">{{ $transactions->links() }}</div>
@endsection
