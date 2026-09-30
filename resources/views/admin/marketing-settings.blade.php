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
                <p class="text-gray-400 text-xs mt-1">সার্ভার-সাইড (Conversions API) টেস্টে ব্যবহার হবে — দেওয়া থাকলে সব সার্ভার ইভেন্ট শুধু Test Events-এ যায়, তাই টেস্ট শেষে মুছে দিন। ব্রাউজার ইভেন্ট দেখতে Events Manager → Test Events-এ ওয়েবসাইটের লিংক দিয়ে সাইট খুলুন।</p>
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
            <h2 class="text-sm font-semibold text-gray-600">Conversions API (সার্ভার-সাইড ইভেন্ট)</h2>
        </div>
        <div class="px-6 py-5 space-y-5">
            <p class="text-xs text-gray-500">ব্রাউজার পিক্সেল iPhone, ad-blocker বা স্লো নেটের কারণে অনেক অর্ডার মিস করে। চালু করলে Purchase, Lead ও CompleteRegistration সার্ভার থেকেও Meta-তে যাবে (একই event ID, তাই ডাবল গণনা হবে না)। কাস্টমারের ফোন/ইমেইল হ্যাশ করে পাঠানো হয়। শুধু প্ল্যাটফর্ম পিক্সেলে যায়।</p>

            <input type="hidden" name="capi_enabled" value="0">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="capi_enabled" value="1" class="mt-0.5 rounded border-gray-300"
                       @checked(old('capi_enabled', $settings->capi_enabled))>
                <span>
                    <span class="block text-sm font-medium text-gray-700">Conversions API চালু করুন</span>
                    <span class="block text-xs text-gray-400 mt-0.5">পিক্সেল চালু ও Pixel ID দেওয়া থাকতে হবে।</span>
                </span>
            </label>
            @error('capi_enabled')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror

            <div>
                @php $hasCapiToken = \App\Support\MetaCapi::token($settings) !== null; @endphp
                <label class="block text-xs font-medium text-gray-600 mb-1" for="capi_access_token">Access token</label>
                <input id="capi_access_token" name="capi_access_token" type="password" autocomplete="new-password" maxlength="1000"
                       placeholder="{{ $hasCapiToken ? '•••••••• সংরক্ষিত আছে — বদলাতে নতুন টোকেন দিন' : 'EAAG…' }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-gray-400">
                <p class="text-gray-400 text-xs mt-1">Events Manager → আপনার পিক্সেল → Settings → Conversions API → “Generate access token”। টোকেন এনক্রিপ্ট করে রাখা হয়, কোথাও দেখানো হয় না। একাধিক Pixel ID থাকলে টোকেনটি সবগুলোর অ্যাক্সেস থাকতে হবে।</p>
                @error('capi_access_token')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                @if($hasCapiToken)
                <input type="hidden" name="capi_token_clear" value="0">
                <label class="flex items-center gap-2 mt-2 text-xs text-gray-600 cursor-pointer">
                    <input type="checkbox" name="capi_token_clear" value="1" class="rounded border-gray-300"> সংরক্ষিত টোকেন মুছে ফেলুন
                </label>
                @endif
            </div>

            <div class="text-xs text-gray-500 space-y-1">
                <p>শেষ সফল পাঠানো: <span class="text-gray-700">{{ $settings->capi_last_success_at ? $settings->capi_last_success_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') : '—' }}</span></p>
                @if($settings->capi_last_error_at)
                <p>শেষ ত্রুটি ({{ $settings->capi_last_error_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}): <span class="text-red-600">{{ $settings->capi_last_error }}</span></p>
                @endif
                @if($settings->test_event_code)
                <p class="text-amber-700">⚠ Test event code দেওয়া আছে — সার্ভার ইভেন্ট শুধু “Test Events”-এ যাচ্ছে। টেস্ট শেষে কোডটি মুছে সংরক্ষণ করুন।</p>
                @endif
            </div>
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
                    <span class="block text-xs text-gray-400 mt-0.5">এখনও কার্যকর নয় — Conversions API এখন শুধু প্ল্যাটফর্ম পিক্সেলে পাঠায়। ভবিষ্যতে চালু হলে ভেন্ডর শুধু তাদের নিজের অর্ডারের কাস্টমারের হ্যাশ করা তথ্য পাবে।</span>
                </span>
            </label>
        </div>
    </div>

    <div class="flex gap-3 mb-8">
        <button type="submit" class="bg-gray-800 text-white px-8 py-2.5 rounded text-sm font-medium hover:bg-gray-700 transition-colors">সংরক্ষণ করুন</button>
    </div>
</form>

<form action="{{ route('admin.marketing-settings.capi-test') }}" method="POST" class="mb-8">
    @csrf
    <button type="submit" class="border border-gray-300 text-gray-700 px-5 py-2 rounded text-sm hover:bg-gray-50">Conversions API টেস্ট ইভেন্ট পাঠান</button>
    <span class="text-xs text-gray-400 ml-2">Test event code দিয়ে সংরক্ষণ করার পর চাপুন, তারপর Events Manager → Test Events দেখুন।</span>
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
