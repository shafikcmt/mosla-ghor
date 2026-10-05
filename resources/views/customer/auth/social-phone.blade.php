<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>মোবাইল নম্বর দিন — {{ $siteName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Noto Sans Bengali', sans-serif; }</style>
</head>
<body class="bg-[#fef9ee] min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <a href="/" class="inline-block mb-4"><span class="text-[#14532d] text-3xl font-bold">{{ $siteName }}</span></a>
        @if($user->avatar_url)
            <img src="{{ $user->avatar_url }}" alt="" class="w-16 h-16 rounded-full mx-auto mb-3 border-2 border-white shadow" referrerpolicy="no-referrer">
        @endif
        <h1 class="text-2xl font-bold text-gray-800">স্বাগতম, {{ $user->name }}!</h1>
        <p class="text-gray-500 text-sm mt-1">শেষ ধাপ: আপনার মোবাইল নম্বর দিন — এই নম্বরে দাম ও অর্ডারের আপডেট জানানো হবে।</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
        @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            <ul class="space-y-0.5">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('customer.social.phone.save') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">মোবাইল নম্বর <span class="text-red-500">*</span></label>
                <div class="flex">
                    <span class="inline-flex items-center gap-1 px-3 border border-r-0 border-gray-300 rounded-l-lg bg-gray-50 text-sm text-gray-600">🇧🇩 +880</span>
                    <input type="tel" name="mobile_number" value="{{ old('mobile_number') }}" required autofocus
                           placeholder="01XXXXXXXXX" inputmode="tel" autocomplete="tel"
                           class="flex-1 min-w-0 border border-gray-300 rounded-r-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
            </div>
            <button type="submit" class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                চালিয়ে যান
            </button>
        </form>

        <form method="POST" action="{{ route('customer.logout') }}" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-xs text-gray-400 hover:text-gray-600">অন্য অ্যাকাউন্ট দিয়ে লগইন করুন</button>
        </form>
    </div>
</div>

</body>
</html>
