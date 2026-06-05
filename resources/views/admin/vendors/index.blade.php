@extends('admin.layout')
@section('title', 'ভেন্ডর তালিকা')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-xl font-bold text-gray-800">মার্চেন্ট / ভেন্ডর</h2>
        <p class="text-sm text-gray-500 mt-0.5">মোট {{ $vendors->total() }} ভেন্ডর</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.vendors.settings') }}"
           class="text-sm text-gray-600 border border-gray-300 hover:bg-gray-50 px-3 py-2 rounded-lg transition-colors">সেটিং</a>
        <a href="{{ route('admin.vendors.create') }}"
           class="bg-[#14532d] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#0d3520] transition-colors">+ নতুন ভেন্ডর</a>
    </div>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <x-ui.stat-card label="মোট ভেন্ডর" :value="$summary['total']" color="gray" />
    <x-ui.stat-card label="অনুমোদিত" :value="$summary['approved']" color="green" />
    <x-ui.stat-card label="পেন্ডিং" :value="$summary['pending']" color="amber" />
    <x-ui.stat-card label="স্থগিত" :value="$summary['suspended']" color="red" />
</div>

{{-- Filters --}}
<form method="GET" class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 mb-4 flex flex-wrap gap-2 items-center">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="নাম / ফোন / ইমেইল খুঁজুন..."
           class="flex-1 min-w-[180px] border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
    <select name="status" onchange="this.form.submit()"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
        <option value="">— সব অবস্থা —</option>
        @foreach(['pending' => 'পেন্ডিং', 'approved' => 'অনুমোদিত', 'suspended' => 'স্থগিত', 'rejected' => 'প্রত্যাখ্যাত'] as $val => $label)
        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <select name="business_type" onchange="this.form.submit()"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
        <option value="">— সব ধরন —</option>
        @foreach($businessTypes as $bt)
        <option value="{{ $bt }}" {{ request('business_type') === $bt ? 'selected' : '' }}>{{ $bt }}</option>
        @endforeach
    </select>
    <button type="submit" class="bg-[#1a6b3a] text-white px-4 py-2 rounded-lg text-sm">খুঁজুন</button>
</form>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    @if($vendors->isEmpty())
        <div class="px-6 py-16 text-center text-gray-400 text-sm">কোনো ভেন্ডর পাওয়া যায়নি।</div>
    @else
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">দোকান</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase hidden md:table-cell">যোগাযোগ</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600 text-xs uppercase hidden lg:table-cell">পণ্য</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600 text-xs uppercase hidden lg:table-cell">অর্ডার</th>
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
                            <p class="text-xs text-gray-400">{{ $vendor->owner_name }}{{ $vendor->business_type ? ' · ' . $vendor->business_type : '' }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 hidden md:table-cell">
                    <p class="text-gray-700">{{ $vendor->phone }}</p>
                    <p class="text-xs text-gray-400">{{ $vendor->email }}</p>
                </td>
                <td class="px-4 py-3 text-center hidden lg:table-cell text-gray-600">{{ $vendor->products_count }}</td>
                <td class="px-4 py-3 text-center hidden lg:table-cell text-gray-600">{{ $vendor->vendor_orders_count }}</td>
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
                    <div class="flex items-center justify-end gap-1.5">
                        <a href="{{ route('admin.vendors.show', $vendor) }}"
                           class="text-xs text-[#1a6b3a] hover:text-[#14532d] border border-[#c8e6c9] rounded px-2.5 py-1">বিস্তারিত</a>
                        <a href="{{ route('admin.vendors.edit', $vendor) }}"
                           class="text-xs text-blue-600 hover:text-blue-800 border border-blue-100 rounded px-2.5 py-1">সম্পাদনা</a>
                    </div>
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
