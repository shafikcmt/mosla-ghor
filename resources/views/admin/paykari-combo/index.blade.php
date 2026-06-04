@extends('admin.layouts.app')

@section('title', 'Paykari Combo Enquiries')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 max-w-7xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Paykari Combo Enquiries</h1>
            <p class="text-gray-500 text-sm mt-1">All wholesale combo enquiries from customers.</p>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl mb-5 text-sm">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Products</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Location</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Vendor</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($enquiries as $enq)
                <tr class="hover:bg-amber-50 transition-colors">
                    <td class="px-5 py-3 font-mono text-gray-400 text-xs">#{{ $enq->id }}</td>
                    <td class="px-5 py-3">
                        <div class="font-semibold text-gray-800">{{ $enq->customer_name }}</div>
                        <div class="text-gray-400 text-xs">{{ $enq->customer_phone }}</div>
                    </td>
                    <td class="px-5 py-3">
                        <div class="text-gray-700">
                            @foreach($enq->items->take(2) as $item)
                                <span class="text-xs">{{ $item->product_name }} ({{ $item->quantity_kg }}kg)</span><br>
                            @endforeach
                            @if($enq->items->count() > 2)
                            <span class="text-gray-400 text-xs">+ {{ $enq->items->count() - 2 }} more</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-3 text-gray-600 text-xs">{{ $enq->delivery_location }}</td>
                    <td class="px-5 py-3 text-gray-600 text-xs">
                        {{ $enq->vendor?->business_name ?? '—' }}
                    </td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $enq->statusBadgeClass() }}">
                            {{ $enq->statusLabel() }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $enq->created_at->format('d M Y') }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('admin.paykari-combo.show', $enq) }}"
                           class="text-amber-600 hover:text-amber-800 font-semibold text-xs">
                            বিস্তারিত →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-gray-400">কোনো enquiry নেই।</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($enquiries->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">{{ $enquiries->links() }}</div>
        @endif
    </div>

</div>
@endsection
