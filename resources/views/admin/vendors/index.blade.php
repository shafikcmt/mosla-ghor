@extends('admin.layout')
@section('title', 'ভেন্ডর তালিকা')

@section('content')

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-xl font-bold text-gray-800">মার্চেন্ট / ভেন্ডর</h2>
        <p class="text-sm text-gray-500 mt-0.5">মোট {{ $vendors->total() }} ভেন্ডর</p>
    </div>
    <a href="{{ route('admin.vendors.settings') }}"
       class="inline-flex items-center gap-2 text-sm text-gray-600 border border-gray-300 hover:bg-gray-50 px-3 py-1.5 rounded-lg transition-colors">
        মাল্টিভেন্ডর সেটিং
    </a>
</div>

{{-- Filter --}}
<form method="GET" class="mb-5 flex flex-wrap gap-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="নাম / ফোন / ইমেইল খুঁজুন..."
           class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 w-56">
    <select name="status" onchange="this.form.submit()"
            class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
        <option value="">— সব অবস্থা —</option>
        @foreach(['pending','approved','suspended','rejected'] as $s)
        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
            @if($s === 'pending') পেন্ডিং
            @elseif($s === 'approved') অনুমোদিত
            @elseif($s === 'suspended') স্থগিত
            @else প্রত্যাখ্যাত @endif
        </option>
        @endforeach
    </select>
    <button type="submit" class="bg-[#1a6b3a] text-white px-4 py-2 rounded-lg text-sm">খুঁজুন</button>
</form>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    @if($vendors->isEmpty())
        <div class="px-6 py-16 text-center text-gray-400 text-sm">কোনো ভেন্ডর পাওয়া যায়নি।</div>
    @else
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">দোকান</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase hidden md:table-cell">যোগাযোগ</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">অবস্থা</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase hidden md:table-cell">তারিখ</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 text-xs uppercase">কার্যক্রম</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($vendors as $vendor)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        @if($vendor->logo)
                        <img src="{{ asset($vendor->logo) }}" alt="" class="w-9 h-9 rounded-full object-cover border flex-shrink-0">
                        @else
                        <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-indigo-600 font-bold text-sm">{{ strtoupper(substr($vendor->shop_name, 0, 1)) }}</span>
                        </div>
                        @endif
                        <div>
                            <p class="font-medium text-gray-800">{{ $vendor->shop_name }}</p>
                            <p class="text-xs text-gray-400">{{ $vendor->owner_name }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 hidden md:table-cell">
                    <p class="text-gray-700">{{ $vendor->phone }}</p>
                    <p class="text-xs text-gray-400">{{ $vendor->email }}</p>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium
                        @if($vendor->status === 'approved') bg-green-100 text-green-700
                        @elseif($vendor->status === 'pending') bg-yellow-100 text-yellow-700
                        @elseif($vendor->status === 'suspended') bg-red-100 text-red-700
                        @else bg-gray-100 text-gray-600 @endif">
                        @if($vendor->status === 'approved') অনুমোদিত
                        @elseif($vendor->status === 'pending') পেন্ডিং
                        @elseif($vendor->status === 'suspended') স্থগিত
                        @else প্রত্যাখ্যাত @endif
                    </span>
                </td>
                <td class="px-4 py-3 hidden md:table-cell text-gray-500 text-xs">{{ $vendor->created_at->format('d M Y') }}</td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.vendors.show', $vendor) }}"
                       class="text-xs text-[#1a6b3a] hover:text-[#14532d] border border-[#c8e6c9] rounded px-2.5 py-1">বিস্তারিত</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($vendors->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">{{ $vendors->links() }}</div>
    @endif
    @endif
</div>
@endsection
