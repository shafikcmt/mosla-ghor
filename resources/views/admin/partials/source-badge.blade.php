@php $srcLabel = \App\Support\MarketingSource::label($model ?? null); @endphp
@if($srcLabel)
    <span class="inline-block text-[10px] font-semibold px-1.5 py-0.5 rounded bg-sky-100 text-sky-700 whitespace-nowrap" title="{{ $srcLabel }}">{{ \Illuminate\Support\Str::limit($srcLabel, 40) }}</span>
@else
    <span class="text-gray-300">—</span>
@endif
