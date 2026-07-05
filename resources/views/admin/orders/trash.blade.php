@extends('admin.layout')

@section('title', 'মুছে ফেলা অর্ডার')

@section('content')

<div x-data="{
        showForce: false,
        forceAction: '',
        forceNumber: '',
        openForce(action, number) { this.forceAction = action; this.forceNumber = number; this.showForce = true; }
     }">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-gray-800">মুছে ফেলা অর্ডার</h1>
            <p class="text-xs text-gray-500 mt-0.5">Trash এ থাকা অর্ডার Restore করুন অথবা স্থায়ীভাবে মুছুন।</p>
        </div>
        <a href="{{ route('admin.orders.index') }}"
           class="text-sm px-3 py-1.5 rounded border border-gray-200 text-gray-600 hover:bg-gray-50">← অর্ডার তালিকায় ফিরুন</a>
    </div>

    @unless(auth()->user()->isSuperAdmin())
        <div class="mb-4 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-4 py-2.5">
            ⚠ স্থায়ীভাবে মুছে ফেলার অনুমতি শুধুমাত্র সুপার অ্যাডমিনের রয়েছে। আপনি অর্ডার শুধু Restore করতে পারবেন।
        </div>
    @endunless

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <span class="text-sm text-gray-500">মোট: {{ $orders->total() }} টি</span>
        <form method="GET" action="{{ route('admin.orders.trash') }}" class="flex gap-1.5">
            <input type="text" name="q" value="{{ $search }}"
                   placeholder="অর্ডার নং / নাম / মোবাইল"
                   class="text-sm border border-gray-300 rounded px-3 py-1.5 w-56 focus:outline-none focus:ring-1 focus:ring-gray-400">
            <button type="submit" class="bg-gray-800 text-white text-xs px-3 py-1.5 rounded hover:bg-gray-700">খুঁজুন</button>
            @if($search)
                <a href="{{ route('admin.orders.trash') }}"
                   class="text-xs px-3 py-1.5 rounded border border-gray-200 text-gray-500 hover:bg-gray-50">রিসেট</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">অর্ডার নং</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">নাম</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">মোবাইল</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">মোট (৳)</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">স্ট্যাটাস</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">কারণ</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">মুছেছেন</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $order->order_number }}</td>
                    <td class="px-4 py-3 text-gray-800">{{ $order->customer_name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $order->mobile_number }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($order->grand_total, 0) }}</td>
                    <td class="px-4 py-3">
                        @if($order->isDeleteProtected())
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">পেইড/ডেলিভারড</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">সাধারণ</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-xs max-w-[180px] truncate" title="{{ $order->delete_reason }}">
                        {{ $order->delete_reason ?: '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                        {{ $order->deletedBy?->name ?? '—' }}<br>
                        <span class="text-gray-400">{{ $order->deleted_at?->format('d M Y, h:i A') }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1.5">
                            <form action="{{ route('admin.orders.restore', $order->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="inline-block bg-green-600 text-white text-xs px-3 py-1 rounded hover:bg-green-700 transition-colors">
                                    পুনরুদ্ধার
                                </button>
                            </form>
                            @if(! auth()->user()->isSuperAdmin())
                                <span class="inline-block bg-gray-100 text-gray-400 text-xs px-3 py-1 rounded cursor-not-allowed"
                                      title="স্থায়ীভাবে মুছে ফেলার অনুমতি শুধুমাত্র সুপার অ্যাডমিনের রয়েছে।">
                                    স্থায়ীভাবে মুছুন
                                </span>
                            @elseif($order->isDeleteProtected())
                                <span class="inline-block bg-gray-100 text-gray-400 text-xs px-3 py-1 rounded cursor-not-allowed"
                                      title="পেইড / ডেলিভারড অর্ডার স্থায়ীভাবে মুছে ফেলা যাবে না।">
                                    স্থায়ীভাবে মুছুন
                                </span>
                            @else
                                <button type="button"
                                        @click="openForce('{{ route('admin.orders.forceDelete', $order->id) }}', '{{ $order->order_number }}')"
                                        class="inline-block bg-red-600 text-white text-xs px-3 py-1 rounded hover:bg-red-700 transition-colors">
                                    স্থায়ীভাবে মুছুন
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">Trash খালি।</td>
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

    {{-- ── Permanent delete modal ──────────────────────────────────────── --}}
    <div x-show="showForce" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
         @keydown.escape.window="showForce = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md" @click.outside="showForce = false">
            <form :action="forceAction" method="POST">
                @csrf
                @method('DELETE')
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-base font-bold text-red-700">স্থায়ীভাবে মুছবেন?</h3>
                </div>
                <div class="px-5 py-4">
                    <p class="text-sm text-gray-600">
                        এই অর্ডার (<span class="font-mono font-medium" x-text="forceNumber"></span>)
                        permanently delete হবে, আর restore করা যাবে না।
                    </p>
                </div>
                <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" @click="showForce = false"
                            class="px-4 py-1.5 text-sm rounded border border-gray-200 text-gray-600 hover:bg-gray-50">বাতিল</button>
                    <button type="submit"
                            class="px-4 py-1.5 text-sm rounded bg-red-600 text-white hover:bg-red-700">স্থায়ীভাবে মুছুন</button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
