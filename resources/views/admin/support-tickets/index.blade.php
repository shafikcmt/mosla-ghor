@extends('admin.layout')
@section('title', 'সাপোর্ট টিকেট')

@section('content')
<h1 class="text-xl font-bold text-gray-800 mb-5">সাপোর্ট টিকেট</h1>

<div class="flex flex-wrap gap-2 mb-4">
    @foreach([''=>'সব', 'open'=>'খোলা', 'replied'=>'উত্তর দেওয়া', 'closed'=>'বন্ধ'] as $val => $lbl)
    <a href="{{ route('admin.support-tickets.index', $val ? ['status'=>$val] : []) }}"
       class="text-xs px-3 py-1.5 rounded-full border transition-colors {{ $status===$val||($val===''&&!$status) ? 'bg-[#14532d] text-white border-[#14532d]' : 'border-gray-300 text-gray-600 hover:border-[#14532d]' }}">
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
                <th class="px-4 py-3 text-left font-semibold text-gray-600">বিষয়</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">অর্ডার</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">স্ট্যাটাস</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">তারিখ</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($tickets as $t)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-500">{{ $t->id }}</td>
                <td class="px-4 py-3">{{ $t->user?->name }}<br><span class="text-xs text-gray-400">{{ $t->user?->phone }}</span></td>
                <td class="px-4 py-3 max-w-xs truncate">{{ $t->subject }}</td>
                <td class="px-4 py-3 text-xs font-mono">{{ $t->order?->order_number ?? '—' }}</td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $t->statusColor() }}">{{ $t->statusLabel() }}</span>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $t->created_at->format('d M Y') }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.support-tickets.show', $t->id) }}" class="text-xs text-[#14532d] hover:underline">দেখুন</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">কোনো টিকেট নেই।</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($tickets->hasPages())<div class="mt-4">{{ $tickets->links() }}</div>@endif
@endsection
