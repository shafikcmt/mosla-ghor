<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>ফাইল অনেক বড় — মসলামার্ট</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md text-center">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div class="text-5xl mb-4">📦</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">ফাইল অনেক বড়</h1>
        <p class="text-gray-600 mb-6">{{ $message ?? 'একবারে পাঠানো ফাইলগুলো মোট অনেক বড়। কয়েকটি ছবি কম দিয়ে আবার চেষ্টা করুন।' }}</p>
        <p class="text-gray-500 text-sm mb-6">ফিরে গিয়ে ছবিগুলো আবার বেছে নিন — কিছুই সংরক্ষিত হয়নি।</p>
        <div class="flex gap-3 justify-center">
            <button onclick="history.back()"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition-colors">
                ← ফিরে যান
            </button>
        </div>
    </div>
</div>
</body>
</html>
