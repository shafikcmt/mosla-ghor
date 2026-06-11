{{-- $active = 'review' | 'payment' --}}
@php
    $steps = [
        ['key' => 'cart',    'label' => 'কার্ট',    'done' => true],
        ['key' => 'review',  'label' => 'রিভিউ',   'done' => $active === 'payment'],
        ['key' => 'payment', 'label' => 'পেমেন্ট', 'done' => false],
    ];
@endphp
<div class="flex items-center justify-center gap-1 sm:gap-2 mb-6 text-xs sm:text-sm select-none">
    @foreach($steps as $i => $s)
        @php
            $isActive = $s['key'] === $active;
            $isDone   = $s['done'];
        @endphp
        <div class="flex items-center gap-1.5">
            <span class="flex items-center justify-center w-6 h-6 rounded-full font-bold text-[11px]
                {{ $isDone ? 'bg-green-600 text-white' : ($isActive ? 'bg-[#14532d] text-white' : 'bg-gray-200 text-gray-500') }}">
                {{ $isDone ? '✓' : $i + 1 }}
            </span>
            <span class="{{ $isActive ? 'font-bold text-[#14532d]' : ($isDone ? 'text-green-700' : 'text-gray-400') }}">{{ $s['label'] }}</span>
        </div>
        @if(! $loop->last)
        <span class="w-5 sm:w-10 h-px bg-gray-300"></span>
        @endif
    @endforeach
</div>
