<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — মসলা স্টোর</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen">

    <nav class="bg-gray-900 text-white px-6 py-3 flex items-center gap-6 shadow">
        <a href="{{ route('admin.dashboard') }}" class="font-bold text-base tracking-wide hover:text-[#c9a227] transition-colors">মসলা Admin</a>
        <a href="{{ route('admin.dashboard') }}"
           class="text-gray-300 hover:text-white text-sm transition-colors">ড্যাশবোর্ড</a>
        <a href="{{ route('admin.products.index') }}"
           class="text-gray-300 hover:text-white text-sm transition-colors">পণ্য তালিকা</a>
        <a href="{{ route('admin.products.create') }}"
           class="text-gray-300 hover:text-white text-sm transition-colors">+ নতুন পণ্য</a>
        <a href="{{ route('admin.orders.index') }}"
           class="text-gray-300 hover:text-white text-sm transition-colors">অর্ডার তালিকা</a>
        <a href="{{ route('admin.payment-settings.index') }}"
           class="text-gray-300 hover:text-white text-sm transition-colors">পেমেন্ট সেটিং</a>
        <a href="{{ route('admin.delivery-settings.index') }}"
           class="text-gray-300 hover:text-white text-sm transition-colors">ডেলিভারি সেটিং</a>
        <a href="{{ route('admin.delivery-zones.index') }}"
           class="text-gray-300 hover:text-white text-sm transition-colors">ডেলিভারি জোন</a>
        <a href="{{ route('admin.combos.index') }}"
           class="text-gray-300 hover:text-white text-sm transition-colors">কম্বো</a>
        <a href="{{ route('admin.faqs.index') }}"
           class="text-gray-300 hover:text-white text-sm transition-colors">FAQ</a>
        <a href="{{ route('admin.reviews.index') }}"
           class="text-gray-300 hover:text-white text-sm transition-colors">রিভিউ</a>
        <a href="{{ route('admin.website-settings.index') }}"
           class="text-gray-300 hover:text-white text-sm transition-colors">ওয়েব সেটিং</a>
        <a href="{{ route('admin.general-settings.index') }}"
           class="text-gray-300 hover:text-white text-sm transition-colors">জেনারেল সেটিং</a>
    </nav>

    <main class="max-w-5xl mx-auto px-4 py-6">

        @if(session('success'))
            <div class="mb-5 flex items-center gap-2 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded text-sm">
                <span>&#10003;</span> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-5 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded text-sm">
                <p class="font-medium mb-1">অনুগ্রহ করে নিচের ত্রুটিগুলো ঠিক করুন:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

</body>
</html>
