@extends('admin.layout')

@section('title', 'অর্ডার তালিকা')

@section('content')

@php
    $tabs = [
        'all'       => 'সব',
        'pending'   => 'অপেক্ষায়',
        'paid'      => 'পেইড',
        'delivered' => 'ডেলিভারড',
        'cancelled' => 'বাতিল',
    ];
    // ids of orders that must not be permanently deleted (shown as a bulk warning)
    $protectedIds = $orders->getCollection()->filter->isDeleteProtected()->pluck('id')->values();
@endphp

<div x-data="{
        selected: [],
        protectedIds: @js($protectedIds),
        showSingle: false,
        singleAction: '',
        singleNumber: '',
        showBulk: false,
        allChecked: false,
        get hasProtected() { return this.selected.some(id => this.protectedIds.includes(id)); },
        openSingle(action, number) { this.singleAction = action; this.singleNumber = number; this.showSingle = true; },
        toggleAll(ids, checked) { this.selected = checked ? [...ids] : []; }
     }">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <h1 class="text-xl font-bold text-gray-800">অর্ডার তালিকা</h1>
        <span class="text-sm text-gray-500">মোট: {{ $orders->total() }} টি অর্ডার</span>
    </div>

    {{-- Filter tabs + search --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex flex-wrap gap-1.5">
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.orders.index', array_filter(['status' => $key === 'all' ? null : $key, 'q' => $search ?: null])) }}"
                   class="px-3 py-1.5 rounded text-xs font-medium transition-colors
                          {{ $status === $key ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                    {{ $label }}
                </a>
            @endforeach
            <a href="{{ route('admin.orders.trash') }}"
               class="px-3 py-1.5 rounded text-xs font-medium bg-white text-red-600 border border-red-200 hover:bg-red-50 transition-colors">
                🗑 মুছে ফেলা অর্ডার
                @if($trashCount > 0)
                    <span class="ml-1 inline-block bg-red-100 text-red-700 rounded px-1.5">{{ $trashCount }}</span>
                @endif
            </a>
        </div>

        <form method="GET" action="{{ route('admin.orders.index') }}" class="flex gap-1.5">
            @if($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
            <input type="text" name="q" value="{{ $search }}"
                   placeholder="অর্ডার নং / নাম / মোবাইল"
                   class="text-sm border border-gray-300 rounded px-3 py-1.5 w-56 focus:outline-none focus:ring-1 focus:ring-gray-400">
            <button type="submit" class="bg-gray-800 text-white text-xs px-3 py-1.5 rounded hover:bg-gray-700">খুঁজুন</button>
            @if($search)
                <a href="{{ route('admin.orders.index', array_filter(['status' => $status === 'all' ? null : $status])) }}"
                   class="text-xs px-3 py-1.5 rounded border border-gray-200 text-gray-500 hover:bg-gray-50">রিসেট</a>
            @endif
        </form>
    </div>

    {{-- Bulk action bar --}}
    <div x-show="selected.length > 0" x-cloak
         class="flex flex-wrap items-center gap-3 mb-3 bg-red-50 border border-red-200 rounded px-4 py-2.5">
        <span class="text-sm text-red-700 font-medium"><span x-text="selected.length"></span> টি নির্বাচিত</span>
        <button type="button" @click="showBulk = true"
                class="bg-red-600 text-white text-xs px-3 py-1.5 rounded hover:bg-red-700">
            নির্বাচিত অর্ডার মুছুন
        </button>
        <button type="button" @click="selected = []" class="text-xs text-gray-500 hover:text-gray-700">বাতিল</button>
    </div>

    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left">
                        <input type="checkbox" x-model="allChecked"
                               @change="toggleAll(@js($orders->getCollection()->pluck('id')->values()), $event.target.checked)"
                               class="rounded border-gray-300">
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">অর্ডার নং</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">নাম</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">মোবাইল</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">ধরন</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">মোট (৳)</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">পেমেন্ট</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">পেমেন্ট স্ট্যাটাস</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">অর্ডার স্ট্যাটাস</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">তারিখ</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <input type="checkbox" value="{{ $order->id }}" x-model.number="selected"
                               class="rounded border-gray-300">
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $order->order_number }}</td>
                    <td class="px-4 py-3 text-gray-800">{{ $order->customer_name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $order->mobile_number }}</td>
                    <td class="px-4 py-3">
                        @php
                            $typeLabels = ['custom' => 'কাস্টম', 'retail' => 'রিটেইল', 'wholesale' => 'হোলসেল'];
                            $typeColors = ['custom' => 'bg-purple-100 text-purple-700', 'retail' => 'bg-blue-100 text-blue-700', 'wholesale' => 'bg-orange-100 text-orange-700'];
                        @endphp
                        <span class="px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$order->order_type] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $typeLabels[$order->order_type] ?? $order->order_type }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($order->grand_total, 0) }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        @php
                            $methodLabels = ['cash_on_delivery' => 'ক্যাশ অন ডেলিভারি', 'bkash' => 'বিকাশ', 'nagad' => 'নগদ', 'rocket' => 'রকেট'];
                        @endphp
                        {{ $methodLabels[$order->payment_method] ?? $order->payment_method }}
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $pColors = ['pending' => 'bg-yellow-100 text-yellow-700', 'verified' => 'bg-green-100 text-green-700', 'failed' => 'bg-red-100 text-red-700'];
                            $pLabels = ['pending' => 'অপেক্ষায়', 'verified' => 'যাচাই হয়েছে', 'failed' => 'ব্যর্থ'];
                        @endphp
                        <span class="px-2 py-0.5 rounded text-xs font-medium {{ $pColors[$order->payment_status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $pLabels[$order->payment_status] ?? $order->payment_status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
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
                        <span class="px-2 py-0.5 rounded text-xs font-medium {{ $oColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $oLabels[$order->order_status] ?? $order->order_status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                        {{ $order->created_at->format('d M Y, h:i A') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1.5">
                            <a href="{{ route('admin.orders.show', $order) }}"
                               class="inline-block bg-gray-800 text-white text-xs px-3 py-1 rounded hover:bg-gray-700 transition-colors">
                                দেখুন
                            </a>
                            <button type="button"
                                    @click="openSingle('{{ route('admin.orders.destroy', $order) }}', '{{ $order->order_number }}')"
                                    class="inline-block bg-red-600 text-white text-xs px-3 py-1 rounded hover:bg-red-700 transition-colors">
                                মুছুন
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="px-4 py-8 text-center text-gray-400">কোনো অর্ডার নেই।</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
    <div class="mt-5">
        {{ $orders->links() }}
    </div>
    @endif

    {{-- ── Single delete modal ─────────────────────────────────────────── --}}
    <div x-show="showSingle" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
         @keydown.escape.window="showSingle = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md" @click.outside="showSingle = false">
            <form :action="singleAction" method="POST">
                @csrf
                @method('DELETE')
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-800">অর্ডার মুছবেন?</h3>
                </div>
                <div class="px-5 py-4 space-y-3">
                    <p class="text-sm text-gray-600">
                        এই অর্ডারটি (<span class="font-mono font-medium" x-text="singleNumber"></span>)
                        Trash এ যাবে, পরে Restore করা যাবে।
                    </p>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">মুছে ফেলার কারণ (ঐচ্ছিক)</label>
                        <textarea name="delete_reason" rows="2"
                                  class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-gray-400"
                                  placeholder="যেমন: ডামি / টেস্ট অর্ডার"></textarea>
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" @click="showSingle = false"
                            class="px-4 py-1.5 text-sm rounded border border-gray-200 text-gray-600 hover:bg-gray-50">বাতিল</button>
                    <button type="submit"
                            class="px-4 py-1.5 text-sm rounded bg-red-600 text-white hover:bg-red-700">Trash এ পাঠান</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Bulk delete modal ───────────────────────────────────────────── --}}
    <div x-show="showBulk" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
         @keydown.escape.window="showBulk = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md" @click.outside="showBulk = false">
            <form action="{{ route('admin.orders.bulkDestroy') }}" method="POST">
                @csrf
                @method('DELETE')
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-800">অর্ডার মুছবেন?</h3>
                </div>
                <div class="px-5 py-4 space-y-3">
                    <p class="text-sm text-gray-600">
                        নির্বাচিত <span class="font-medium" x-text="selected.length"></span> টি অর্ডার
                        Trash এ যাবে, পরে Restore করা যাবে।
                    </p>
                    <div x-show="hasProtected"
                         class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-3 py-2">
                        ⚠ নির্বাচিত অর্ডারের মধ্যে পেইড / ডেলিভারড অর্ডার রয়েছে। নিশ্চিত হয়ে মুছুন।
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">মুছে ফেলার কারণ (ঐচ্ছিক)</label>
                        <textarea name="delete_reason" rows="2"
                                  class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-gray-400"
                                  placeholder="যেমন: ডামি / টেস্ট অর্ডার"></textarea>
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" @click="showBulk = false"
                            class="px-4 py-1.5 text-sm rounded border border-gray-200 text-gray-600 hover:bg-gray-50">বাতিল</button>
                    <button type="submit"
                            class="px-4 py-1.5 text-sm rounded bg-red-600 text-white hover:bg-red-700">Trash এ পাঠান</button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
