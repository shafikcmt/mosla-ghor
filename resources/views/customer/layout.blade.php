<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'আমার অ্যাকাউন্ট') — মসলা ঘর</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Bengali', sans-serif; background: #fef9ee; }
        .sidebar-link { @apply flex items-center gap-3 px-4 py-2.5 text-sm rounded-lg transition-colors; }
        .sidebar-link.active { @apply bg-[#14532d] text-white font-semibold; }
        .sidebar-link:not(.active) { @apply text-gray-600 hover:bg-gray-100; }
    </style>
</head>
<body class="min-h-screen">

{{-- Top Header --}}
<header class="bg-[#14532d] shadow-md sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <button id="sidebar-toggle" class="md:hidden text-green-300 hover:text-white p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <a href="/" class="text-[#c9a227] text-lg font-bold">মসলা ঘর</a>
            <span class="text-green-400 text-xs hidden sm:inline">/ অ্যাকাউন্ট</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-green-200 text-sm hidden sm:inline">{{ Auth::user()->name }}</span>
            <form method="POST" action="{{ route('customer.logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg transition-colors">
                    লগআউট
                </button>
            </form>
        </div>
    </div>
</header>

<div class="max-w-7xl mx-auto px-4 py-5 flex gap-5">

    {{-- Sidebar --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-gray-200 overflow-y-auto pt-20
                  -translate-x-full md:translate-x-0 md:static md:inset-auto md:w-52 md:pt-0 md:bg-transparent md:border-none
                  transition-transform duration-200 shrink-0">
        <nav class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden md:block">
            @php
            $r = request()->route()?->getName();
            $navItems = [
                ['route' => 'customer.account',                        'label' => 'ড্যাশবোর্ড',        'icon' => '🏠'],
                ['route' => 'customer.orders.index',                   'label' => 'আমার অর্ডার',       'icon' => '📦'],
                ['route' => 'customer.wholesale.enquiry.index',        'label' => 'পাইকারি Enquiry',   'icon' => '🏭'],
                ['route' => 'customer.wholesale.quote.index',          'label' => 'কোটেশন',            'icon' => '📋'],
                ['route' => 'customer.returns.index',                  'label' => 'রিটার্ন/রিফান্ড',  'icon' => '↩️'],
                ['route' => 'customer.wishlist.index',                 'label' => 'উইশলিস্ট',          'icon' => '❤️'],
                ['route' => 'customer.addresses.index',                'label' => 'ঠিকানা',             'icon' => '📍'],
                ['route' => 'customer.profile.edit',                   'label' => 'প্রোফাইল',           'icon' => '👤'],
                ['route' => 'customer.support.index',                  'label' => 'সাপোর্ট',            'icon' => '💬'],
            ];
            @endphp
            @foreach($navItems as $item)
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-3 px-4 py-3 text-sm border-b border-gray-50 transition-colors
                      {{ str_starts_with($r ?? '', rtrim($item['route'], '.index')) ? 'bg-[#14532d] text-white font-semibold' : 'text-gray-700 hover:bg-green-50' }}">
                <span class="text-base">{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
            </a>
            @endforeach
            <form method="POST" action="{{ route('customer.logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors">
                    <span class="text-base">🚪</span><span>লগআউট</span>
                </button>
            </form>
        </nav>
    </aside>

    {{-- Overlay for mobile --}}
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-20 hidden md:hidden" onclick="closeSidebar()"></div>

    {{-- Main Content --}}
    <main class="flex-1 min-w-0">

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
        @endif
        @if(session('info'))
        <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-sm">
            {{ session('info') }}
        </div>
        @endif
        @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            <ul class="space-y-0.5">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        @yield('content')
    </main>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    });
    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
</script>
</body>
</html>
