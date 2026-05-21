<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>লগইন — মসলা ঘর</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Noto Sans Bengali', sans-serif; }</style>
</head>
<body class="bg-[#fef9ee] min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">

    <div class="text-center mb-8">
        <a href="/" class="inline-block mb-4">
            <span class="text-[#14532d] text-3xl font-bold">মসলা ঘর</span>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">লগইন করুন</h1>
        <p class="text-gray-500 text-sm mt-1">আপনার অর্ডার দেখুন ও ট্র্যাক করুন</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">

        @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
        @endif

        @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            <ul class="space-y-0.5">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('customer.login.post') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">মোবাইল নম্বর <span class="text-red-500">*</span></label>
                <input type="text" name="mobile_number" value="{{ old('mobile_number') }}" required
                       placeholder="০১XXXXXXXXX" autocomplete="username"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">পাসওয়ার্ড <span class="text-red-500">*</span></label>
                <input type="password" name="password" required autocomplete="current-password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="remember" id="remember" value="1" class="rounded border-gray-300 text-[#14532d]">
                <label for="remember" class="text-sm text-gray-600">লগইন মনে রাখুন</label>
            </div>

            <button type="submit"
                    class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                লগইন করুন
            </button>
        </form>
    </div>

    <div class="text-center mt-5 space-y-2 text-sm text-gray-500">
        <p>নতুন? <a href="{{ route('customer.register') }}" class="text-[#14532d] font-semibold hover:underline">রেজিস্ট্রেশন করুন</a></p>
        <p>মার্চেন্ট? <a href="{{ route('vendor.login') }}" class="text-indigo-600 font-semibold hover:underline">মার্চেন্ট লগইন</a></p>
        <p><a href="/" class="text-gray-400 hover:text-gray-600">← হোম পেজে ফিরুন</a></p>
    </div>
</div>

</body>
</html>
