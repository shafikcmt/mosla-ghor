<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অর্ডার সম্পন্ন — {{ $siteName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@400;600;700&family=Noto+Sans+Bengali:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body           { font-family: 'Noto Sans Bengali', sans-serif; background: #fef9ee; }
        .font-serif-bn { font-family: 'Noto Serif Bengali', serif; }
        .gold-rule     { height: 1px; background: linear-gradient(90deg, transparent, #c9a227, transparent); }
        @keyframes checkIn { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .check-anim { animation: checkIn .4s cubic-bezier(.34,1.56,.64,1) .1s both; }

        @media print {
            .no-print  { display: none !important; }
            nav, footer { display: none !important; }
            body       { background: white !important; font-family: 'Noto Sans Bengali', sans-serif; }
            .receipt-card {
                box-shadow: none !important;
                border: 1px solid #e5e7eb !important;
                border-radius: 0 !important;
                max-width: 100% !important;
            }
            .receipt-header { background: #1a1a1a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .receipt-totals { background: #1a1a1a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 1.5cm; size: A4 portrait; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

@php
    $methodLabels = [
        'cash_on_delivery' => 'ক্যাশ অন ডেলিভারি',
        'bkash'            => 'বিকাশ',
        'rocket'           => 'রকেট',
        'nagad'            => 'নগদ',
    ];
    $paymentStatusLabels = [
        'pending'  => 'পেমেন্ট অপেক্ষায়',
        'verified' => 'পেমেন্ট যাচাই হয়েছে',
        'failed'   => 'পেমেন্ট ব্যর্থ',
    ];
    $addressParts = array_filter([
        $order->division_name, $order->district_name,
        $order->upazila_name, $order->union_name,
    ]);
    $whatsappLink = $whatsappNumber
        ? 'https://wa.me/' . preg_replace('/\D/', '', $whatsappNumber) . '?text=' . rawurlencode('আমার অর্ডার নম্বর: ' . $order->order_number . ' — একটু জানাবেন কি?')
        : null;
@endphp

{{-- Minimal nav (hidden on print) --}}
<nav class="no-print bg-[#0f3d22] py-4 px-5 shadow-lg">
    <div class="max-w-3xl mx-auto flex items-center justify-between">
        <a href="/" class="font-serif-bn text-[#c9a227] text-xl font-bold">{{ $siteName }}</a>
        <a href="/" class="text-green-400 hover:text-[#c9a227] text-xs transition-colors">← হোমে ফিরুন</a>
    </div>
</nav>

<main class="flex-1 py-10 px-4">
    <div class="max-w-2xl mx-auto">

        {{-- Success header (hidden on print — replaced by receipt header) --}}
        <div class="no-print text-center mb-8">
            <div class="check-anim w-20 h-20 rounded-full bg-[#14532d] flex items-center justify-center mx-auto mb-5 shadow-xl">
                <svg class="w-10 h-10 text-[#c9a227]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="font-serif-bn text-[#14532d] text-3xl md:text-4xl font-bold mb-2">অর্ডার সম্পন্ন হয়েছে!</h1>
            <p class="text-gray-600 text-sm max-w-sm mx-auto">আপনার অর্ডার সফলভাবে গ্রহণ করা হয়েছে। শীঘ্রই কল করে নিশ্চিত করা হবে।</p>
        </div>

        {{-- Receipt / Order card --}}
        <div class="receipt-card bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden mb-6">

            {{-- Receipt header --}}
            <div class="receipt-header bg-[#0f3d22] px-6 py-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-serif-bn text-[#c9a227] font-bold text-lg">{{ $siteName }}</p>
                        <p class="text-green-400 text-xs mt-0.5">অর্ডার রসিদ / Order Receipt</p>
                    </div>
                    <div class="text-right">
                        <p class="font-serif-bn text-[#c9a227] font-bold text-base">{{ $order->order_number }}</p>
                        <p class="text-green-400 text-[10px] mt-0.5">{{ $order->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                </div>
            </div>

            {{-- Customer info --}}
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
                    <div class="text-gray-800">{{ $order->alternative_number }}</div>
                </div>
                @endif
                @if(count($addressParts))
                <div class="sm:col-span-2">
                    <div class="text-gray-400 text-[10px] uppercase tracking-wider mb-0.5">বিভাগ / জেলা / উপজেলা</div>
                    <div class="text-gray-700">{{ implode(' › ', $addressParts) }}</div>
                </div>
                @endif
                @if($order->delivery_zone_name || $order->delivery_location_name)
                <div class="sm:col-span-2">
                    <div class="text-gray-400 text-[10px] uppercase tracking-wider mb-0.5">ডেলিভারি এলাকা</div>
                    <div class="text-gray-700 font-semibold">
                        {{ implode(', ', array_filter([$order->delivery_location_name, $order->delivery_zone_name])) }}
                    </div>
                </div>
                @endif
                <div class="sm:col-span-2">
                    <div class="text-gray-400 text-[10px] uppercase tracking-wider mb-0.5">বাড়ি / রাস্তা</div>
                    <div class="text-gray-800 font-semibold">{{ $order->full_address }}</div>
                </div>
                @if($order->order_note)
                <div class="sm:col-span-2">
                    <div class="text-gray-400 text-[10px] uppercase tracking-wider mb-0.5">বিশেষ নির্দেশনা</div>
                    <div class="text-gray-600 italic">{{ $order->order_note }}</div>
                </div>
                @endif
            </div>

            {{-- Items table --}}
            <div class="border-b border-gray-100">
                <div class="bg-gray-50 px-6 py-2.5">
                    <h3 class="text-[#14532d] text-[10px] font-bold uppercase tracking-widest">অর্ডার তালিকা</h3>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="px-6 py-2 text-left text-[10px] text-gray-400 uppercase tracking-wider font-medium">পণ্য</th>
                            <th class="px-6 py-2 text-center text-[10px] text-gray-400 uppercase tracking-wider font-medium">পরিমাণ</th>
                            <th class="px-6 py-2 text-right text-[10px] text-gray-400 uppercase tracking-wider font-medium">মূল্য</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="px-6 py-3 font-serif-bn text-[#14532d] font-bold">{{ $item->product_name }}</td>
                            <td class="px-6 py-3 text-center text-gray-500 text-xs">
                                @if($item->quantity_gram >= 1000)
                                    {{ $item->quantity_gram / 1000 }} কেজি
                                @else
                                    {{ $item->quantity_gram }} গ্রাম
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right font-bold text-[#c9a227] font-serif-bn">৳{{ number_format($item->unit_price, 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="receipt-totals bg-[#0f3d22] px-6 py-5 space-y-2">
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
                @if($order->cod_charge)
                <div class="flex justify-between text-sm text-green-400">
                    <span>COD চার্জ</span>
                    <span>৳{{ number_format($order->cod_charge, 0) }}</span>
                </div>
                @endif
                @if($order->payment_discount > 0)
                <div class="flex justify-between text-sm text-[#c9a227]">
                    <span>পেমেন্ট ডিসকাউন্ট</span>
                    <span>- ৳{{ number_format($order->payment_discount, 0) }}</span>
                </div>
                @endif
                <div class="gold-rule opacity-30 my-1"></div>
                <div class="flex justify-between font-bold text-[#c9a227] font-serif-bn text-xl">
                    <span>সর্বমোট</span>
                    <span>৳{{ number_format($order->grand_total, 0) }}</span>
                </div>

                <div class="pt-2 space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="text-green-400 text-xs">পেমেন্ট পদ্ধতি:</span>
                        <span class="text-green-300 text-xs font-semibold">{{ $methodLabels[$order->payment_method] ?? $order->payment_method }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-green-400 text-xs">পেমেন্ট স্ট্যাটাস:</span>
                        <span class="text-green-300 text-xs font-semibold">{{ $paymentStatusLabels[$order->payment_status] ?? $order->payment_status }}</span>
                    </div>
                    @if($order->estimated_delivery)
                    <div class="flex items-center gap-2">
                        <span class="text-green-400 text-xs">আনুমানিক ডেলিভারি:</span>
                        <span class="text-green-300 text-xs font-semibold">{{ $order->estimated_delivery }}</span>
                    </div>
                    @endif
                    @if($order->transaction_id)
                    <div class="flex items-center gap-2">
                        <span class="text-green-400 text-xs">TrxID:</span>
                        <span class="text-green-300 text-xs font-mono">{{ $order->transaction_id }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Print footer (visible only on print) --}}
            <div class="hidden print:block px-6 py-3 bg-gray-50 border-t border-gray-200">
                <p class="text-xs text-gray-400 text-center">প্রিন্ট তারিখ: {{ now()->format('d M Y, h:i A') }} — {{ $siteName }}</p>
            </div>
        </div>

        {{-- Action buttons (hidden on print) --}}
        <div class="no-print flex flex-col sm:flex-row items-center justify-center gap-3 mt-4">
            <button onclick="window.print()"
                    class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:border-gray-400 text-gray-700 font-semibold px-6 py-3 rounded-full transition-colors shadow-sm text-sm">
                🖨️ রসিদ প্রিন্ট করুন
            </button>

            @if($whatsappLink)
            <a href="{{ $whatsappLink }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#1ebe5d] text-white font-semibold px-6 py-3 rounded-full transition-colors shadow-sm text-sm">
                💬 WhatsApp-এ যোগাযোগ
            </a>
            @endif

            <a href="/"
               class="inline-flex items-center gap-2 bg-[#14532d] hover:bg-[#166534] text-[#fef9ee] font-semibold px-6 py-3 rounded-full transition-colors shadow-lg text-sm">
                ← হোম পেজে ফিরুন
            </a>
        </div>

        <p class="no-print text-center text-gray-400 text-xs mt-4">এই অর্ডার নম্বরটি সংরক্ষণ করুন: <strong class="text-gray-600">{{ $order->order_number }}</strong></p>

    </div>
</main>

<footer class="no-print bg-[#0f3d22] py-6 px-5 mt-8">
    <p class="text-center text-green-700 text-xs">&copy; {{ date('Y') }} {{ $siteName }} — সমস্ত অধিকার সংরক্ষিত।</p>
</footer>

</body>
</html>
