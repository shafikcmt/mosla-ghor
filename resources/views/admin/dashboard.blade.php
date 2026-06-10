@extends('admin.layout')

@section('title', 'ড্যাশবোর্ড')

@section('content')

<h1 class="text-xl font-bold text-gray-800 mb-6">ড্যাশবোর্ড</h1>

{{-- ── Paykari enquiry widgets ──────────────────────────────────────── --}}
@isset($enquiryStats)
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <a href="{{ route('admin.wholesale.enquiry.index', ['status' => 'pending']) }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 hover:border-amber-300 transition-colors">
        <p class="text-xs text-gray-400">নতুন Enquiry</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">{{ $enquiryStats['new_enquiries'] }}</p>
    </a>
    <a href="{{ route('admin.wholesale.quote.index', ['status' => 'sent_to_customer']) }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 hover:border-blue-300 transition-colors">
        <p class="text-xs text-gray-400">নতুন Quote</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">{{ $enquiryStats['new_quotes'] }}</p>
    </a>
    <a href="{{ route('admin.wholesale.chat.index') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 hover:border-indigo-300 transition-colors">
        <p class="text-xs text-gray-400">নতুন বার্তা</p>
        <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $enquiryStats['new_messages'] }}</p>
    </a>
    <a href="{{ route('admin.wholesale.quote.index', ['status' => 'converted_to_order']) }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 hover:border-green-300 transition-colors">
        <p class="text-xs text-gray-400">Confirm অর্ডার</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ $enquiryStats['confirmed_orders'] }}</p>
    </a>
</div>
@endisset

