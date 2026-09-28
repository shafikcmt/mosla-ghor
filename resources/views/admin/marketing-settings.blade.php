@extends('admin.layout')

@section('title', 'মার্কেটিং / ট্র্যাকিং')

@section('content')

<h1 class="text-xl font-bold text-gray-800 mb-6">মার্কেটিং / ট্র্যাকিং — Meta (Facebook) Pixel</h1>

<form action="{{ route('admin.marketing-settings.update') }}" method="POST">
    @csrf

    <div class="bg-white rounded shadow-sm border border-gray-100 mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-600">প্ল্যাটফর্ম পিক্সেল (MoslaMart)</h2>
        </div>
        <div class="px-6 py-5 space-y-5">
            <input type="hidden" name="pixel_enabled" value="0">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="pixel_enabled" value="1" class="mt-0.5 rounded border-gray-300"
                       @checked(old('pixel_enabled', $settings->pixel_enabled))>
                <span>
                    <span class="block text-sm font-medium text-gray-700">Meta Pixel চালু করুন</span>
                    <span class="block text-xs text-gray-400 mt-0.5">চালু থাকলে ওয়েবসাইটের পেজে পিক্সেল লোড হবে (অ্যাডমিন ও ভেন্ডর প্যানেলে কখনো নয়)।</span>
                </span>
            </label>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="pixel_ids">Pixel ID (এক বা একাধিক)</label>
                <textarea id="pixel_ids" name="pixel_ids" rows="2" maxlength="200" placeholder="123456789012345"
                          class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-gray-400">{{ old('pixel_ids', implode(', ', (array) $settings->pixel_ids)) }}</textarea>
                <p class="text-gray-400 text-xs mt-1">শুধু সংখ্যা (১০–২০ অঙ্ক), কমা দিয়ে আলাদা করুন, সর্বোচ্চ ৫টি। Events Manager → Data sources → আপনার পিক্সেল → Settings-এ Pixel ID পাবেন। কোনো কোড/স্ক্রিপ্ট পেস্ট করবেন না — কোড আমরাই তৈরি করি।</p>
                @error('pixel_ids')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="test_event_code">Test event code (ঐচ্ছিক)</label>
                <input id="test_event_code" name="test_event_code" maxlength="30" value="{{ old('test_event_code', $settings->test_event_code) }}" placeholder="TEST12345"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-gray-400">
                <p class="text-gray-400 text-xs mt-1">সার্ভার-সাইড (Conversions API) টেস্টে ব্যবহার হবে। ব্রাউজার ইভেন্ট দেখতে Events Manager → Test Events-এ ওয়েবসাইটের লিংক দিয়ে সাইট খুলুন।</p>
                @error('test_event_code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <span class="block text-xs font-medium text-gray-600 mb-2">প্ল্যাটফর্ম পিক্সেল কোন পণ্যের ইভেন্ট পাবে</span>
                @php $scope = old('platform_pixel_scope', $settings->platform_pixel_scope ?: 'all'); @endphp
                <label class="flex items-start gap-2 mb-2 cursor-pointer text-sm text-gray-700">
                    <input type="radio" name="platform_pixel_scope" value="all" class="mt-1" @checked($scope === 'all')>
                    <span>সব পণ্য <span class="text-xs text-gray-400">(অ্যাডমিন + সব ভেন্ডরের পণ্য — ডিফল্ট)</span></span>
                </label>
                <label class="flex items-start gap-2 cursor-pointer text-sm text-gray-700">
                    <input type="radio" name="platform_pixel_scope" value="own" class="mt-1" @checked($scope === 'own')>
                    <span>শুধু নিজের পণ্য <span class="text-xs text-gray-400">(ভেন্ডর ছাড়া অ্যাডমিনের পণ্য)</span></span>
                </label>
            </div>

            <input type="hidden" name="track_admin_users" value="0">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="track_admin_users" value="1" class="mt-0.5 rounded border-gray-300"
                       @checked(old('track_admin_users', $settings->track_admin_users))>
                <span>
                    <span class="block text-sm font-medium text-gray-700">লগইন করা অ্যাডমিনকেও ট্র্যাক করুন</span>
                    <span class="block text-xs text-gray-400 mt-0.5">বন্ধ থাকলে (প্রস্তাবিত) অ্যাডমিনের টেস্ট ভিজিট/অর্ডার বিজ্ঞাপনের ডেটায় যাবে না। Pixel Helper দিয়ে টেস্ট করতে সাময়িকভাবে চালু করুন বা লগআউট করে দেখুন।</span>
                </span>
            </label>
        </div>
    </div>

    <div class="bg-white rounded shadow-sm border border-gray-100 mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-600">ভেন্ডর পিক্সেল</h2>
        </div>
        <div class="px-6 py-5 space-y-5">
            <input type="hidden" name="vendor_pixels_enabled" value="0">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="vendor_pixels_enabled" value="1" class="mt-0.5 rounded border-gray-300"
                       @checked(old('vendor_pixels_enabled', $settings->vendor_pixels_enabled))>
                <span>
                    <span class="block text-sm font-medium text-gray-700">ভেন্ডরদের নিজস্ব পিক্সেল ব্যবহার করতে দিন (মাস্টার সুইচ)</span>
                    <span class="block text-xs text-gray-400 mt-0.5">চালু থাকলে ভেন্ডর প্রোফাইলে “Marketing” ট্যাব দেখাবে। প্রতিটি ভেন্ডরের পিক্সেল শুধু তার নিজের পণ্যের ইভেন্ট ও দাম পাবে।</span>
                </span>
            </label>
            <input type="hidden" name="vendor_capi_allowed" value="0">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="vendor_capi_allowed" value="1" class="mt-0.5 rounded border-gray-300"
                       @checked(old('vendor_capi_allowed', $settings->vendor_capi_allowed))>
                <span>
                    <span class="block text-sm font-medium text-gray-700">ভেন্ডরদের Conversions API অনুমতি দিন</span>
                    <span class="block text-xs text-gray-400 mt-0.5">পরের ধাপে (Phase B) কার্যকর হবে। চালু করলে ভেন্ডর তাদের নিজের অর্ডারের কাস্টমারের হ্যাশ করা তথ্য পাবে।</span>
                </span>
            </label>
        </div>
    </div>

    <div class="flex gap-3 mb-8">
        <button type="submit" class="bg-gray-800 text-white px-8 py-2.5 rounded text-sm font-medium hover:bg-gray-700 transition-colors">সংরক্ষণ করুন</button>
    </div>
</form>

<div class="bg-white rounded shadow-sm border border-gray-100 mb-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-sm font-semibold text-gray-600">পিক্সেল সেট করা ভেন্ডর</h2>
    </div>
    @if($vendorPixels->isEmpty())
        <p class="px-6 py-5 text-sm text-gray-400">এখনও কোনো ভেন্ডর পিক্সেল সেট করেননি।</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">ভেন্ডর</th>
                    <th class="px-4 py-3 text-left">Pixel ID</th>
                    <th class="px-4 py-3 text-left">অবস্থা</th>
                    <th class="px-4 py-3 text-left">শেষ Purchase ইভেন্ট</th>
                    <th class="px-4 py-3 text-right">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($vendorPixels as $vp)
                @php
                    $vendorOk = $vp->vendor && $vp->vendor->status === 'approved' && $vp->vendor->is_active;
                    $status = match (true) {
                        $vp->admin_blocked                  => ['অ্যাডমিন বন্ধ করেছে', 'text-red-600'],
                        ! $settings->vendor_pixels_enabled  => ['মাস্টার সুইচ বন্ধ', 'text-gray-500'],
                        ! $vp->pixel_enabled                => ['ভেন্ডর বন্ধ রেখেছে', 'text-gray-500'],
                        ! $vendorOk                         => ['ভেন্ডর সক্রিয়/অনুমোদিত নয়', 'text-gray-500'],
                        default                             => ['চালু', 'text-green-700'],
                    };
                @endphp
                <tr>
                    <td class="px-4 py-3 text-gray-800">{{ $vp->vendor?->shop_name ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono text-gray-700">{{ $vp->pixel_id }}</td>
                    <td class="px-4 py-3 {{ $status[1] }}">{{ $status[0] }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $vp->last_event_at ? $vp->last_event_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') : '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('admin.marketing-settings.vendors.toggle', $vp) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-xs font-medium {{ $vp->admin_blocked ? 'text-green-700' : 'text-red-600' }}">
                                {{ $vp->admin_blocked ? 'আবার চালু করুন' : 'বন্ধ করুন' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
