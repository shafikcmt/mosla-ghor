@extends('admin.layout')

@section('title', $customer->name . ' — কাস্টমার')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.customers.index') }}"
       class="text-sm text-gray-500 hover:text-gray-800 transition-colors">← কাস্টমার তালিকা</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">{{ $customer->name }}</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left column: info + edit --}}
    <div class="lg:col-span-1 space-y-4">

        {{-- Customer info card --}}
        <div class="bg-white rounded shadow-sm border border-gray-100 p-5 space-y-3 text-sm">
            <h2 class="font-semibold text-gray-700 text-xs uppercase tracking-wider border-b border-gray-100 pb-2">কাস্টমার তথ্য</h2>

            <div class="flex items-center justify-between">
                <span class="text-gray-500">স্ট্যাটাস</span>
                @if($customer->is_active)
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">সক্রিয়</span>
                @else
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-600">নিষ্ক্রিয়</span>
                @endif
            </div>

            <div>
                <span class="text-gray-500 text-xs">মোবাইল</span>
                <p class="font-mono font-semibold text-gray-800">{{ $customer->mobile_number }}</p>
            </div>

            @if($customer->alternative_number)
            <div>
                <span class="text-gray-500 text-xs">বিকল্প নম্বর</span>
                <p class="font-mono text-gray-700">{{ $customer->alternative_number }}</p>
            </div>
            @endif

            @if($customer->email)
            <div>
                <span class="text-gray-500 text-xs">ইমেইল</span>
                <p class="text-gray-700">{{ $customer->email }}</p>
            </div>
            @endif

            <div>
                <span class="text-gray-500 text-xs">ঠিকানা</span>
                @php
                    $parts = array_filter([
                        $customer->last_division_name,
                        $customer->last_district_name,
                        $customer->last_upazila_name,
                        $customer->last_union_name,
                    ]);
                @endphp
                @if(count($parts))
                <p class="text-gray-600 text-xs">{{ implode(' › ', $parts) }}</p>
                @endif
                @if($customer->last_full_address)
                <p class="text-gray-800 font-medium">{{ $customer->last_full_address }}</p>
                @endif
            </div>

            <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                <span class="text-gray-500 text-xs">মার্কেটিং সম্মতি</span>
                @if($customer->accepts_marketing)
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">✓ সম্মত</span>
                @else
                    <span class="text-gray-400 text-xs">সম্মতি দেননি</span>
                @endif
            </div>

            <div class="border-t border-gray-100 pt-3 grid grid-cols-2 gap-3 text-center">
                <div class="bg-gray-50 rounded p-2">
                    <p class="text-2xl font-bold text-gray-800">{{ $customer->total_orders }}</p>
                    <p class="text-gray-500 text-xs">মোট অর্ডার</p>
                </div>
                <div class="bg-gray-50 rounded p-2">
                    <p class="text-2xl font-bold text-gray-800">৳{{ number_format($customer->total_spent, 0) }}</p>
                    <p class="text-gray-500 text-xs">মোট খরচ</p>
                </div>
            </div>

            @if($customer->last_order_at)
            <p class="text-gray-400 text-xs text-center">শেষ অর্ডার: {{ $customer->last_order_at->format('d M Y') }}</p>
            @endif
        </div>

        {{-- WhatsApp offer message --}}
        @php
            $offerMsg = "আসসালামু আলাইকুম {$customer->name},\nমসলা ঘর থেকে নতুন অফার চলছে। অরিজিনাল আস্ত মসলা কম্বো অর্ডার করতে ভিজিট করুন: {$siteUrl}";
        @endphp
        <div class="bg-white rounded shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-700 text-xs uppercase tracking-wider mb-3">অফার মেসেজ</h2>
            <p id="offer-msg-text"
               class="text-sm text-gray-700 whitespace-pre-wrap bg-gray-50 border border-gray-200 rounded p-3 leading-relaxed">{{ $offerMsg }}</p>
            <button onclick="copyOfferMsg()"
                    class="mt-3 w-full flex items-center justify-center gap-2 border border-green-700 text-green-700 hover:bg-green-50 text-sm font-medium px-4 py-2 rounded transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                </svg>
                <span id="copy-btn-label">WhatsApp মেসেজ কপি করুন</span>
            </button>
        </div>

        {{-- Edit notes / status --}}
        <div class="bg-white rounded shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-gray-700 text-xs uppercase tracking-wider mb-3">নোট ও স্ট্যাটাস</h2>
            <form method="POST" action="{{ route('admin.customers.update', $customer) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">নোট</label>
                    <textarea name="notes" rows="4"
                              placeholder="কাস্টমার সম্পর্কে নোট লিখুন..."
                              class="w-full border border-gray-200 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-green-700 resize-none">{{ $customer->notes }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ $customer->is_active ? 'checked' : '' }}
                               class="rounded border-gray-300 text-green-700 focus:ring-green-700">
                        <span class="text-sm text-gray-700">কাস্টমার সক্রিয়</span>
                    </label>
                </div>
                <button type="submit"
                        class="w-full bg-gray-800 hover:bg-gray-700 text-white text-sm font-medium px-4 py-2 rounded transition-colors">
                    সংরক্ষণ করুন
                </button>
            </form>
        </div>

    </div>

    {{-- Right column: recent orders --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded shadow-sm border border-gray-100">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-700 text-sm">সাম্প্রতিক অর্ডার</h2>
                <span class="text-gray-400 text-xs">সর্বশেষ ১০টি</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600">অর্ডার নং</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-600">মোট (৳)</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600">পেমেন্ট</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600">স্ট্যাটাস</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600">তারিখ</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentOrders as $order)
                        @php
                            $pColors = ['pending' => 'bg-yellow-100 text-yellow-700', 'verified' => 'bg-green-100 text-green-700', 'failed' => 'bg-red-100 text-red-700'];
                            $pLabels = ['pending' => 'অপেক্ষায়', 'verified' => 'যাচাই', 'failed' => 'ব্যর্থ'];
                            $oColors = ['pending' => 'bg-yellow-100 text-yellow-700', 'confirmed' => 'bg-blue-100 text-blue-700', 'processing' => 'bg-indigo-100 text-indigo-700', 'shipped' => 'bg-cyan-100 text-cyan-700', 'delivered' => 'bg-green-100 text-green-700', 'cancelled' => 'bg-red-100 text-red-700'];
                            $oLabels = ['pending' => 'অপেক্ষায়', 'confirmed' => 'নিশ্চিত', 'processing' => 'প্রসেসিং', 'shipped' => 'শিপড', 'delivered' => 'ডেলিভারড', 'cancelled' => 'বাতিল'];
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $order->order_number }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($order->grand_total, 0) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-xs font-medium {{ $pColors[$order->payment_status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $pLabels[$order->payment_status] ?? $order->payment_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-xs font-medium {{ $oColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $oLabels[$order->order_status] ?? $order->order_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                                {{ $order->created_at->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}"
                                   class="text-xs text-blue-600 hover:text-blue-900">দেখুন →</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-400 text-sm">কোনো অর্ডার নেই।</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
function copyOfferMsg() {
    const text = document.getElementById('offer-msg-text').innerText;
    const btn  = document.getElementById('copy-btn-label');
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = '✓ কপি হয়েছে!';
        setTimeout(() => { btn.textContent = 'WhatsApp মেসেজ কপি করুন'; }, 2500);
    }).catch(() => {
        // Fallback for older browsers
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        btn.textContent = '✓ কপি হয়েছে!';
        setTimeout(() => { btn.textContent = 'WhatsApp মেসেজ কপি করুন'; }, 2500);
    });
}
</script>

@endsection
