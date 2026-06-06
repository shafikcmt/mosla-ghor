<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ভেন্ডর লগইন — মসলা মার্ট</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-700 mb-3">
            <span class="text-white text-xl font-black">ভ</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">ভেন্ডর লগইন</h1>
        <p class="text-gray-500 text-sm mt-1">মসলা মার্ট মার্চেন্ট পোর্টাল</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">

        @if(session('error'))
            <div class="mb-5 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('vendor.login.post') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ফোন নম্বর বা ইমেইল</label>
                <input type="text" name="identifier" value="{{ old('identifier') }}" required autofocus
                       placeholder="০১XXXXXXXXX" autocomplete="username"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">পাসওয়ার্ড</label>
                <input type="password" name="password" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="remember" id="remember" class="rounded border-gray-300 text-indigo-600">
                <label for="remember" class="text-sm text-gray-600">লগইন মনে রাখুন</label>
            </div>

            <button type="submit"
                    class="w-full bg-indigo-700 hover:bg-indigo-800 text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                লগইন করুন
            </button>
        </form>

        @if(\App\Support\AuthSettings::vendorOtpLogin() && \App\Support\AuthSettings::enabledChannels() !== [])
        <div class="mt-4 pt-4 border-t border-gray-100">
            <a href="{{ route('vendor.login.otp') }}"
               class="block text-center w-full border border-indigo-700 text-indigo-700 hover:bg-indigo-700 hover:text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                OTP দিয়ে লগইন করুন
            </a>
        </div>
        @endif
    </div>

    <p class="text-center text-sm text-gray-500 mt-5">
        নতুন ভেন্ডর?
        <a href="{{ route('vendor.register') }}" class="text-indigo-600 font-medium hover:underline">রেজিস্ট্রেশন করুন</a>
    </p>
</div>

</body>
</html>
