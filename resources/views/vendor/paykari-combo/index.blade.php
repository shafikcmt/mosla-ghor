@extends('vendor.layouts.app')

@section('title', 'Paykari Combo Enquiries')

@section('content')
<div class="px-4 sm:px-6 py-8 max-w-5xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Paykari Combo Enquiries</h1>
        <p class="text-gray-500 text-sm mt-1">Admin assigned পাইকারি কম্বো enquiry সমূহ।</p>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl mb-5 text-sm">
        {{ session('success') }}
    </div>
    @endif

    @if($enquiries->isEmpty())
    <div class="bg-white rounded-2xl border border-amber-100 p-12 text-center">
        <div class="text-5xl mb-4">📦</div>
        <h3 class="text-lg font-bold text-gray-700 mb-2">কোনো enquiry নেই</h3>
        <p class="text-gray-500 text-sm">Admin কোনো paykari combo enquiry assign করেননি।</p>
    </div>
    @else
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Products</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Location</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Business</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($enquiries as $enq)
                <tr class="hover:bg-amber-50 transition-colors">
                    <td class="px-5 py-3 font-mono text-gray-400 text-xs">#{{ $enq->id }}</td>
                    <td class="px-5 py-3">
                        @foreach($enq->items->take(2) as $item)
                        <div class="text-xs text-gray-700">{{ $item->product_name }} — {{ $item->quantity_kg }}kg</div>
                        @endforeach
                        @if($enq->items->count() > 2)
                        <div class="text-xs text-gray-400">+ {{ $enq->items->count() - 2 }} more</div>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-600 text-xs">{{ $enq->delivery_location }}</td>
                    <td class="px-5 py-3 text-gray-600 text-xs">{{ $enq->businessTypeLabel() }}</td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $enq->statusBadgeClass() }}">
                            {{ $enq->statusLabel() }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $enq->created_at->format('d M Y') }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('vendor.paykari-combo.show', $enq) }}"
                           class="text-amber-600 hover:text-amber-800 font-semibold text-xs">বিস্তারিত →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($enquiries->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">{{ $enquiries->links() }}</div>
        @endif
    </div>
    @endif

</div>
@endsection
