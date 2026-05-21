<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'অনুপলব্ধ' }} — মসলা ঘর</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Noto Sans Bengali', sans-serif; }</style>
</head>
<body class="bg-[#fef9ee] min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md text-center">
    <a href="/" class="inline-block mb-8">
        <span class="text-[#14532d] text-3xl font-bold">মসলা ঘর</span>
    </a>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div class="w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-5">
            <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M5.07 19H19a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16a2 2 0 001.73 3z"/>
            </svg>
        </div>

        <h2 class="text-lg font-bold text-gray-800 mb-3">{{ $title ?? 'বর্তমানে অনুপলব্ধ' }}</h2>
        <p class="text-gray-500 text-sm leading-relaxed">{{ $message }}</p>

        @if(!empty($contactUrl))
        <a href="{{ $contactUrl }}"
           class="mt-5 inline-block bg-[#14532d] text-white text-sm font-medium px-6 py-2.5 rounded-lg hover:bg-[#0d3520] transition-colors">
            যোগাযোগ করুন
        </a>
        @endif
    </div>

    <p class="mt-5 text-sm text-gray-400">
        <a href="/" class="hover:text-[#14532d] transition-colors">← হোম পেজে ফিরুন</a>
    </p>
</div>

</body>
</html>
