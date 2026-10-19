<section class="hm-container hm-slider" data-hero-slider aria-roledescription="carousel" aria-label="প্রধান অফার">
    <div class="hm-slides">
        @foreach($heroSlides as $slide)
        @if($slide->isBanner())
        {{-- Picture banner: text lives in the image; the whole slide is one link. --}}
        @php
            $bannerLink = \App\Support\AnnouncementUrl::isValid($slide->link_url ?? '') ? $slide->link_url : null;
            $bannerTag = $bannerLink ? 'a' : 'div';
            $mobileSrc = $slide->mobile_image_path ? \App\Support\ProductMedia::url($slide->mobile_image_path) : null;
        @endphp
        <{{ $bannerTag }} class="hm-slide hm-slide-banner {{ $mobileSrc ? 'has-mobile' : '' }} {{ $loop->first ? 'is-current' : '' }}" data-slide @unless($bannerLink) role="group" @endunless aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $loop->count }}: {{ $slide->title }}" @if($bannerLink) href="{{ $bannerLink }}" @endif @if(!$loop->first) inert aria-hidden="true" @endif>
            @if($loop->first)<h1 id="home-title" class="sr-only">{{ $slide->title }}</h1>@endif
            <picture>
                @if($mobileSrc)<source media="(max-width: 767px)" srcset="{{ $mobileSrc }}">@endif
                <img src="{{ \App\Support\ProductMedia::url($slide->image_path) }}" alt="{{ $slide->title }}" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif decoding="async">
            </picture>
        </{{ $bannerTag }}>
        @else
        <div class="hm-hero hm-slide {{ $loop->first ? 'is-current' : '' }}" data-slide role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $loop->count }}" @if(!$loop->first) inert aria-hidden="true" @endif>
            <div class="hm-hero-copy">
                @if($slide->eyebrow)<p class="hm-campaign">{{ $slide->eyebrow }}</p>@endif
                @if($loop->first)<h1 id="home-title">{{ $slide->title }}</h1>@else<h2 class="hm-slide-title">{{ $slide->title }}</h2>@endif
                @if($slide->subtitle)<p class="hm-hero-description">{{ $slide->subtitle }}</p>@endif
                <div class="hm-actions">
                    @foreach(['primary', 'secondary'] as $button)
                        @if(filled($slide->{$button.'_label'}) && \App\Support\AnnouncementUrl::isValid($slide->{$button.'_url'} ?? ''))
                        <a class="hm-button {{ $button === 'secondary' ? 'hm-button-outline' : '' }}" href="{{ $slide->{$button.'_url'} }}">{{ $slide->{$button.'_label'} }}</a>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="hm-hero-display hm-hero-banner">
                <img class="product-artwork" src="{{ \App\Support\ProductMedia::url($slide->image_path) }}" alt="{{ $slide->title }}" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.svg') }}'">
            </div>
        </div>
        @endif
        @endforeach
    </div>
    @if($heroSlides->count() > 1)
    <div class="hm-slider-controls" data-slider-controls hidden>
        <button type="button" data-prev aria-label="আগের স্লাইড">‹</button>
        <div class="hm-slider-dots" role="group" aria-label="স্লাইড নির্বাচন">
            @foreach($heroSlides as $slide)
            <button type="button" data-dot="{{ $loop->index }}" aria-label="স্লাইড {{ $loop->iteration }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"><span></span></button>
            @endforeach
        </div>
        <button type="button" data-next aria-label="পরের স্লাইড">›</button>
        <button type="button" data-pause aria-label="স্বয়ংক্রিয় স্লাইড বন্ধ করুন">Ⅱ</button>
    </div>
    @endif
</section>
<script src="{{ asset('js/home-slider.js') }}" defer></script>
