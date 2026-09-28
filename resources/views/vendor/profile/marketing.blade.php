@extends('vendor.layout')
@section('title', 'মার্কেটিং')

@section('content')

@include('vendor.profile._tabs', ['active' => 'marketing'])

<form method="POST" action="{{ route('vendor.profile.marketing.update') }}">
    @csrf @method('PUT')

    <div class="bg-white rounded-xl border border-gray-100 p-6 mb-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4 pb-2 border-b">Meta (Facebook) Pixel</h3>

        @if($setting->admin_blocked)
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded px-4 py-3">
                অ্যাডমিন আপনার পিক্সেল সাময়িকভাবে বন্ধ রেখেছেন। বিস্তারিত জানতে অ্যাডমিনের সাথে যোগাযোগ করুন।
            </div>
        @endif

        <input type="hidden" name="pixel_enabled" value="0">
        <label class="flex items-start gap-3 cursor-pointer mb-5">
            <input type="checkbox" name="pixel_enabled" value="1" class="mt-0.5 rounded border-gray-300"
                   @checked(old('pixel_enabled', $setting->pixel_enabled))>
            <span>
                <span class="block text-sm font-medium text-gray-700">আমার পিক্সেল চালু করুন</span>
                <span class="block text-xs text-gray-400 mt-0.5">আপনার পিক্সেল শুধু আপনার নিজের পণ্যের ইভেন্ট পাবে — পণ্য দেখা, কার্টে যোগ, চেকআউট ও অর্ডার (শুধু আপনার পণ্যের দাম)।</span>
            </span>
        </label>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="pixel_id">Pixel ID</label>
                <input id="pixel_id" name="pixel_id" inputmode="numeric" maxlength="20" value="{{ old('pixel_id', $setting->pixel_id) }}" placeholder="123456789012345"
                       class="w-full border rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('pixel_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="test_event_code">Test event code <span class="text-gray-400 font-normal">(ঐচ্ছিক)</span></label>
                <input id="test_event_code" name="test_event_code" maxlength="30" value="{{ old('test_event_code', $setting->test_event_code) }}" placeholder="TEST12345"
                       class="w-full border rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('test_event_code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-5 bg-indigo-50 border border-indigo-100 rounded-lg px-4 py-3 text-sm text-indigo-900 leading-relaxed">
            <p class="font-semibold mb-1">Pixel ID কোথায় পাবেন?</p>
            <ol class="list-decimal list-inside space-y-0.5">
                <li>business.facebook.com → <b>Events Manager</b> খুলুন।</li>
                <li>বাম দিকে <b>Data sources</b> থেকে আপনার পিক্সেল বেছে নিন (না থাকলে “Connect data” → Web দিয়ে তৈরি করুন)।</li>
                <li>পিক্সেলের নামের নিচে বা <b>Settings</b> ট্যাবে ১৫–১৬ অঙ্কের সংখ্যাটিই Pixel ID — শুধু সংখ্যাটি এখানে দিন।</li>
            </ol>
            <p class="mt-2 text-xs text-indigo-700">কোনো কোড বা স্ক্রিপ্ট পেস্ট করবেন না — পিক্সেলের কোড MoslaMart নিজে তৈরি করে। Test event code পরের ধাপে সার্ভার-সাইড টেস্টে কাজে লাগবে।</p>
        </div>
    </div>

    <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700">সংরক্ষণ করুন</button>
</form>
@endsection
