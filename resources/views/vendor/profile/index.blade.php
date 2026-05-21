@extends('vendor.layout')
@section('title', 'শপ প্রোফাইল')

@section('content')

<h2 class="text-lg font-bold text-gray-800 mb-6">শপ প্রোফাইল</h2>

<form method="POST" action="{{ route('vendor.profile.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4 pb-2 border-b">দোকানের তথ্য</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">দোকানের নাম</label>
                <input type="text" value="{{ $vendor->shop_name }}" disabled
                       class="w-full border border-gray-200 rounded px-3 py-2 text-sm bg-gray-50 text-gray-500">
                <p class="text-xs text-gray-400 mt-1">দোকানের নাম পরিবর্তন করতে অ্যাডমিনের সাথে যোগাযোগ করুন।</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">মালিকের নাম <span class="text-red-500">*</span></label>
                <input type="text" name="owner_name" value="{{ old('owner_name', $vendor->owner_name) }}" required
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ফোন <span class="text-red-500">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', $vendor->phone) }}" required
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ব্যবসার ধরন</label>
                <input type="text" name="business_type" value="{{ old('business_type', $vendor->business_type) }}"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">ঠিকানা</label>
                <textarea name="address" rows="2"
                          class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('address', $vendor->address) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">লোগো</label>
                @if($vendor->logo)
                <div class="mb-2">
                    <img src="{{ asset($vendor->logo) }}" alt="" class="w-20 h-20 rounded-lg object-cover border">
                </div>
                @endif
                <input type="file" name="logo" accept="image/*"
                       class="w-full border rounded px-3 py-2 text-sm bg-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ব্যানার</label>
                @if($vendor->banner)
                <div class="mb-2">
                    <img src="{{ asset($vendor->banner) }}" alt="" class="h-16 rounded-lg object-cover border">
                </div>
                @endif
                <input type="file" name="banner" accept="image/*"
                       class="w-full border rounded px-3 py-2 text-sm bg-white">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">পেমেন্ট তথ্য (পেআউটের জন্য)</label>
                <textarea name="payment_info" rows="3" placeholder="বিকাশ: 01XXXXXXXXX, ব্যাংক: ..."
                          class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('payment_info', isset($vendor->payment_info['details']) ? $vendor->payment_info['details'] : '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 p-5 mb-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-3">অ্যাকাউন্ট তথ্য (শুধু দেখার জন্য)</h3>
        <dl class="grid grid-cols-2 gap-3 text-sm">
            <div>
                <dt class="text-gray-500 text-xs">ইমেইল</dt>
                <dd class="font-medium">{{ $vendor->email }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 text-xs">অবস্থা</dt>
                <dd>
                    <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium
                        @if($vendor->status === 'approved') bg-green-100 text-green-700
                        @elseif($vendor->status === 'pending') bg-yellow-100 text-yellow-700
                        @else bg-red-100 text-red-700 @endif">
                        {{ $vendor->status }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-gray-500 text-xs">রেজিস্ট্রেশন তারিখ</dt>
                <dd class="font-medium">{{ $vendor->created_at->format('d M Y') }}</dd>
            </div>
        </dl>
    </div>

    <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-2 rounded-lg text-sm transition-colors">
        প্রোফাইল আপডেট করুন
    </button>
</form>

@endsection
