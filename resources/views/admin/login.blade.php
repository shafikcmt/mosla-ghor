<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অ্যাডমিন লগইন — মসলা ঘর</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0a2e17] min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-sm">

        {{-- Brand --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#c9a227] mb-3 shadow-lg">
                <span class="text-[#0d3520] text-2xl font-black">ম</span>
            </div>
            <h1 class="text-white text-xl font-bold tracking-wide">মসলা ঘর</h1>
            <p class="text-[#4d7a5a] text-sm mt-1">Admin Panel</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-2xl px-8 py-8">
            <h2 class="text-gray-800 text-base font-semibold mb-6 text-center">লগইন করুন</h2>

            @if(session('error'))
                <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">ইমেইল</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           required autofocus autocomplete="email"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-900
                                  focus:outline-none focus:ring-2 focus:ring-[#14532d] focus:border-transparent
                                  transition-shadow {{ $errors->has('email') ? 'border-red-400' : '' }}">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">পাসওয়ার্ড</label>
                    <input id="password" type="password" name="password"
                           required autocomplete="current-password"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-900
                                  focus:outline-none focus:ring-2 focus:ring-[#14532d] focus:border-transparent
                                  transition-shadow">
                </div>

                <button type="submit"
                        class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold
                               py-2.5 rounded-lg text-sm transition-colors mt-2 shadow-sm">
                    লগইন করুন
                </button>
            </form>
        </div>

        <p class="text-center text-[#3a6649] text-xs mt-6">
            &copy; {{ date('Y') }} মসলা ঘর। সমস্ত অধিকার সংরক্ষিত।
        </p>

    </div>

</body>
</html>
