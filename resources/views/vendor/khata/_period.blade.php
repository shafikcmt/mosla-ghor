{{-- Period chips (+ custom range). Needs $period, $from, $to; keeps other query params. --}}
@php $keep = request()->except(['period', 'from', 'to', 'page']); @endphp
<div class="flex flex-wrap gap-1.5 items-center">
    @foreach(['today' => 'আজ', 'yesterday' => 'গতকাল', 'week' => 'এই সপ্তাহ', 'month' => 'এই মাস', 'last_month' => 'গত মাস', 'year' => 'এই বছর'] as $key => $label)
    <a href="{{ request()->url().'?'.http_build_query($keep + ['period' => $key]) }}"
       class="text-xs font-semibold px-3 py-1.5 rounded-full border {{ $period === $key ? 'bg-[#0f7a3e] text-white border-[#0f7a3e]' : 'bg-white text-gray-600 border-gray-200' }}">{{ $label }}</a>
    @endforeach
    <form method="GET" class="flex items-center gap-1 text-xs">
        @foreach($keep as $k => $v)@if(is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
        <input type="hidden" name="period" value="custom">
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="border border-gray-200 rounded-lg px-2 py-1 bg-white">
        <span>—</span>
        <input type="date" name="to" value="{{ $to->toDateString() }}" class="border border-gray-200 rounded-lg px-2 py-1 bg-white">
        <button class="px-2.5 py-1 rounded-lg bg-gray-800 text-white">দেখুন</button>
    </form>
</div>
