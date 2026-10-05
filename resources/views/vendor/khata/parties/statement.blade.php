@extends('vendor.khata.layout')
@section('title', 'হিসাব বিবরণী — '.$party->name)
@section('heading', 'হিসাব বিবরণী')
@section('subheading', $party->name)
@section('back', route('vendor.khata.parties.show', $party))

@section('content')
@php $tk = fn ($n) => '৳'.number_format((float) $n, 0); $final = $rows->last()['balance'] ?? (float) $party->opening_balance; @endphp
<div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 sm:p-6">
    <div class="flex items-start justify-between gap-3 border-b pb-3">
        <div>
            <p class="text-lg font-bold">{{ $vendor->shop_name }}</p>
            <p class="text-sm text-gray-500">{{ $vendor->phone }}@if($vendor->address) · {{ $vendor->address }}@endif</p>
        </div>
        <button onclick="window.print()" class="no-print text-sm bg-gray-800 text-white px-3 py-2 rounded-xl">🖨 প্রিন্ট / PDF</button>
    </div>
    <div class="flex flex-wrap justify-between gap-2 py-3 text-sm">
        <p>পার্টি: <b>{{ $party->name }}</b> {{ $party->phone }}</p>
        <p>তারিখ: {{ now()->format('d M Y') }}</p>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs">
            <tr><th class="text-left p-2">তারিখ</th><th class="text-left p-2">বিবরণ</th><th class="text-right p-2">মোট</th><th class="text-right p-2">পরিশোধ</th><th class="text-right p-2">ব্যালেন্স</th></tr>
        </thead>
        <tbody class="divide-y">
            <tr><td class="p-2">{{ optional($party->opening_date)->format('d M Y') }}</td><td class="p-2">প্রারম্ভিক ব্যালেন্স</td><td></td><td></td>
                <td class="p-2 text-right font-semibold num">{{ $tk(abs((float) $party->opening_balance)) }} {{ (float) $party->opening_balance < 0 ? 'বকেয়া' : 'পাওনা' }}</td></tr>
            @foreach($rows as $r)
            @php $tx = $r['tx']; @endphp
            <tr>
                <td class="p-2 whitespace-nowrap">{{ $tx->date->format('d M Y') }}</td>
                <td class="p-2">{{ $tx->typeLabel() }}{{ $tx->number ? ' #'.$tx->number : '' }}</td>
                <td class="p-2 text-right num">{{ in_array($tx->type, ['sale', 'purchase'], true) ? $tk($tx->total) : '' }}</td>
                <td class="p-2 text-right num">{{ (float) $tx->paid > 0 ? $tk($tx->paid) : '' }}</td>
                <td class="p-2 text-right font-semibold num {{ $r['balance'] < 0 ? 'text-red-600' : '' }}">{{ $tk(abs($r['balance'])) }} {{ $r['balance'] < 0 ? 'বকেয়া' : 'পাওনা' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div class="mt-4 text-right">
        <p class="text-sm text-gray-500">{{ $final < 0 ? 'মোট বকেয়া (আমাদের দেনা)' : 'মোট পাওনা' }}</p>
        <p class="text-2xl font-bold num">{{ $tk(abs($final)) }}</p>
        <p class="text-xs text-gray-500">{{ \App\Support\BanglaNumber::taka(abs($final)) }}</p>
    </div>
</div>
@endsection
