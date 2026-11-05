@extends('admin.layout')

@section('title', 'মার্কেটিং রিপোর্ট')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <h1 class="text-xl font-bold text-gray-800">মার্কেটিং রিপোর্ট</h1>
    <form method="GET" action="{{ route('admin.marketing-report.index') }}" class="flex flex-wrap items-end gap-2">
        <div>
            <label class="block text-[11px] text-gray-500 mb-0.5" for="from">শুরু</label>
            <input id="from" type="date" name="from" value="{{ $from }}" class="border border-gray-300 rounded px-2 py-1 text-sm">
        </div>
        <div>
            <label class="block text-[11px] text-gray-500 mb-0.5" for="to">শেষ</label>
            <input id="to" type="date" name="to" value="{{ $to }}" class="border border-gray-300 rounded px-2 py-1 text-sm">
        </div>
        <button type="submit" class="bg-gray-800 text-white text-xs px-3 py-2 rounded hover:bg-gray-700">দেখুন</button>
    </form>
</div>

@if($rows->isEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">
        <p class="text-4xl mb-3">📊</p>
        <p class="text-sm">এই সময়ে কোনো ডাটা নেই। UTM লিংক (utm_source, utm_campaign) দিয়ে ভিজিট এলে এখানে দেখা যাবে।</p>
    </div>
@else
<div class="bg-white rounded shadow-sm border border-gray-100 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200 text-xs text-gray-600">
            <tr>
                <th class="px-3 py-2 text-left font-semibold">সোর্স</th>
                <th class="px-3 py-2 text-left font-semibold">ক্যাম্পেইন</th>
                <th class="px-3 py-2 text-right font-semibold">ইউনিক ভিজিটর</th>
                <th class="px-3 py-2 text-right font-semibold">মোট ভিজিট</th>
                <th class="px-3 py-2 text-right font-semibold">অর্ডার</th>
                <th class="px-3 py-2 text-right font-semibold">অর্ডারের টাকা (৳)</th>
                <th class="px-3 py-2 text-right font-semibold">পাইকারি এনকোয়ারি</th>
                <th class="px-3 py-2 text-right font-semibold">কম্বো এনকোয়ারি</th>
                <th class="px-3 py-2 text-right font-semibold">কনভার্সন %</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($rows as $r)
            <tr class="{{ $r['direct'] ? 'bg-gray-50 text-gray-500' : '' }}">
                @if($r['direct'])
                    <td class="px-3 py-2 font-medium" colspan="2">সরাসরি / অজানা</td>
                @else
                    <td class="px-3 py-2 font-medium text-gray-800">{{ $r['source'] ?? '—' }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ $r['campaign'] ?? '—' }}</td>
                @endif
                <td class="px-3 py-2 text-right">{{ $r['direct'] ? '—' : number_format($r['visitors']) }}</td>
                <td class="px-3 py-2 text-right">{{ $r['direct'] ? '—' : number_format($r['visits']) }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($r['orders']) }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($r['revenue'], 0) }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($r['wholesale']) }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($r['combo']) }}</td>
                <td class="px-3 py-2 text-right font-semibold">{{ $r['rate'] === null ? '—' : $r['rate'].'%' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-gray-50 border-t border-gray-200 text-xs font-semibold text-gray-700">
            <tr>
                <td class="px-3 py-2" colspan="2">মোট</td>
                <td class="px-3 py-2 text-right">{{ number_format($totals['visitors']) }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($totals['visits']) }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($totals['orders']) }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($totals['revenue'], 0) }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($totals['wholesale']) }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($totals['combo']) }}</td>
                <td class="px-3 py-2"></td>
            </tr>
        </tfoot>
    </table>
</div>
<p class="text-[11px] text-gray-400 mt-2">অর্ডার = বাতিল ও ট্র্যাশ বাদে। কনভার্সন % = (অর্ডার + পাইকারি + কম্বো এনকোয়ারি) ÷ ইউনিক ভিজিটর। অর্ডার/এনকোয়ারি ক্যাম্পেইনের সাথে মেলানো হয় তাতে সংরক্ষিত UTM দিয়ে।</p>
@endif
@endsection
