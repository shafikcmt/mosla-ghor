{{--
    Centered section heading: gold eyebrow (with side rules) + serif title + subtitle.
    Reused across the storefront homepage sections (products, combos, reviews, why-us…).

    Props:
      eyebrow  — small uppercase gold label (optional)
      title    — serif Bengali heading (optional)
      subtitle — muted supporting line (optional)
      dark     — true on dark/green backgrounds (renders a cream title)
      margin   — wrapper bottom margin utility (default mb-10)

    Any default slot content renders below the subtitle (e.g. mode-toggle tabs).
--}}
@props([
    'eyebrow'  => null,
    'title'    => null,
    'subtitle' => null,
    'dark'     => false,
    'margin'   => 'mb-10',
])
<div class="text-center {{ $margin }}">
    @if($eyebrow)
    <div class="flex items-center justify-center gap-4 mb-3">
        <div class="h-px w-14 bg-[#c9a227] opacity-50"></div>
        <span class="text-[#c9a227] text-xs tracking-[.3em] uppercase font-semibold">{{ $eyebrow }}</span>
        <div class="h-px w-14 bg-[#c9a227] opacity-50"></div>
    </div>
    @endif

    @if($title)
    <h2 class="font-serif-bn {{ $dark ? 'text-[#fef9ee]' : 'text-[#14532d]' }} text-3xl md:text-4xl font-bold">{{ $title }}</h2>
    @endif

    @if($subtitle)
    <p class="text-gray-400 text-sm mt-2 max-w-md mx-auto leading-relaxed">{{ $subtitle }}</p>
    @endif

    {{ $slot }}
</div>
