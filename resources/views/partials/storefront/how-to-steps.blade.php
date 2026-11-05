{{--
    "How to order" 3-step guide for the combo builders (retail box + wholesale list).
    Customers kept placing one order / enquiry per product because the "add several, send once"
    flow was not obvious. Usage: @include('partials.storefront.how-to-steps', ['tone' => 'green'|'amber', 'steps' => [a, b, c]])
--}}
@php
    $hsAmber = ($tone ?? 'green') === 'amber';
    $hsCard  = $hsAmber ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-green-50 border-green-200 text-green-900';
    $hsNum   = $hsAmber ? 'bg-amber-600' : 'bg-[#14532d]';
@endphp
<ol class="grid grid-cols-3 gap-2 sm:gap-3 max-w-2xl mx-auto mb-6 text-center" aria-label="কীভাবে অর্ডার করবেন">
    @foreach($steps as $i => $text)
    <li class="rounded-xl border {{ $hsCard }} px-2 py-2.5 sm:px-3 flex flex-col items-center gap-1.5">
        <span class="w-6 h-6 rounded-full {{ $hsNum }} text-white text-xs font-bold flex items-center justify-center">{{ ['১','২','৩'][$i] ?? $i + 1 }}</span>
        <span class="text-[11px] sm:text-xs font-semibold leading-snug">{{ $text }}</span>
    </li>
    @endforeach
</ol>
