<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP লগইন — মসলা মার্ট</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-700 mb-3">
            <span class="text-white text-xl font-black">ভ</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">OTP দিয়ে লগইন</h1>
        <p class="text-gray-500 text-sm mt-1">পাসওয়ার্ড ছাড়াই কোড দিয়ে লগইন করুন</p>
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

        <form method="POST" action="{{ route('vendor.login.otp.send') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ফোন নম্বর বা ইমেইল</label>
                <input type="text" name="identifier" value="{{ old('identifier') }}" required autofocus
                       placeholder="০১XXXXXXXXX" autocomplete="username"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <button type="submit"
                    class="w-full bg-indigo-700 hover:bg-indigo-800 text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                কোড পাঠান
            </button>
        </form>
    </div>

    <p class="text-center text-sm text-gray-500 mt-5">
        <a href="{{ route('vendor.login') }}" class="text-indigo-600 font-medium hover:underline">পাসওয়ার্ড দিয়ে লগইন করুন</a>
    </p>
</div>

</body>
</html>
