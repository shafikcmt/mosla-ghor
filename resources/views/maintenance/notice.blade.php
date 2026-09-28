@php
    use App\Support\Maintenance;
    $vendor    = $vendor ?? false;
    $preview   = $preview ?? false;
    $siteName  = \App\Models\WebsiteSetting::siteName();
    $until     = Maintenance::until();
    $showCount = $until && ! Maintenance::expired();
    $links     = Maintenance::contactLinks();
    // A blocked form submission (e.g. invoice payment) gets an explicit line.
    $blockedAction = ! $preview && ! in_array(request()->method(), ['GET', 'HEAD'], true);
@endphp
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ Maintenance::title() }} — {{ $siteName }}</title>
    <link rel="icon" href="{{ asset('icons/icon-192.png') }}">
    {{-- Domain verification keeps working while the site is in maintenance. --}}
    @include('partials.storefront.site-meta-tags')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --deep: {{ $vendor ? '#1e1b4b' : '#0f3d22' }}; --main: {{ $vendor ? '#4338ca' : '#14532d' }}; --gold: #c9a227; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { min-height: 100vh; font-family: 'Noto Sans Bengali', system-ui, sans-serif; color: #1c1917;
               background: {{ $vendor ? '#eef2ff' : '#fef9ee' }}; display: flex; flex-direction: column; }
        .mn-preview { background: #b91c1c; color: #fff; text-align: center; font-size: 13px; padding: 8px 16px; }
        .mn-preview a { color: #fff; font-weight: 700; }
        .mn-wrap { flex: 1; display: flex; align-items: center; justify-content: center; padding: 32px 16px; }
        .mn-card { width: 100%; max-width: 480px; background: #fff; border-radius: 20px; padding: 32px 24px; text-align: center;
                   box-shadow: 0 12px 40px rgba(15, 61, 34, .12); border-top: 5px solid var(--gold); }
        .mn-brand { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 22px; }
        .mn-brand img { width: 44px; height: 44px; border-radius: 12px; }
        .mn-brand span { font-weight: 700; font-size: 18px; color: var(--deep); }
        .mn-icon { width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 50%; background: #fdf6e3;
                   display: flex; align-items: center; justify-content: center; font-size: 30px; }
        h1 { font-size: 22px; line-height: 1.4; color: var(--deep); margin-bottom: 10px; }
        .mn-msg { color: #57534e; line-height: 1.8; font-size: 15px; white-space: pre-line; }
        .mn-action { margin-top: 14px; padding: 10px 12px; border-radius: 10px; background: #fef2f2; color: #b91c1c; font-size: 14px; }
        .mn-count { margin-top: 22px; }
        .mn-count small { display: block; color: #78716c; font-size: 13px; margin-bottom: 8px; }
        .mn-units { display: flex; justify-content: center; gap: 8px; }
        .mn-unit { min-width: 64px; padding: 10px 6px; border-radius: 12px; background: var(--deep); color: #fff; }
        .mn-unit b { display: block; font-size: 22px; line-height: 1.1; font-variant-numeric: tabular-nums; }
        .mn-unit span { font-size: 12px; opacity: .8; }
        .mn-until { margin-top: 8px; font-size: 13px; color: #78716c; }
        .mn-btns { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-top: 24px; }
        .mn-btn { flex: 1 1 140px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 48px;
                  padding: 10px 16px; border-radius: 12px; font-weight: 600; text-decoration: none; font-size: 15px; }
        .mn-call { background: var(--main); color: #fff; }
        .mn-wa { background: #16a34a; color: #fff; }
        .mn-links { margin-top: 20px; font-size: 14px; }
        .mn-links a { color: var(--main); font-weight: 600; }
        footer { text-align: center; font-size: 12px; color: #a8a29e; padding: 16px; }
    </style>
</head>
<body>
@if($preview)
    <div class="mn-preview">প্রিভিউ — গ্রাহকরা এই পেজটি দেখবেন। <a href="{{ route('admin.website-settings.index') }}#maintenance">সেটিংয়ে ফিরে যান</a></div>
@endif
<main class="mn-wrap">
    <div class="mn-card">
        <div class="mn-brand">
            <img src="{{ asset('icons/icon-192.png') }}" alt="">
            <span>{{ $siteName }}{{ $vendor ? ' — মার্চেন্ট প্যানেল' : '' }}</span>
        </div>
        <div class="mn-icon" aria-hidden="true">🛠️</div>
        <h1>{{ Maintenance::title() }}</h1>
        <p class="mn-msg">{{ Maintenance::message() }}</p>

        @if($blockedAction)
            <p class="mn-action">মেইনটেন্যান্স চলাকালীন এই কাজটি করা যাবে না। পরে আবার চেষ্টা করুন।</p>
        @endif

        @if($showCount)
            <div class="mn-count" data-mn-until="{{ $until->toIso8601String() }}">
                <small>আনুমানিক আর বাকি</small>
                <div class="mn-units">
                    <div class="mn-unit"><b data-mn="d">০</b><span>দিন</span></div>
                    <div class="mn-unit"><b data-mn="h">০</b><span>ঘণ্টা</span></div>
                    <div class="mn-unit"><b data-mn="m">০</b><span>মিনিট</span></div>
                    <div class="mn-unit"><b data-mn="s">০</b><span>সেকেন্ড</span></div>
                </div>
                <p class="mn-until">{{ $until->format('d M Y, h:i A') }} (বাংলাদেশ সময়)</p>
            </div>
        @endif

        @if($links)
            <div class="mn-btns">
                <a class="mn-btn mn-call" href="{{ $links['tel'] }}">📞 কল করুন</a>
                <a class="mn-btn mn-wa" href="{{ $links['whatsapp'] }}" target="_blank" rel="noopener">WhatsApp</a>
            </div>
        @endif

        @if($vendor)
            <p class="mn-links">
                <a href="{{ route('vendor.login') }}">লগইন পেজ</a>
            </p>
        @elseif(! request()->is('track-order'))
            <p class="mn-links"><a href="{{ route('track-order') }}">আগের অর্ডার ট্র্যাক করুন →</a></p>
        @endif
    </div>
</main>
<footer>© {{ date('Y') }} {{ $siteName }}</footer>

@if($showCount)
<script>
(() => {
    const box = document.querySelector('[data-mn-until]');
    const end = new Date(box.dataset.mnUntil).getTime();
    const bn = n => String(n).replace(/\d/g, d => '০১২৩৪৫৬৭৮৯'[d]);
    const set = (k, v) => { box.querySelector(`[data-mn="${k}"]`).textContent = bn(v); };
    const tick = () => {
        let s = Math.max(0, Math.floor((end - Date.now()) / 1000));
        if (s === 0) { box.hidden = true; clearInterval(timer); return; }
        set('d', Math.floor(s / 86400)); s %= 86400;
        set('h', Math.floor(s / 3600)); s %= 3600;
        set('m', Math.floor(s / 60)); set('s', s % 60);
    };
    const timer = setInterval(tick, 1000);
    tick();
})();
</script>
@endif
</body>
</html>
