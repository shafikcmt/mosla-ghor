<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অর্ডার সম্পন্ন — মসলা ঘর</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@400;600;700&family=Noto+Sans+Bengali:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body           { font-family: 'Noto Sans Bengali', sans-serif; background: #fef9ee; }
        .font-serif-bn { font-family: 'Noto Serif Bengali', serif; }
        .gold-rule     { height: 1px; background: linear-gradient(90deg, transparent, #c9a227, transparent); }
        @keyframes checkIn { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .check-anim { animation: checkIn .4s cubic-bezier(.34,1.56,.64,1) .1s both; }
    </style>
</head>
<body class="min-h-screen flex flex-col">

{{-- Minimal nav --}}
<nav class="bg-[#0f3d22] py-4 px-5 shadow-lg">
    <div class="max-w-3xl mx-auto flex items-center justify-between">
        <a href="/" class="font-serif-bn text-[#c9a227] text-xl font-bold">মসলা ঘর</a>
        <a href="/" class="text-green-400 hover:text-[#c9a227] text-xs transition-colors">← হোমে ফিরুন</a>
    </div>
</nav>

<main class="flex-1 py-12 px-4">
    <div class="max-w-2xl mx-auto">

        {{-- Success header --}}
        <div class="text-center mb-10">
            <div class="check-anim w-20 h-20 rounded-full bg-[#14532d] flex items-center justify-center mx-auto mb-5 shadow-xl">
                <svg class="w-10 h-10 text-[#c9a227]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="font-serif-bn text-[#14532d] text-3xl md:text-4xl font-bold mb-2">অর্ডার সম্পন্ন হয়েছে!</h1>
            <p class="text-gray-500 text-sm max-w-sm mx-auto">ধন্যবাদ। আমরা শীঘ্রই আপনার সাথে যোগাযোগ করব।</p>
            <div class="mt-5 inline-block bg-[#14532d] text-[#c9a227] font-serif-bn font-bold text-xl px-7 py-3 rounded-xl shadow-lg tracking-wide">
                {{ $order->order_number }}
            </div>
            <p class="text-gray-400 text-xs mt-2">এই অর্ডার নম্বরটি সংরক্ষণ করুন।</p>
        </div>

        {{-- Order card --}}
        <div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden mb-8">

            {{-- Customer info --}}
            <div class="bg-[#14532d] px-6 py-3">
                <h3 class="text-[#c9a227] text-xs font-bold uppercase tracking-widest">গ্রাহকের তথ্য</h3>
            </div>
            <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm border-b border-gray-100">
                <div>
                    <div class="text-gray-400 text-[10px] uppercase tracking-wider mb-0.5">নাম</div>
                    <div class="text-gray-800 font-semibold">{{ $order->customer_name }}</div>
                </div>
                <div>
                    <div class="text-gray-400 text-[10px] uppercase tracking-wider mb-0.5">মোবাইল</div>
                    <div class="text-gray-800 font-semibold">{{ $order->mobile_number }}</div>
                </div>
                @if($order->alternative_number)
                <div>
                    <div class="text-gray-400 text-[10px] uppercase tracking-wider mb-0.5">বিকল্প নম্বর</div>
                    <div class="text-gray-800 font-semibold">{{ $order->alternative_number }}</div>
                </div>
                @endif
                <div class="{{ $order->alternative_number ? '' : 'sm:col-span-2' }}">
                    <div class="text-gray-400 text-[10px] uppercase tracking-wider mb-0.5">ঠিকানা</div>
                    <div class="text-gray-800 font-semibold">{{ $order->full_address }}</div>
                    <div class="text-gray-500 text-xs mt-0.5">{{ $order->area }}, {{ $order->district }}</div>
                </div>
                @if($order->delivery_zone_name || $order->delivery_location_name)
                <div>
                    <div class="text-gray-400 text-[10px] uppercase tracking-wider mb-0.5">ডেলিভারি জোন</div>
                    <div class="text-gray-800 font-semibold">{{ $order->delivery_zone_name ?: '—' }}</div>
                    @if($order->delivery_location_name)
                        <div class="text-gray-500 text-xs mt-0.5">{{ $order->delivery_location_name }}</div>
                    @endif
                </div>
                @endif
                @if($order->order_note)
                <div class="sm:col-span-2">
                    <div class="text-gray-400 text-[10px] uppercase tracking-wider mb-0.5">বিশেষ নির্দেশনা</div>
                    <div class="text-gray-600 italic text-sm">{{ $order->order_note }}</div>
                </div>
                @endif
            </div>

            {{-- Items table --}}
            <div class="border-b border-gray-100">
                <div class="bg-gray-50 px-6 py-3">
                    <h3 class="text-[#14532d] text-xs font-bold uppercase tracking-widest">অর্ডার তালিকা</h3>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="px-6 py-2.5 text-left text-[10px] text-gray-400 uppercase tracking-wider font-medium">পণ্য</th>
                            <th class="px-6 py-2.5 text-center text-[10px] text-gray-400 uppercase tracking-wider font-medium">পরিমাণ</th>
                            <th class="px-6 py-2.5 text-right text-[10px] text-gray-400 uppercase tracking-wider font-medium">মূল্য</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="px-6 py-3 font-serif-bn text-[#14532d] font-bold">{{ $item->product_name }}</td>
                            <td class="px-6 py-3 text-center text-gray-500 text-xs">{{ $item->quantity_gram }}g</td>
                            <td class="px-6 py-3 text-right font-bold text-[#c9a227] font-serif-bn">৳{{ number_format($item->unit_price, 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="bg-[#0f3d22] px-6 py-5 space-y-2.5">
                <div class="flex justify-between text-sm text-green-400">
                    <span>সাবটোটাল</span>
                    <span>৳{{ number_format($order->subtotal, 0) }}</span>
                </div>
                <div class="flex justify-between text-sm text-green-400">
                    <span>প্যাকেজিং চার্জ</span>
                    <span>৳{{ number_format($order->packaging_cost, 0) }}</span>
                </div>
                @if($order->delivery_charge > 0)
                <div class="flex justify-between text-sm text-green-400">
                    <span>ডেলিভারি চার্জ</span>
                    <span>৳{{ number_format($order->delivery_charge, 0) }}</span>
                </div>
                @endif
                <div class="gold-rule opacity-30"></div>
                <div class="flex justify-between font-bold text-[#c9a227] font-serif-bn text-xl">
                    <span>সর্বমোট</span>
                    <span>৳{{ number_format($order->grand_total, 0) }}</span>
                </div>
                @php
                    $methodLabels = [
                        'cash_on_delivery' => 'ক্যাশ অন ডেলিভারি',
                        'bkash'            => 'বিকাশ',
                        'rocket'           => 'রকেট',
                        'nagad'            => 'নগদ',
                    ];
                @endphp
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-green-500 text-xs">💳</span>
                    <span class="text-green-500 text-xs">পেমেন্ট পদ্ধতি: {{ $methodLabels[$order->payment_method] ?? $order->payment_method }}</span>
                </div>
                @if($order->transaction_id)
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-green-500 text-xs">🔖</span>
                    <span class="text-green-500 text-xs">ট্রানজেকশন আইডি: {{ $order->transaction_id }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Back button --}}
        <div class="text-center">
            <a href="/"
               class="inline-flex items-center gap-2 bg-[#14532d] hover:bg-[#166534] text-[#fef9ee] font-semibold px-8 py-3.5 rounded-full transition-colors shadow-lg text-sm">
                ← হোম পেজে ফিরে যান
            </a>
        </div>

    </div>
</main>

<footer class="bg-[#0f3d22] py-6 px-5 mt-8">
    <p class="text-center text-green-700 text-xs">&copy; {{ date('Y') }} মসলা ঘর — সমস্ত অধিকার সংরক্ষিত।</p>
</footer>

</body>
</html>
