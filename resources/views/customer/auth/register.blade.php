<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>রেজিস্ট্রেশন — মসলা ঘর</title>
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
        <h1 class="text-2xl font-bold text-gray-800">নতুন অ্যাকাউন্ট</h1>
        <p class="text-gray-500 text-sm mt-1">আপনার অর্ডার ট্র্যাক করুন সহজেই</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">

        @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            <ul class="space-y-0.5">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('customer.register.post') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">আপনার নাম <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required autocomplete="name"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">মোবাইল নম্বর <span class="text-red-500">*</span></label>
                <input type="text" name="mobile_number" value="{{ old('mobile_number') }}" required
                       placeholder="০১XXXXXXXXX"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            @if(\App\Support\AuthSettings::showEmailFieldOnRegister())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ইমেইল <span class="text-gray-400 font-normal">(ঐচ্ছিক)</span></label>
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">পাসওয়ার্ড <span class="text-red-500">*</span></label>
                <input type="password" name="password" required minlength="6" autocomplete="new-password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">পাসওয়ার্ড নিশ্চিত করুন <span class="text-red-500">*</span></label>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            <button type="submit"
                    class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                রেজিস্ট্রেশন করুন
            </button>
        </form>
    </div>

    <div class="text-center mt-5 space-y-2 text-sm text-gray-500">
        <p>আগেই অ্যাকাউন্ট আছে? <a href="{{ route('customer.login') }}" class="text-[#14532d] font-semibold hover:underline">লগইন করুন</a></p>
        <p><a href="/" class="text-gray-400 hover:text-gray-600">← হোম পেজে ফিরুন</a></p>
    </div>
</div>

</body>
</html>
