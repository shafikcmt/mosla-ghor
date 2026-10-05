@extends('vendor.khata.layout')
@section('title', 'রিপোর্ট')
@section('heading', 'রিপোর্ট')
@section('subheading', $from->format('d M Y').' — '.$to->format('d M Y'))

@section('content')
@php $tk = fn ($n) => ((float) $n < 0 ? '−' : '').'৳'.number_format(abs((float) $n), 0); $q3 = fn ($n) => rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.'); @endphp
<div class="no-print flex items-start justify-between gap-2">
    @include('vendor.khata._period')
    <button onclick="window.print()" class="shrink-0 text-sm bg-gray-800 text-white px-3 py-1.5 rounded-xl">🖨 প্রিন্ট</button>
</div>

<div class="hidden print:block mb-3">
    <p class="text-xl font-bold">{{ $vendor->shop_name }} — রিপোর্ট</p>
    <p>{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</p>
</div>

<div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3">
    @foreach([
        ['মোট বিক্রি', $summary['sales'], 'text-blue-700', $summary['sales_count'].'টি চালান'],
        ['মোট ক্রয়', $summary['purchase'], 'text-orange-600', null],
        ['মোট খরচ', $summary['expense'], 'text-amber-600', null],
        ['নিট লাভ', $summary['net_profit'], $summary['net_profit'] >= 0 ? 'text-green-700' : 'text-red-600', 'বিক্রয় লাভ '.$tk($summary['gross_profit']).' − খরচ'],
        ['টাকা এসেছে', $summary['cash_in'], 'text-green-700', 'নগদ বিক্রি + পেমেন্ট ইন'],
        ['টাকা গেছে', $summary['cash_out'], 'text-red-600', 'ক্রয় + পেমেন্ট আউট + খরচ'],
        ['এই সময়ের বাকি বিক্রি', $summary['sale_due'], 'text-red-600', null],
        ['বর্তমান স্টক মূল্য', $stockValue, 'text-gray-900', 'ক্রয় মূল্যে'],
    ] as [$label, $value, $cls, $hint])
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
        <p class="text-xs text-gray-500">{{ $label }}</p>
        <p class="text-xl font-bold num {{ $cls }}">{{ $tk($value) }}</p>
        @if($hint)<p class="text-[11px] text-gray-400 mt-0.5">{{ $hint }}</p>@endif
    </div>
    @endforeach
</div>

<div class="mt-4 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
    <p class="px-4 pt-3 font-bold">{{ $byMonth ? 'মাসভিত্তিক হিসাব' : 'দিনভিত্তিক হিসাব' }}</p>
    <div class="overflow-x-auto">
    <table class="w-full text-sm mt-2">
        <thead class="bg-gray-50 text-gray-500 text-xs">
            <tr><th class="p-2 text-left">{{ $byMonth ? 'মাস' : 'তারিখ' }}</th><th class="p-2 text-right">বিক্রি</th><th class="p-2 text-right">ক্রয়</th><th class="p-2 text-right">খরচ</th><th class="p-2 text-right">টাকা এসেছে</th><th class="p-2 text-right">টাকা গেছে</th></tr>
        </thead>
        <tbody class="divide-y">
            @forelse($rows as $r)
            <tr>
                <td class="p-2 whitespace-nowrap">{{ $byMonth ? \Carbon\Carbon::parse($r->bucket.'-01')->format('M Y') : \Carbon\Carbon::parse($r->bucket)->format('d M, D') }}</td>
                <td class="p-2 text-right num">{{ $tk($r->sales) }}</td>
                <td class="p-2 text-right num">{{ $tk($r->purchase) }}</td>
                <td class="p-2 text-right num">{{ $tk($r->expense) }}</td>
                <td class="p-2 text-right num text-green-700">{{ $tk($r->cash_in) }}</td>
                <td class="p-2 text-right num text-red-600">{{ $tk($r->cash_out) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="p-6 text-center text-gray-400">এই সময়ে কোনো লেনদেন নেই।</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="mt-4 grid md:grid-cols-2 gap-4">
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <p class="px-4 pt-3 font-bold">সবচেয়ে বেশি বিক্রি</p>
        <table class="w-full text-sm mt-2">
            <thead class="bg-gray-50 text-gray-500 text-xs"><tr><th class="p-2 text-left">আইটেম</th><th class="p-2 text-right">পরিমাণ</th><th class="p-2 text-right">বিক্রি</th><th class="p-2 text-right">লাভ</th></tr></thead>
            <tbody class="divide-y">
                @forelse($topItems as $t)
                <tr><td class="p-2">{{ $t->name }}</td><td class="p-2 text-right num">{{ $q3($t->qty) }} {{ \App\Models\Khata\KhataItem::UNITS[$t->unit] ?? $t->unit }}</td>
                    <td class="p-2 text-right num">{{ $tk($t->amount) }}</td><td class="p-2 text-right num {{ $t->profit < 0 ? 'text-red-600' : 'text-green-700' }}">{{ $tk($t->profit) }}</td></tr>
                @empty
                <tr><td colspan="4" class="p-6 text-center text-gray-400">কোনো বিক্রি নেই।</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <p class="px-4 pt-3 font-bold">খরচের খাত</p>
        <table class="w-full text-sm mt-2">
            <tbody class="divide-y">
                @forelse($expenses as $e)
                <tr><td class="p-2">{{ $e->category }}</td><td class="p-2 text-right num">{{ $tk($e->amount) }}</td></tr>
                @empty
                <tr><td class="p-6 text-center text-gray-400">কোনো খরচ নেই।</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
    <p class="px-4 pt-3 font-bold">পাওনা / বকেয়া তালিকা (আজ পর্যন্ত)</p>
    <table class="w-full text-sm mt-2">
        <tbody class="divide-y">
            @forelse($parties as $p)
            @php $b = $p->balance(); @endphp
            <tr>
                <td class="p-2"><a class="hover:underline" href="{{ route('vendor.khata.parties.show', $p) }}">{{ $p->name }}</a> <span class="text-xs text-gray-400">{{ $p->phone }}</span></td>
                <td class="p-2 text-right font-semibold num {{ $b > 0 ? 'text-green-700' : 'text-red-600' }}">{{ $tk(abs($b)) }} {{ $b > 0 ? 'পাওনা' : 'বকেয়া' }}</td>
            </tr>
            @empty
            <tr><td class="p-6 text-center text-gray-400">কারো কাছে কোনো বাকি নেই। 🎉</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
