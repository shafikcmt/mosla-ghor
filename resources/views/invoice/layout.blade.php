<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ইনভয়েস') — {{ $siteName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@400;600;700&family=Noto+Sans+Bengali:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body           { font-family: 'Noto Sans Bengali', sans-serif; background: #fef9ee; }
        .font-serif-bn { font-family: 'Noto Serif Bengali', serif; }
        @media print {
            .no-print { display: none !important; }
            body      { background: white !important; }
            .card     { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
            @page     { margin: 1.2cm; size: A4 portrait; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

<nav class="no-print bg-[#0f3d22] py-4 px-5 shadow">
    <div class="max-w-2xl mx-auto flex items-center justify-between">
        <span class="font-serif-bn text-[#c9a227] text-xl font-bold">{{ ($vendor ?? null)?->shop_name ?? $siteName }}</span>
        <span class="text-green-300 text-xs">{{ $siteName }}</span>
    </div>
</nav>

<main class="flex-1 py-6 px-4">
    <div class="max-w-2xl mx-auto">
        @if(session('error'))
            <div class="no-print mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
        @endif
        @yield('content')
    </div>
</main>

<footer class="no-print py-5 text-center text-xs text-gray-400">
    {{ $siteName }} — MoslaMart
</footer>

</body>
</html>
