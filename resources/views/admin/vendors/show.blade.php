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

{{-- One-time credential banner (after create / reset password) --}}
@if(session('generated_password'))
<div class="mb-5 bg-emerald-50 border border-emerald-200 rounded-xl p-4" x-data="{ shown: true }">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-emerald-800">🔑 লগইন ক্রেডেনশিয়াল (একবারই দেখানো হবে)</p>
            <p class="text-xs text-emerald-600 mt-0.5">এখনই কপি করে ভেন্ডরকে দিন — পরে আর দেখা যাবে না।</p>
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                <div class="bg-white rounded-lg border border-emerald-100 px-3 py-2">
                    <span class="text-xs text-gray-400 block">ইমেইল</span>
                    <span class="font-mono" id="cred-email">{{ session('generated_email') }}</span>
                </div>
                <div class="bg-white rounded-lg border border-emerald-100 px-3 py-2 flex items-center justify-between gap-2">
                    <div>
                        <span class="text-xs text-gray-400 block">পাসওয়ার্ড</span>
                        <span class="font-mono" id="cred-pass">{{ session('generated_password') }}</span>
                    </div>
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('cred-pass').innerText); this.innerText='✓ কপি হয়েছে'"
                            class="text-xs bg-emerald-600 text-white px-2.5 py-1 rounded hover:bg-emerald-700 whitespace-nowrap">কপি</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Action buttons --}}
<div class="flex flex-wrap gap-2 mb-6">
    @if($vendor->status === 'pending')
    <form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">
        @csrf
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">✓ অনুমোদন করুন</button>
    </form>
    <form method="POST" action="{{ route('admin.vendors.reject', $vendor) }}">
        @csrf
        <button type="submit" onclick="return confirm('প্রত্যাখ্যান করবেন?')"
                class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">✗ প্রত্যাখ্যান করুন</button>
    </form>
    @elseif($vendor->status === 'approved')
    <form method="POST" action="{{ route('admin.vendors.suspend', $vendor) }}">
        @csrf
        <button type="submit" onclick="return confirm('স্থগিত করবেন?')"
                class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">স্থগিত করুন</button>
    </form>
    @elseif(in_array($vendor->status, ['suspended', 'rejected']))
    <form method="POST" action="{{ route('admin.vendors.reactivate', $vendor) }}">
        @csrf
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">পুনরায় সক্রিয় করুন</button>
    </form>
    @endif

    <a href="{{ route('admin.vendors.edit', $vendor) }}"
       class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">✎ সম্পাদনা</a>

    <form method="POST" action="{{ route('admin.vendors.reset-password', $vendor) }}"
          onsubmit="return confirm('নতুন পাসওয়ার্ড তৈরি করবেন? পুরোনো পাসওয়ার্ড আর কাজ করবে না।')">
        @csrf
        <button type="submit" class="bg-gray-700 hover:bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">🔑 পাসওয়ার্ড রিসেট</button>
    </form>

    <a href="{{ route('admin.vendor-pickup-points.index', ['vendor_id' => $vendor->id]) }}"
       class="border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium px-4 py-2 rounded-lg transition-colors">📍 পিকআপ পয়েন্ট</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">

    {{-- Vendor info --}}
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4 pb-2 border-b">ভেন্ডর তথ্য</h3>
        <dl class="grid grid-cols-2 gap-3 text-sm">
            <div><dt class="text-gray-500 text-xs">মালিকের নাম</dt><dd class="font-medium">{{ $vendor->owner_name }}</dd></div>
            <div><dt class="text-gray-500 text-xs">ফোন</dt><dd class="font-mono">{{ $vendor->phone }}</dd></div>
            <div><dt class="text-gray-500 text-xs">ইমেইল (login)</dt><dd>{{ $vendor->email }}</dd></div>
            <div><dt class="text-gray-500 text-xs">ব্যবসার ধরন</dt><dd>{{ $vendor->business_type ?: '—' }}</dd></div>
            <div class="col-span-2"><dt class="text-gray-500 text-xs">ঠিকানা</dt><dd>{{ implode(', ', array_filter([$vendor->address, $vendor->city, $vendor->district])) ?: '—' }}</dd></div>
            <div><dt class="text-gray-500 text-xs">ট্রেড লাইসেন্স</dt><dd>{{ $vendor->trade_license ?: '—' }}</dd></div>
            <div><dt class="text-gray-500 text-xs">NID</dt><dd>{{ $vendor->nid ?: '—' }}</dd></div>
            <div><dt class="text-gray-500 text-xs">কমিশন</dt><dd>{{ $vendor->commission_type ?? 'ডিফল্ট' }} — {{ $vendor->commission_value ?? '—' }}</dd></div>
            <div><dt class="text-gray-500 text-xs">পণ্য অটো অনুমোদন</dt><dd>{{ $vendor->product_auto_approve ? 'হ্যাঁ' : 'না' }}</dd></div>
            <div class="col-span-2"><dt class="text-gray-500 text-xs">অ্যাডমিন নোট</dt><dd class="text-gray-600">{{ $vendor->admin_note ?: '—' }}</dd></div>
        </dl>
    </div>

    {{-- Pickup points --}}
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4 pb-2 border-b">
            <h3 class="font-semibold text-gray-700 text-sm">পিকআপ পয়েন্ট</h3>
            <a href="{{ route('admin.vendor-pickup-points.create', ['vendor_id' => $vendor->id]) }}" class="text-xs text-[#1a6b3a] hover:underline">+ যোগ</a>
        </div>
        @forelse($vendor->pickupPoints as $pp)
        <div class="text-sm py-2 border-b border-gray-50 last:border-0">
            <div class="flex items-center justify-between">
                <span class="font-medium text-gray-800">{{ $pp->pickup_name }}</span>
                @if($pp->is_default)<span class="text-[10px] bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded">ডিফল্ট</span>@endif
            </div>
            <p class="text-xs text-gray-400">{{ $pp->phone }} · {{ $pp->city }}, {{ $pp->district }}</p>
        </div>
        @empty
        <p class="text-xs text-gray-400">কোনো পিকআপ পয়েন্ট নেই।</p>
        @endforelse
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
