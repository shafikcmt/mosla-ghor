<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ভেন্ডর রেজিস্ট্রেশন — মসলা মার্ট</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-xl">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-700 mb-3">
            <span class="text-white text-xl font-black">ভ</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">মার্চেন্ট রেজিস্ট্রেশন</h1>
        <p class="text-gray-500 text-sm mt-1">মসলা মার্টে আপনার দোকান খুলুন</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">

        @if(session('error'))
            <div class="mb-5 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('vendor.register.post') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">দোকানের নাম <span class="text-red-500">*</span></label>
                    <input type="text" name="shop_name" value="{{ old('shop_name') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">মালিকের নাম <span class="text-red-500">*</span></label>
                    <input type="text" name="owner_name" value="{{ old('owner_name') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ফোন নম্বর <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required
                           placeholder="০১XXXXXXXXX" inputmode="tel"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">এই নম্বর দিয়েই লগইন করবেন</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ব্যবসার ধরন</label>
                    <input type="text" name="business_type" value="{{ old('business_type') }}" placeholder="যেমন: আমদানিকারক, পাইকারি"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ইমেইল <span class="text-gray-400">(ঐচ্ছিক)</span></label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ঠিকানা</label>
                <textarea name="address" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('address') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">পাসওয়ার্ড <span class="text-red-500">*</span></label>
                    <input type="password" name="password" required minlength="8"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">পাসওয়ার্ড নিশ্চিত করুন <span class="text-red-500">*</span></label>
                    <input type="password" name="password_confirmation" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">লোগো (ঐচ্ছিক)</label>
                    <input type="file" name="logo" accept="image/*"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">KYC / ট্রেড লাইসেন্স (ঐচ্ছিক)</label>
                    <input type="file" name="kyc_document" accept="image/*,.pdf"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
                </div>
            </div>

            <button type="submit"
                    class="w-full bg-indigo-700 hover:bg-indigo-800 text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                রেজিস্ট্রেশন করুন
            </button>
        </form>
    </div>

    <p class="text-center text-sm text-gray-500 mt-5">
        আগেই অ্যাকাউন্ট আছে?
        <a href="{{ route('vendor.login') }}" class="text-indigo-600 font-medium hover:underline">লগইন করুন</a>
    </p>
</div>

</body>
</html>
