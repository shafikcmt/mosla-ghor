{{-- One transaction row. Needs $tx (party loaded). --}}
@php
    $tc = ['sale' => 'bg-blue-100 text-blue-700', 'purchase' => 'bg-orange-100 text-orange-700', 'payment_in' => 'bg-green-100 text-green-700',
           'payment_out' => 'bg-red-100 text-red-700', 'expense' => 'bg-amber-100 text-amber-700', 'stock_in' => 'bg-purple-100 text-purple-700', 'stock_out' => 'bg-gray-100 text-gray-600'][$tx->type] ?? 'bg-gray-100';
    $status = $tx->statusLabel();
@endphp
<a href="{{ route('vendor.khata.tx.show', $tx) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50">
    <span class="shrink-0 text-[11px] font-bold px-2 py-1 rounded-lg {{ $tc }}">{{ $tx->typeLabel() }}{{ $tx->number ? ' #'.$tx->number : '' }}</span>
    <div class="min-w-0 flex-1">
        <p class="font-semibold text-gray-800 truncate">{{ $tx->party?->name ?? ($tx->category ?: ($tx->note ?: (in_array($tx->type, ['sale', 'purchase'], true) ? 'নগদ' : $tx->typeLabel()))) }}</p>
        <p class="text-xs text-gray-400">{{ $tx->date->format('d M Y') }}@if($tx->note && $tx->party) · {{ \Illuminate\Support\Str::limit($tx->note, 30) }}@endif</p>
    </div>
    <div class="text-right shrink-0">
        <p class="font-bold num">৳{{ number_format((float) $tx->total, 0) }}</p>
        @if($status)
            <p class="text-[11px] {{ $tx->due() > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $status }}</p>
        @endif
    </div>
</a>
