@extends('admin.layout')
@section('title', $vendor->shop_name)

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.vendors.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← ভেন্ডর তালিকা</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">{{ $vendor->shop_name }}</h1>
    <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium
        @if($vendor->status === 'approved') bg-green-100 text-green-700
        @elseif($vendor->status === 'pending') bg-yellow-100 text-yellow-700
        @elseif($vendor->status === 'suspended') bg-red-100 text-red-700
        @else bg-gray-100 text-gray-600 @endif">
        {{ $vendor->status }}
    </span>
</div>

{{-- Action buttons --}}
<div class="flex flex-wrap gap-2 mb-6">
    @if($vendor->status === 'pending')
    <form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">
        @csrf
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            ✓ অনুমোদন করুন
        </button>
    </form>
    <form method="POST" action="{{ route('admin.vendors.reject', $vendor) }}">
        @csrf
        <button type="submit" onclick="return confirm('প্রত্যাখ্যান করবেন?')"
                class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            ✗ প্রত্যাখ্যান করুন
        </button>
    </form>
    @elseif($vendor->status === 'approved')
    <form method="POST" action="{{ route('admin.vendors.suspend', $vendor) }}">
        @csrf
        <button type="submit" onclick="return confirm('স্থগিত করবেন?')"
                class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            স্থগিত করুন
        </button>
    </form>
    @elseif(in_array($vendor->status, ['suspended', 'rejected']))
    <form method="POST" action="{{ route('admin.vendors.reactivate', $vendor) }}">
        @csrf
        <button type="submit"
                class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            পুনরায় সক্রিয় করুন
        </button>
    </form>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">

    {{-- Vendor info --}}
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4 pb-2 border-b">ভেন্ডর তথ্য</h3>
        <dl class="grid grid-cols-2 gap-3 text-sm">
            <div><dt class="text-gray-500 text-xs">মালিকের নাম</dt><dd class="font-medium">{{ $vendor->owner_name }}</dd></div>
            <div><dt class="text-gray-500 text-xs">ফোন</dt><dd class="font-mono">{{ $vendor->phone }}</dd></div>
            <div><dt class="text-gray-500 text-xs">ইমেইল</dt><dd>{{ $vendor->email }}</dd></div>
            <div><dt class="text-gray-500 text-xs">ব্যবসার ধরন</dt><dd>{{ $vendor->business_type ?: '—' }}</dd></div>
            <div class="col-span-2"><dt class="text-gray-500 text-xs">ঠিকানা</dt><dd>{{ $vendor->address ?: '—' }}</dd></div>
            <div><dt class="text-gray-500 text-xs">কমিশন</dt>
                <dd>{{ $vendor->commission_type ?? 'ডিফল্ট' }} — {{ $vendor->commission_value ?? '—' }}</dd>
            </div>
            <div><dt class="text-gray-500 text-xs">পণ্য অটো অনুমোদন</dt>
                <dd>{{ $vendor->product_auto_approve ? 'হ্যাঁ' : 'না' }}</dd>
            </div>
            <div class="col-span-2"><dt class="text-gray-500 text-xs">অ্যাডমিন নোট</dt><dd class="text-gray-600">{{ $vendor->admin_note ?: '—' }}</dd></div>
        </dl>
    </div>

    {{-- Edit commission / settings --}}
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4 pb-2 border-b">সেটিং আপডেট</h3>
        <form method="POST" action="{{ route('admin.vendors.update', $vendor) }}" class="space-y-3">
            @csrf @method('PUT')

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">কমিশন ধরন</label>
                <select name="commission_type"
                        class="w-full border rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-green-400">
                    <option value="">ডিফল্ট ব্যবহার করুন</option>
                    <option value="percentage" {{ $vendor->commission_type === 'percentage' ? 'selected' : '' }}>শতাংশ (%)</option>
                    <option value="fixed" {{ $vendor->commission_type === 'fixed' ? 'selected' : '' }}>নির্দিষ্ট (৳)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">কমিশন মান</label>
                <input type="number" name="commission_value" value="{{ $vendor->commission_value }}" step="0.01" min="0"
                       class="w-full border rounded px-2 py-1.5 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-green-400">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="product_auto_approve" value="1" id="paa"
                       {{ $vendor->product_auto_approve ? 'checked' : '' }}>
                <label for="paa" class="text-xs text-gray-700 cursor-pointer">পণ্য অটো অনুমোদন</label>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">অ্যাডমিন নোট</label>
                <textarea name="admin_note" rows="2"
                          class="w-full border rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-green-400">{{ $vendor->admin_note }}</textarea>
            </div>
            <button type="submit"
                    class="w-full bg-[#1a6b3a] hover:bg-[#14532d] text-white text-sm font-medium py-2 rounded-lg transition-colors">
                আপডেট করুন
            </button>
        </form>
    </div>
