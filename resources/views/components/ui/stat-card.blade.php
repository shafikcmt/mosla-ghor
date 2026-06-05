@props([
    'label' => '',
    'value' => '',
    'color' => 'gray', // gray | green | blue | amber | red | emerald | indigo
])

@php
$ring = [
    'gray'    => 'text-gray-700',
    'green'   => 'text-green-600',
    'emerald' => 'text-emerald-600',
    'blue'    => 'text-blue-600',
    'amber'   => 'text-amber-600',
    'red'     => 'text-red-600',
    'indigo'  => 'text-indigo-600',
][$color] ?? 'text-gray-700';
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3']) }}>
    <div class="flex items-center justify-between">
        <p class="text-xs font-medium text-gray-500">{{ $label }}</p>
        @isset($icon)
            <span class="{{ $ring }}">{{ $icon }}</span>
        @endisset
    </div>
    <p class="mt-1 text-2xl font-bold {{ $ring }}">{{ $value }}</p>
    @isset($sub)
        <p class="text-[11px] text-gray-400 mt-0.5">{{ $sub }}</p>
    @endisset
</div>
