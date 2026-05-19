<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>চালান — {{ $order->order_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@400;600;700&family=Noto+Sans+Bengali:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body           { font-family: 'Noto Sans Bengali', sans-serif; background: #f3f4f6; }
        .font-serif-bn { font-family: 'Noto Serif Bengali', serif; }

        @media print {
            .no-print  { display: none !important; }
            body       { background: white !important; }
            .invoice   { box-shadow: none !important; margin: 0 !important; max-width: 100% !important; border: none !important; }
            .page-break { page-break-before: always; }
            @page { margin: 1.5cm; size: A4 portrait; }
        }
    </style>
</head>
<body class="min-h-screen py-8 px-4">

{{-- Action bar (hidden on print) --}}
<div class="no-print max-w-3xl mx-auto mb-4 flex items-center justify-between">
    <a href="{{ route('admin.orders.show', $order) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 bg-white border border-gray-300 px-4 py-2 rounded shadow-sm transition-colors">
        ← অর্ডারে ফিরুন
    </a>
    <button onclick="window.print()"
            class="inline-flex items-center gap-2 text-sm text-white bg-gray-800 hover:bg-gray-700 px-5 py-2 rounded shadow-sm transition-colors">
        🖨️ প্রিন্ট করুন
    </button>
</div>

@php
    $methodLabels = ['cash_on_delivery' => 'ক্যাশ অন ডেলিভারি', 'bkash' => 'বিকাশ', 'rocket' => 'রকেট', 'nagad' => 'নগদ'];
    $paymentStatusLabels = ['pending' => 'পেমেন্ট অপেক্ষায়', 'verified' => 'পেমেন্ট যাচাই হয়েছে', 'failed' => 'পেমেন্ট ব্যর্থ'];
    $orderStatusLabels   = ['pending' => 'অপেক্ষায়', 'confirmed' => 'নিশ্চিত', 'processing' => 'প্রসেসিং', 'shipped' => 'শিপড', 'delivered' => 'ডেলিভারড', 'cancelled' => 'বাতিল'];
    $courierStatusLabels = [
        'pending' => 'অপেক্ষায়', 'processing' => 'প্রসেসিং', 'ready_for_courier' => 'কুরিয়ার প্রস্তুত',
        'sent_to_courier' => 'কুরিয়ারে পাঠানো', 'picked_up' => 'পিক-আপ হয়েছে',
        'in_transit' => 'ট্রানজিটে', 'delivered' => 'ডেলিভারড',
        'returned' => 'ফেরত', 'cancelled' => 'বাতিল', 'failed_delivery' => 'ডেলিভারি ব্যর্থ',
    ];

    $addressParts = array_filter([$order->division_name, $order->district_name, $order->upazila_name, $order->union_name]);
    $addressChain = implode(' › ', $addressParts);
@endphp

{{-- Invoice card --}}
<div class="invoice max-w-3xl mx-auto bg-white shadow-lg rounded border border-gray-200">

    {{-- ── Header ── --}}
    <div class="px-8 pt-8 pb-5 border-b-2 border-gray-800">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="font-serif-bn text-2xl font-bold text-gray-900">{{ $siteName }}</h1>
                @if($sitePhone)
                <p class="text-gray-500 text-xs mt-0.5">ফোন: {{ $sitePhone }}</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500 uppercase tracking-widest font-semibold">চালান / Invoice</p>
                <p class="font-serif-bn font-bold text-gray-900 text-lg mt-0.5">{{ $order->order_number }}</p>
                <p class="text-gray-400 text-xs mt-0.5">তারিখ: {{ $order->created_at->format('d M Y, h:i A') }}</p>
            </div>
        </div>
        {{-- Status row --}}
        <div class="flex flex-wrap gap-2 mt-4">
            <span class="px-2 py-0.5 bg-gray-100 border border-gray-300 text-gray-700 text-[10px] font-semibold rounded uppercase tracking-wide">
                অর্ডার: {{ $orderStatusLabels[$order->order_status] ?? $order->order_status }}
            </span>
            <span class="px-2 py-0.5 bg-gray-100 border border-gray-300 text-gray-700 text-[10px] font-semibold rounded uppercase tracking-wide">
                পেমেন্ট: {{ $paymentStatusLabels[$order->payment_status] ?? $order->payment_status }}
            </span>
            @if($order->courier_status)
            <span class="px-2 py-0.5 bg-gray-100 border border-gray-300 text-gray-700 text-[10px] font-semibold rounded uppercase tracking-wide">
                কুরিয়ার: {{ $courierStatusLabels[$order->courier_status] ?? $order->courier_status }}
            </span>
            @endif
        </div>
    </div>

    {{-- ── Customer & Address ── --}}
    <div class="px-8 py-5 border-b border-gray-200">
        <h2 class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">গ্রাহকের তথ্য</h2>
        <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
            <div>
                <span class="text-gray-500 text-xs">নাম:</span>
                <span class="ml-1 font-semibold text-gray-900">{{ $order->customer_name }}</span>
            </div>
            <div>
                <span class="text-gray-500 text-xs">মোবাইল:</span>
                <span class="ml-1 font-semibold text-gray-900">{{ $order->mobile_number }}</span>
            </div>
            @if($order->alternative_number)
            <div>
                <span class="text-gray-500 text-xs">বিকল্প নম্বর:</span>
                <span class="ml-1 text-gray-800">{{ $order->alternative_number }}</span>
            </div>
            @endif
            @if($addressChain)
            <div class="{{ $order->alternative_number ? '' : 'col-span-2' }}">
                <span class="text-gray-500 text-xs">বিভাগ / জেলা / উপজেলা:</span>
                <span class="ml-1 text-gray-800">{{ $addressChain }}</span>
            </div>
            @endif
            @if($order->delivery_zone_name || $order->delivery_location_name)
            <div class="col-span-2">
                <span class="text-gray-500 text-xs">ডেলিভারি এলাকা:</span>
                <span class="ml-1 text-gray-800">
                    {{ implode(', ', array_filter([$order->delivery_location_name, $order->delivery_zone_name])) }}
                </span>
            </div>
            @endif
            <div class="col-span-2">
                <span class="text-gray-500 text-xs">বাড়ি / রাস্তা:</span>
                <span class="ml-1 text-gray-800">{{ $order->full_address }}</span>
            </div>
        </div>
    </div>

    {{-- ── Order Items ── --}}
    <div class="px-8 py-5 border-b border-gray-200">
        <h2 class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">অর্ডারের পণ্য</h2>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-300">
                    <th class="pb-2 text-left text-xs font-bold text-gray-700">পণ্যের নাম</th>
                    <th class="pb-2 text-center text-xs font-bold text-gray-700">পরিমাণ</th>
                    <th class="pb-2 text-right text-xs font-bold text-gray-700">একক মূল্য</th>
                    <th class="pb-2 text-right text-xs font-bold text-gray-700">মোট</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr class="border-b border-gray-100 last:border-0">
                    <td class="py-2 font-serif-bn text-gray-900 font-semibold">{{ $item->product_name }}</td>
                    <td class="py-2 text-center text-gray-600">
                        @if($item->quantity_gram >= 1000)
                            {{ $item->quantity_gram / 1000 }} কেজি
                        @else
                            {{ $item->quantity_gram }} গ্রাম
                        @endif
                    </td>
                    <td class="py-2 text-right text-gray-700">৳ {{ number_format($item->unit_price, 0) }}</td>
                    <td class="py-2 text-right font-bold text-gray-900">৳ {{ number_format($item->line_total, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── Cost Breakdown ── --}}
    <div class="px-8 py-5 border-b border-gray-200">
        <div class="ml-auto max-w-xs space-y-1.5 text-sm">
            <div class="flex justify-between text-gray-600">
                <span>সাবটোটাল</span><span>৳ {{ number_format($order->subtotal, 0) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>প্যাকেজিং</span><span>৳ {{ number_format($order->packaging_cost, 0) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>ডেলিভারি চার্জ</span><span>৳ {{ number_format($order->delivery_charge, 0) }}</span>
            </div>
            @if($order->cod_charge)
            <div class="flex justify-between text-gray-600">
                <span>COD চার্জ</span><span>৳ {{ number_format($order->cod_charge, 0) }}</span>
            </div>
            @endif
            <div class="flex justify-between font-bold text-gray-900 border-t border-gray-800 pt-2 text-base">
                <span>সর্বমোট</span><span>৳ {{ number_format($order->grand_total, 0) }}</span>
            </div>
        </div>
    </div>

    {{-- ── Payment & Courier ── --}}
    <div class="px-8 py-5 border-b border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
        {{-- Payment --}}
        <div>
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">পেমেন্ট তথ্য</h2>
            <div class="space-y-1.5">
                <div>
                    <span class="text-gray-500 text-xs">পদ্ধতি:</span>
                    <span class="ml-1 font-semibold text-gray-800">{{ $methodLabels[$order->payment_method] ?? $order->payment_method }}</span>
                </div>
                <div>
                    <span class="text-gray-500 text-xs">স্ট্যাটাস:</span>
                    <span class="ml-1 text-gray-800">{{ $paymentStatusLabels[$order->payment_status] ?? $order->payment_status }}</span>
                </div>
                @if($order->sender_number)
                <div>
                    <span class="text-gray-500 text-xs">সেন্ডার নম্বর:</span>
                    <span class="ml-1 text-gray-800">{{ $order->sender_number }}</span>
                </div>
                @endif
                @if($order->transaction_id)
                <div>
                    <span class="text-gray-500 text-xs">TrxID:</span>
                    <span class="ml-1 font-mono text-gray-800">{{ $order->transaction_id }}</span>
                </div>
                @endif
                @if($order->paid_amount !== null)
                <div>
                    <span class="text-gray-500 text-xs">পেমেন্ট করা:</span>
                    <span class="ml-1 font-bold text-gray-900">৳ {{ number_format($order->paid_amount, 0) }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Courier --}}
        <div>
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">কুরিয়ার তথ্য</h2>
            <div class="space-y-1.5">
                @if($order->selectedCourier)
                <div>
                    <span class="text-gray-500 text-xs">কুরিয়ার:</span>
                    <span class="ml-1 font-semibold text-gray-800">{{ $order->selectedCourier->name }}</span>
                </div>
                @endif
                @if($order->courier_status)
                <div>
                    <span class="text-gray-500 text-xs">স্ট্যাটাস:</span>
                    <span class="ml-1 text-gray-800">{{ $courierStatusLabels[$order->courier_status] ?? $order->courier_status }}</span>
                </div>
                @endif
                @if($order->tracking_id)
                <div>
                    <span class="text-gray-500 text-xs">ট্র্যাকিং:</span>
                    <span class="ml-1 font-mono text-gray-800">{{ $order->tracking_id }}</span>
                </div>
                @endif
                @if($order->consignment_id)
                <div>
                    <span class="text-gray-500 text-xs">Consignment:</span>
                    <span class="ml-1 font-mono text-gray-800">{{ $order->consignment_id }}</span>
                </div>
                @endif
                @if($order->sent_to_courier_at)
                <div>
                    <span class="text-gray-500 text-xs">পাঠানোর তারিখ:</span>
                    <span class="ml-1 text-gray-800">{{ $order->sent_to_courier_at->format('d M Y') }}</span>
                </div>
                @endif
                @if(!$order->selectedCourier && !$order->courier_status)
                <span class="text-gray-400 text-xs">কুরিয়ার নির্বাচিত হয়নি।</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Notes ── --}}
    @if($order->order_note || $order->courier_note)
    <div class="px-8 py-4 border-b border-gray-200 text-sm space-y-2">
        @if($order->order_note)
        <div>
            <span class="text-gray-500 text-xs font-semibold uppercase tracking-wide">গ্রাহকের নোট:</span>
            <span class="ml-2 text-gray-700 italic">{{ $order->order_note }}</span>
        </div>
        @endif
        @if($order->courier_note)
        <div>
            <span class="text-gray-500 text-xs font-semibold uppercase tracking-wide">কুরিয়ার নোট:</span>
            <span class="ml-2 text-gray-700 italic">{{ $order->courier_note }}</span>
        </div>
        @endif
    </div>
    @endif

    {{-- ── Footer ── --}}
    <div class="px-8 py-4 bg-gray-50 flex items-center justify-between text-xs text-gray-400">
        <span>{{ $siteName }}</span>
        <span>প্রিন্ট তারিখ: {{ now()->format('d M Y, h:i A') }}</span>
    </div>

</div>

{{-- Bottom spacer for screen view --}}
<div class="no-print h-8"></div>

</body>
</html>
