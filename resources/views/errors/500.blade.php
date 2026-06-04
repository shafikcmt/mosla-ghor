<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error — মসলামার্ট</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md text-center">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div class="text-5xl mb-4">⚠️</div>
        <h1 class="text-xl font-bold text-gray-800 mb-2">কিছু একটা সমস্যা হয়েছে</h1>
        <p class="text-gray-600 mb-2">Something went wrong. Please try again later.</p>
        <p class="text-gray-400 text-xs mb-6">আমাদের টিম বিষয়টি দেখছে।</p>
        <div class="flex gap-3 justify-center">
            <button onclick="location.reload()"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition-colors">
                আবার চেষ্টা করুন
            </button>
            <a href="{{ url('/') }}"
               class="border border-gray-200 text-gray-600 hover:bg-gray-50 font-semibold px-6 py-2.5 rounded-xl text-sm transition-colors">
                হোম পেজ
            </a>
        </div>
    </div>
</div>
</body>
</html>
