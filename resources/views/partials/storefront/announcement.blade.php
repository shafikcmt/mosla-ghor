{{--
    Top announcement marquee — dynamic, admin-controlled.
    ──────────────────────────────────────────────────────────────
    • Reads from WebsiteSetting (key/value). Self-computes $ws if not passed,
      so it works whether included from the navbar partial or standalone.
    • Hides entirely when disabled or when there is no text.
    • Text is escaped (e()) — no raw HTML, XSS-safe. Optional link wraps the
      whole strip. Colours fall back to the spice gold / deep-green defaults.
    • Scoped CSS (.ms-marquee) injected once per page via @once.
--}}
@php
    $ws = $ws ?? \App\Models\WebsiteSetting::allKeyed();

    $annEnabled = ($ws['announcement_enabled'] ?? '1') === '1';
    $annText1   = trim($ws['announcement_text_1'] ?? '');
    $annText2   = trim($ws['announcement_text_2'] ?? '');

    // Seed default copy ONLY on a fresh install (the key was never configured).
    // If the admin saved a blank text, respect it — an empty bar stays hidden.
    if (! array_key_exists('announcement_text_1', $ws) && ! array_key_exists('announcement_text_2', $ws)) {
        $annText1 = 'ঈদ স্পেশাল — এখনই অর্ডার করুন এবং পান বিশেষ ছাড়! ✨ ১০০% খাঁটি মসলা — কোনো ভেজাল নেই ✨ সারা বাংলাদেশে হোম ডেলিভারি';
    }

    $annSegments = array_values(array_filter([$annText1, $annText2], fn ($t) => $t !== ''));

    // Only allow http/https links — block javascript:/data: and other schemes.
    $annLinkUrl = trim($ws['announcement_link_url'] ?? '');
    if ($annLinkUrl !== '' && ! preg_match('#^https?://#i', $annLinkUrl)) {
        $annLinkUrl = '';
    }
    $annLinkLbl = trim($ws['announcement_link_label'] ?? '');

    $annBg    = trim($ws['announcement_bg_color'] ?? '')   ?: '#C9A227';
    $annFg    = trim($ws['announcement_text_color'] ?? '') ?: '#064E2E';
    $annSpeed = $ws['announcement_speed'] ?? 'normal';
    $annDur   = ['slow' => 45, 'normal' => 30, 'fast' => 18][$annSpeed] ?? 30;
@endphp

@if($annEnabled && count($annSegments) > 0)
@once
<style>
    @keyframes ms-marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
    .ms-marquee-bar   { overflow: hidden; }
    .ms-marquee-track {
        display: inline-flex; white-space: nowrap; will-change: transform;
        animation: ms-marquee var(--ms-marquee-dur, 30s) linear infinite;
    }
    .ms-marquee-bar:hover .ms-marquee-track { animation-play-state: paused; }
    .ms-marquee-seg { padding: 0 1.25rem; }
    @media (prefers-reduced-motion: reduce) {
        .ms-marquee-track { animation: none; transform: none; }
    }
</style>
@endonce

@php
    // Build one run of segments separated by a spice diamond, then duplicate
    // the whole run so the -50% keyframe loops seamlessly.
    $annRun = collect($annSegments)
        ->map(fn ($s) => '<span class="ms-marquee-seg">✦ ' . e($s) . '</span>')
        ->implode('');
    if ($annLinkLbl !== '' && $annLinkUrl !== '') {
        $annRun .= '<span class="ms-marquee-seg" style="text-decoration:underline;font-weight:700;">✦ ' . e($annLinkLbl) . '</span>';
    }
@endphp

<div class="ms-marquee-bar text-sm font-semibold" style="background: {{ $annBg }}; color: {{ $annFg }};">
    @if($annLinkUrl !== '')
    <a href="{{ $annLinkUrl }}" class="block py-2 hover:opacity-90 transition-opacity" style="color: inherit;">
    @else
    <div class="py-2">
    @endif
        <span class="ms-marquee-track" style="--ms-marquee-dur: {{ $annDur }}s;">
            {!! $annRun !!}{!! $annRun !!}
        </span>
    @if($annLinkUrl !== '')
    </a>
    @else
    </div>
    @endif
</div>
@endif
