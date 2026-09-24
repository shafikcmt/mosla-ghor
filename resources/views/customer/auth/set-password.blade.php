<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>পাসওয়ার্ড সেট করুন — {{ $siteName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Noto Sans Bengali', sans-serif; }</style>
</head>
<body class="bg-[#fef9ee] min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">

    <div class="text-center mb-8">
        <a href="/" class="inline-block mb-4">
            <span class="text-[#14532d] text-3xl font-bold">{{ $siteName }}</span>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">পাসওয়ার্ড সেট করুন</h1>
        <p class="text-gray-500 text-sm mt-1">enquiry track করতে একটি পাসওয়ার্ড দিন</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">

        @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            <ul class="space-y-0.5">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="mb-4 text-sm text-gray-600 bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
            অ্যাকাউন্ট: <span class="font-semibold text-gray-800">{{ $user->name }}</span>
            <br><span class="text-gray-500 text-xs">{{ $user->phone }}</span>
        </div>

        {{-- Action = current signed URL so the signature carries over to POST. --}}
        <form method="POST" action="{{ request()->fullUrl() }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">নতুন পাসওয়ার্ড <span class="text-red-500">*</span></label>
                <input type="password" name="password" required autocomplete="new-password" minlength="6"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                <p class="text-[11px] text-gray-400 mt-1">কমপক্ষে ৬ অক্ষর।</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">পাসওয়ার্ড আবার দিন <span class="text-red-500">*</span></label>
                <input type="password" name="password_confirmation" required autocomplete="new-password" minlength="6"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            <button type="submit"
                    class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                পাসওয়ার্ড সেট করুন ও লগইন করুন
            </button>
        </form>
    </div>

    <div class="text-center mt-5 space-y-2 text-sm text-gray-500">
        <p><a href="{{ route('customer.login') }}" class="text-[#14532d] font-semibold hover:underline">পাসওয়ার্ড দিয়ে লগইন করুন</a></p>
        <p><a href="/" class="text-gray-400 hover:text-gray-600">← হোম পেজে ফিরুন</a></p>
    </div>
</div>

</body>
</html>
