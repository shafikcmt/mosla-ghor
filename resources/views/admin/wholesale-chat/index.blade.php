@extends('admin.layout')
@section('title', 'Chat Monitor')

@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-5">Chat Monitor</h2>

@if($enquiries->isEmpty())
<div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">
    <p class="text-4xl mb-3">💬</p>
    <p class="text-sm">কোনো active chat নেই।</p>
</div>
@else
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Enquiry</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">পণ্য</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Customer</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Vendor</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">বার্তা</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">কার্যক্রম</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($enquiries as $enquiry)
            @php $unread = $enquiry->chatMessages->where('is_read_by_admin', false)->count(); @endphp
            <tr class="hover:bg-gray-50 {{ $unread > 0 ? 'bg-yellow-50' : '' }}">
                <td class="px-4 py-3 text-gray-400 text-xs">#{{ $enquiry->id }}</td>
                <td class="px-4 py-3 font-medium text-gray-800">{{ $enquiry->productLabel() }}</td>
                <td class="px-4 py-3 text-gray-600 text-xs hidden sm:table-cell">{{ $enquiry->customer_name }}</td>
                <td class="px-4 py-3 text-gray-600 text-xs hidden md:table-cell">{{ $enquiry->vendor?->shop_name ?? $enquiry->vendor?->name }}</td>
                <td class="px-4 py-3">
                    @if($unread > 0)
                    <span class="text-xs bg-red-500 text-white px-2 py-0.5 rounded-full font-bold">{{ $unread }} নতুন</span>
                    @else
                    <span class="text-xs text-gray-400">{{ $enquiry->chatMessages->count() }} টি</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.wholesale.chat.show', $enquiry->id) }}"
                       class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition-colors font-medium">
                        দেখুন
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($enquiries->hasPages())
    <div class="px-4 py-3 border-t">{{ $enquiries->links() }}</div>
    @endif
</div>
@endif
@endsection
