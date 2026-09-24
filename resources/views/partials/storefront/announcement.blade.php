@php
    $ws = $ws ?? \App\Models\WebsiteSetting::allKeyed();
    $annEnabled = ($ws['announcement_enabled'] ?? '0') === '1';
    $annMessage = trim($ws['announcement_text_1'] ?? '');
    $annLinkUrl = trim($ws['announcement_link_url'] ?? '');
    if (! \App\Support\AnnouncementUrl::isValid($annLinkUrl)) {
        $annLinkUrl = '';
    }
    $annLinkLabel = trim($ws['announcement_link_label'] ?? '');
@endphp
@if($annEnabled && $annMessage !== '')
@once
<style>
.ms-announcement{background:#c9a227;color:#064e2e;font-size:13px;font-weight:600;line-height:20px}
.ms-announcement-inner{max-width:1280px;margin:auto;padding:6px 20px;min-height:36px;display:flex;align-items:center;justify-content:center;gap:12px}
.ms-announcement-message{min-width:0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow-wrap:anywhere}
.ms-announcement a{color:inherit;text-decoration:underline;text-underline-offset:3px}
.ms-announcement-link{flex-shrink:0;max-width:40%;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.ms-announcement a:focus-visible{outline:2px solid #064e2e;outline-offset:2px}
.ms-announcement a:hover{text-decoration-thickness:2px}
@media(max-width:767px){.ms-announcement-inner{padding:8px 14px;gap:10px}.ms-announcement-message{-webkit-line-clamp:2}.ms-announcement-link{line-height:28px;min-height:28px}}
</style>
@endonce
<aside class="ms-announcement" aria-label="Announcement">
    <div class="ms-announcement-inner">
        @if($annLinkUrl !== '' && $annLinkLabel === '')
            <a class="ms-announcement-message" href="{{ $annLinkUrl }}">{{ $annMessage }}</a>
        @else
            <p class="ms-announcement-message">{{ $annMessage }}</p>
            @if($annLinkUrl !== '')
                <a class="ms-announcement-link" href="{{ $annLinkUrl }}">{{ $annLinkLabel }}</a>
            @endif
        @endif
    </div>
</aside>
@endif
