<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP যাচাই — মসলা মার্ট</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-700 mb-3">
            <span class="text-white text-xl font-black">ভ</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">OTP যাচাই করুন</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $masked }} — এ পাঠানো কোডটি লিখুন</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">

        @if(session('error'))
            <div class="mb-5 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('vendor.login.otp.verify.post') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">OTP কোড</label>
                <input type="text" name="code" required inputmode="numeric" autocomplete="one-time-code"
                       maxlength="6" placeholder="৬ সংখ্যার কোড"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm tracking-[0.4em] text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <button type="submit"
                    class="w-full bg-indigo-700 hover:bg-indigo-800 text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                যাচাই করে লগইন করুন
            </button>
        </form>

        <form method="POST" action="{{ route('vendor.login.otp.resend') }}" class="mt-3">
            @csrf
            <button type="submit" class="w-full text-indigo-700 text-sm font-medium hover:underline py-1">
                কোড পাইনি? আবার পাঠান
            </button>
        </form>
    </div>

    <p class="text-center text-sm text-gray-500 mt-5">
        <a href="{{ route('vendor.login.otp') }}" class="text-gray-500 hover:text-gray-700">← অন্য নম্বর</a>
        <span class="mx-1">·</span>
        <a href="{{ route('vendor.login') }}" class="text-indigo-600 font-medium hover:underline">পাসওয়ার্ড দিয়ে লগইন</a>
    </p>
</div>

</body>
</html>