{{-- ── Stats grid ──────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">

    @php
    $cards = [
        ['label' => 'মোট অর্ডার',    'value' => $stats['total_orders'],    'color' => 'bg-gray-800  text-white'],
        ['label' => 'অপেক্ষায়',      'value' => $stats['pending'],         'color' => 'bg-yellow-50 text-yellow-800 border border-yellow-200'],
        ['label' => 'নিশ্চিত',        'value' => $stats['confirmed'],       'color' => 'bg-blue-50   text-blue-800   border border-blue-200'],
        ['label' => 'প্রসেসিং',       'value' => $stats['processing'],      'color' => 'bg-indigo-50 text-indigo-800 border border-indigo-200'],
        ['label' => 'শিপড',           'value' => $stats['shipped'],         'color' => 'bg-cyan-50   text-cyan-800   border border-cyan-200'],
        ['label' => 'ডেলিভারড',       'value' => $stats['delivered'],       'color' => 'bg-green-50  text-green-800  border border-green-200'],
        ['label' => 'বাতিল',          'value' => $stats['cancelled'],       'color' => 'bg-red-50    text-red-800    border border-red-200'],
        ['label' => 'মোট বিক্রয়',    'value' => '৳' . number_format($stats['total_sales'], 0), 'color' => 'bg-emerald-700 text-white'],
        ['label' => 'আজকের বিক্রয়',  'value' => '৳' . number_format($stats['today_sales'], 0), 'color' => 'bg-emerald-50  text-emerald-800 border border-emerald-200'],
        ['label' => 'আজকের অর্ডার',  'value' => $stats['today_orders'],    'color' => 'bg-amber-50  text-amber-800  border border-amber-200'],
        ['label' => 'মোট পণ্য',       'value' => $stats['total_products'],  'color' => 'bg-white     text-gray-700   border border-gray-200'],
        ['label' => 'সক্রিয় পণ্য',   'value' => $stats['active_products'], 'color' => 'bg-white     text-gray-700   border border-gray-200'],
        ['label' => 'মোট কম্বো',      'value' => $stats['total_combos'],   'color' => 'bg-white     text-gray-700   border border-gray-200'],
        ['label' => 'সক্রিয় কম্বো',  'value' => $stats['active_combos'],  'color' => 'bg-white     text-gray-700   border border-gray-200'],
    ];
    @endphp

    @foreach($cards as $card)
    <div class="rounded-lg px-4 py-4 {{ $card['color'] }} shadow-sm">
        <div class="text-2xl font-bold leading-none mb-1">{{ $card['value'] }}</div>
        <div class="text-xs font-medium opacity-70 mt-1">{{ $card['label'] }}</div>
    </div>
    @endforeach

</div>

{{-- ── Quick links ─────────────────────────────────────────────────── --}}
<div class="bg-white rounded shadow-sm border border-gray-100 px-5 py-4 mb-8">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">দ্রুত লিংক</p>
    <div class="flex flex-wrap gap-2">
        @foreach([
            ['পণ্য',              route('admin.products.index')],
            ['কম্বো',             route('admin.combos.index')],
            ['অর্ডার',            route('admin.orders.index')],
            ['পেমেন্ট সেটিং',    route('admin.payment-settings.index')],
            ['ডেলিভারি সেটিং',   route('admin.delivery-settings.index')],
            ['ডেলিভারি জোন',     route('admin.delivery-zones.index')],
            ['FAQ',               route('admin.faqs.index')],
            ['রিভিউ',             route('admin.reviews.index')],
            ['ওয়েব সেটিং',      route('admin.website-settings.index')],
        ] as [$label, $url])
        <a href="{{ $url }}"
           class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium px-3 py-1.5 rounded transition-colors">
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>

{{-- ── Recent orders ───────────────────────────────────────────────── --}}
<div class="bg-white rounded shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <p class="text-sm font-semibold text-gray-700">সাম্প্রতিক অর্ডার (সর্বশেষ ১০টি)</p>
        <a href="{{ route('admin.orders.index') }}" class="text-xs text-gray-500 hover:underline">সব দেখুন →</a>
    </div>

    @if($recentOrders->isEmpty())
        <p class="px-5 py-8 text-center text-gray-400 text-sm">কোনো অর্ডার নেই।</p>
    @else
    @php
        $oColors = [
            'pending'    => 'bg-yellow-100 text-yellow-700',
            'confirmed'  => 'bg-blue-100   text-blue-700',
            'processing' => 'bg-indigo-100 text-indigo-700',
            'shipped'    => 'bg-cyan-100   text-cyan-700',
            'delivered'  => 'bg-green-100  text-green-700',
            'cancelled'  => 'bg-red-100    text-red-700',
        ];
        $oLabels = [
            'pending'    => 'অপেক্ষায়',
            'confirmed'  => 'নিশ্চিত',
            'processing' => 'প্রসেসিং',
            'shipped'    => 'শিপড',
            'delivered'  => 'ডেলিভারড',
            'cancelled'  => 'বাতিল',
        ];
        $pColors = ['pending' => 'bg-yellow-100 text-yellow-700', 'verified' => 'bg-green-100 text-green-700', 'failed' => 'bg-red-100 text-red-700'];
        $pLabels = ['pending' => 'অপেক্ষায়', 'verified' => 'যাচাই', 'failed' => 'ব্যর্থ'];
    @endphp
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">অর্ডার নং</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">গ্রাহক</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">মোট</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">পেমেন্ট</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">স্ট্যাটাস</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">তারিখ</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($recentOrders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $order->order_number }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-800 text-xs">{{ $order->customer_name }}</div>
                        <div class="text-gray-400 text-xs">{{ $order->mobile_number }}</div>
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-gray-800 text-xs">৳{{ number_format($order->grand_total, 0) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded text-xs font-medium {{ $pColors[$order->payment_status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $pLabels[$order->payment_status] ?? $order->payment_status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded text-xs font-medium {{ $oColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $oLabels[$order->order_status] ?? $order->order_status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center text-xs text-gray-400">{{ $order->created_at->format('d M, h:i A') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.orders.show', $order) }}"
                           class="text-xs text-gray-600 hover:underline">দেখুন</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
