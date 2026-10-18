{{-- One transaction row. Needs $tx (party loaded). --}}
@php
    $tc = ['sale' => 'bg-blue-50 text-blue-700 ring-blue-100', 'purchase' => 'bg-orange-50 text-orange-700 ring-orange-100', 'payment_in' => 'bg-green-50 text-green-700 ring-green-100',
           'payment_out' => 'bg-red-50 text-red-700 ring-red-100', 'expense' => 'bg-amber-50 text-amber-700 ring-amber-100', 'stock_in' => 'bg-purple-50 text-purple-700 ring-purple-100', 'stock_out' => 'bg-gray-100 text-gray-600 ring-gray-200'][$tx->type] ?? 'bg-gray-100 ring-gray-200';
    $status = $tx->statusLabel();
    $rowEdit = \App\Support\KhataStaff::canEdit(auth()->user(), $tx);
    $who = $tx->party?->name ?? ($tx->category ?: ($tx->note ?: (in_array($tx->type, ['sale', 'purchase'], true) ? 'নগদ' : $tx->typeLabel())));
@endphp
<div class="flex items-stretch hover:bg-gray-50 transition-colors">
    <a href="{{ route('vendor.khata.tx.show', $tx) }}" class="flex flex-1 min-w-0 items-center gap-3 pl-4 pr-2 py-3">
        <span class="shrink-0 min-w-[64px] text-center text-[11px] font-bold px-2 py-1 rounded-lg ring-1 {{ $tc }}">{{ $tx->typeLabel() }}{{ $tx->number ? ' #'.$tx->number : '' }}</span>
        <div class="min-w-0 flex-1">
            <p class="font-semibold text-gray-800 truncate">{{ $who }}</p>
            <p class="text-xs text-gray-400 truncate">{{ $tx->date->format('d M Y') }}@if($tx->note && $tx->party) · {{ \Illuminate\Support\Str::limit($tx->note, 30) }}@endif</p>
        </div>
        <div class="text-right shrink-0">
            <p class="font-bold text-gray-900 num">৳{{ number_format((float) $tx->total, 0) }}</p>
            @if($status)
                <p class="text-[11px] font-medium {{ $tx->due() > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $status }}</p>
            @endif
        </div>
    </a>
    @if($rowEdit)
    <a href="{{ route('vendor.khata.tx.edit', $tx) }}" class="no-print shrink-0 self-center mr-3 inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-600 hover:border-[#0f7a3e] hover:text-[#0f7a3e]"
       aria-label="{{ $tx->typeLabel() }} #{{ $tx->number }} সম্পাদনা" title="সম্পাদনা">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.2 5.2l3.6 3.6M4 20l4.2-1 10.6-10.6a1.5 1.5 0 000-2.1l-1.1-1.1a1.5 1.5 0 00-2.1 0L5 15.8 4 20z"/></svg>
        <span class="hidden sm:inline">সম্পাদনা</span>
    </a>
    @endif
</div>
