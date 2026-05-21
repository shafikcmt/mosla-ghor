<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>আমার অ্যাকাউন্ট — মসলা ঘর</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Noto Sans Bengali', sans-serif; background: #fef9ee; }</style>
</head>
<body class="min-h-screen">

{{-- Header --}}
<header class="bg-[#14532d] shadow-md">
    <div class="max-w-4xl mx-auto px-5 py-4 flex items-center justify-between">
        <a href="/" class="text-[#c9a227] text-xl font-bold">মসলা ঘর</a>
        <div class="flex items-center gap-3">
            <span class="text-green-200 text-sm hidden sm:inline">{{ $customer->name }}</span>
            <form method="POST" action="{{ route('customer.logout') }}">
                @csrf
                <button type="submit"
                        class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg transition-colors">
                    লগআউট
                </button>
            </form>
        </div>
    </div>
</header>

<main class="max-w-4xl mx-auto px-5 py-8">

    @if(session('success'))
    <div class="mb-5 bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    {{-- Profile card --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-[#14532d] flex items-center justify-center text-white text-xl font-bold">
                {{ mb_substr($customer->name, 0, 1) }}
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $customer->name }}</h2>
                <p class="text-gray-500 text-sm">{{ $customer->mobile_number }}</p>
                @if($customer->email)
                <p class="text-gray-400 text-xs">{{ $customer->email }}</p>
                @endif
            </div>
        </div>

        <div class="mt-4 grid grid-cols-3 gap-4 text-center border-t pt-4">
            <div>
                <p class="text-2xl font-bold text-[#14532d]">{{ $customer->total_orders ?? $orders->total() }}</p>
                <p class="text-xs text-gray-500 mt-0.5">মোট অর্ডার</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-[#14532d]">৳{{ number_format($customer->total_spent ?? 0, 0) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">মোট খরচ</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-[#14532d]">
                    @if($customer->last_order_at) {{ $customer->last_order_at->diffForHumans() }}
                    @else — @endif
                </p>
                <p class="text-xs text-gray-500 mt-0.5">সর্বশেষ অর্ডার</p>
            </div>
        </div>
    </div>

    {{-- Orders --}}
    <h3 class="text-lg font-bold text-gray-800 mb-3">আমার অর্ডার</h3>

    @if($orders->isEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">
        <p class="text-4xl mb-3">📦</p>
        <p class="text-sm">এখনো কোনো অর্ডার নেই।</p>
        <a href="/" class="mt-4 inline-block text-sm text-[#14532d] font-semibold hover:underline">পণ্য দেখুন →</a>
    </div>
    @else
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">অর্ডার নং</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">তারিখ</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">মোট</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">অবস্থা</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @php
                $oColors = [
                    'pending'    => 'bg-yellow-100 text-yellow-700',
                    'confirmed'  => 'bg-blue-100 text-blue-700',
                    'processing' => 'bg-indigo-100 text-indigo-700',
                    'shipped'    => 'bg-cyan-100 text-cyan-700',
                    'delivered'  => 'bg-green-100 text-green-700',
                    'cancelled'  => 'bg-red-100 text-red-700',
                ];
                $oLabels = [
                    'pending'    => 'অপেক্ষায়',
                    'confirmed'  => 'নিশ্চিত',
                    'processing' => 'প্রসেসিং',
                    'shipped'    => 'শিপড',
                    'delivered'  => 'ডেলিভারড',
                    'cancelled'  => 'বাতিল',
                ];
                @endphp
                @foreach($orders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $order->order_number }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs hidden sm:table-cell">{{ $order->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-mono font-semibold">৳{{ number_format($order->grand_total, 0) }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium {{ $oColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $oLabels[$order->order_status] ?? $order->order_status }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($orders->hasPages())
        <div class="px-4 py-3 border-t">{{ $orders->links() }}</div>
        @endif
    </div>
    @endif

</main>

</body>
</html>
