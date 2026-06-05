@props([
    'courier',
    'only' => null, // optional array subset: ['status','type','configured','default','vendor']
])

@php
$show = fn($k) => $only === null || in_array($k, (array) $only, true);
$pill = 'px-2 py-0.5 rounded text-[11px] font-semibold';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-center gap-1.5']) }}>
    @if($show('status'))
        <span class="{{ $pill }} {{ $courier->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}">
            {{ $courier->status === 'active' ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
        </span>
    @endif

    @if($show('type'))
        @if($courier->supportsApi())
            <span class="{{ $pill }} {{ $courier->api_enabled ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400' }}">
                API {{ $courier->api_enabled ? 'চালু' : 'বন্ধ' }}
            </span>
        @else
            <span class="{{ $pill }} bg-amber-100 text-amber-700">ম্যানুয়াল</span>
        @endif
    @endif

    @if($show('configured') && $courier->supportsApi())
        <span class="{{ $pill }} {{ $courier->isConfigured() ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
            {{ $courier->isConfigured() ? 'Configured' : 'Not configured' }}
        </span>
    @endif

    @if($show('default') && $courier->is_default)
        <span class="{{ $pill }} bg-yellow-100 text-yellow-700">ডিফল্ট</span>
    @endif

    @if($show('vendor'))
        <span class="{{ $pill }} {{ $courier->vendor_allowed ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-400' }}">
            ভেন্ডর {{ $courier->vendor_allowed ? 'অনুমোদিত' : 'বন্ধ' }}
        </span>
    @endif
</span>
