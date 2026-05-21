@extends('admin.layout')
@section('title', 'রিটার্ন রিকোয়েস্ট')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">রিটার্ন রিকোয়েস্ট</h1>
</div>

{{-- Filter --}}
<div class="flex flex-wrap gap-2 mb-4">
    @foreach([''=>'সব', 'pending'=>'অপেক্ষায়', 'approved'=>'অনুমোদিত', 'rejected'=>'প্রত্যাখ্যাত', 'completed'=>'সম্পন্ন'] as $val => $lbl)
    <a href="{{ route('admin.return-requests.index', $val ? ['status'=>$val] : []) }}"
       class="text-xs px-3 py-1.5 rounded-full border transition-colors {{ $status === $val || ($val===''&&!$status) ? 'bg-[#14532d] text-white border-[#14532d]' : 'border-gray-300 text-gray-600 hover:border-[#14532d]' }}">
        {{ $lbl }}
    </a>
    @endforeach
</div>

<div class="bg-white rounded shadow overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">কাস্টমার</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">অর্ডার</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">কারণ</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">স্ট্যাটাস</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">তারিখ</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($returns as $r)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-500">{{ $r->id }}</td>
                <td class="px-4 py-3">{{ $r->user?->name }}<br><span class="text-xs text-gray-400">{{ $r->user?->phone }}</span></td>
                <td class="px-4 py-3 text-xs font-mono">{{ $r->order?->order_number }}</td>
                <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ $r->reason }}</td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $r->statusColor() }}">{{ $r->statusLabel() }}</span>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $r->created_at->format('d M Y') }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.return-requests.show', $r->id) }}" class="text-xs text-[#14532d] hover:underline">দেখুন</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">কোনো রিটার্ন রিকোয়েস্ট নেই।</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($returns->hasPages())<div class="mt-4">{{ $returns->links() }}</div>@endif
@endsection
