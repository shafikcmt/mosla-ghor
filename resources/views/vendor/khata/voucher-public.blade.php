<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $tx->typeLabel() }}{{ $tx->number ? ' #'.$tx->number : '' }} — {{ $tx->vendor->shop_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Hind Siliguri', system-ui, sans-serif; } .num { font-variant-numeric: tabular-nums; } @media print { .no-print { display: none !important; } .voucher { border: 0 !important; box-shadow: none !important; } }</style>
</head>
<body class="bg-gray-100 py-4 px-3">
<div class="max-w-3xl mx-auto">
    @include('vendor.khata._voucher')
    <div class="no-print text-center mt-4">
        <button onclick="window.print()" class="bg-gray-800 text-white font-semibold px-5 py-2.5 rounded-xl">🖨 প্রিন্ট / PDF সেভ</button>
    </div>
</div>
</body>
</html>