</div>

{{-- Products --}}
@if($products->isNotEmpty())
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden mb-5">
    <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
        <h3 class="font-semibold text-gray-700 text-sm">পণ্যসমূহ (সাম্প্রতিক {{ $products->count() }}টি)</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">পণ্য</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">অনুমোদন</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">অবস্থা</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">কার্যক্রম</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($products as $product)
            <tr>
                <td class="px-4 py-2 font-medium">{{ $product->name_bn }}</td>
                <td class="px-4 py-2">
                    <span class="text-xs px-2 py-0.5 rounded-full
                        @if($product->approval_status === 'approved') bg-green-100 text-green-700
                        @elseif($product->approval_status === 'pending') bg-yellow-100 text-yellow-700
                        @else bg-red-100 text-red-700 @endif">
                        {{ $product->approval_status ?? '—' }}
                    </span>
                </td>
                <td class="px-4 py-2">
                    <span class="text-xs {{ $product->is_active ? 'text-green-700' : 'text-gray-400' }}">
                        {{ $product->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                    </span>
                </td>
                <td class="px-4 py-2 text-right">
                    <a href="{{ route('admin.products.edit', $product) }}"
                       class="text-xs text-[#1a6b3a] border border-[#c8e6c9] rounded px-2 py-0.5">সম্পাদনা</a>

                    {{-- Approve product --}}
                    @if($product->approval_status === 'pending')
                    <form method="POST" action="{{ route('admin.vendor-products.approve', $product) }}" class="inline ml-1">
                        @csrf
                        <button type="submit" class="text-xs text-green-700 border border-green-200 rounded px-2 py-0.5 hover:bg-green-50">অনুমোদন</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Recent orders --}}
@if($recentOrders->isNotEmpty())
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden mb-5">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">সাম্প্রতিক অর্ডার</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">অর্ডার</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">সাবটোটাল</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">কমিশন</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">প্রাপ্য</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">অবস্থা</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($recentOrders as $vo)
            <tr>
                <td class="px-4 py-2 font-medium">{{ $vo->order?->order_number }}</td>
                <td class="px-4 py-2 font-mono">৳{{ number_format($vo->subtotal, 0) }}</td>
                <td class="px-4 py-2 font-mono text-red-600">৳{{ number_format($vo->commission_amount, 0) }}</td>
                <td class="px-4 py-2 font-mono text-green-700">৳{{ number_format($vo->payable_amount, 0) }}</td>
                <td class="px-4 py-2 text-xs">{{ $vo->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Recent payouts --}}
@if($recentPayouts->isNotEmpty())
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">সাম্প্রতিক পেআউট রিকুয়েস্ট</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">তারিখ</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">পরিমাণ</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">পদ্ধতি</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">অবস্থা</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($recentPayouts as $payout)
            <tr>
                <td class="px-4 py-2 text-gray-500 text-xs">{{ $payout->created_at->format('d M Y') }}</td>
                <td class="px-4 py-2 font-mono font-semibold">৳{{ number_format($payout->amount, 0) }}</td>
                <td class="px-4 py-2 text-gray-600">{{ $payout->payment_method }}</td>
                <td class="px-4 py-2">
                    <span class="text-xs px-2 py-0.5 rounded-full
                        @if($payout->status === 'paid') bg-green-100 text-green-700
                        @elseif($payout->status === 'pending') bg-yellow-100 text-yellow-700
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ $payout->status }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
