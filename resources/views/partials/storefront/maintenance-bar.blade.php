{{-- Maintenance: admin reminder bar (admins only) + customer notice banner (banner mode). --}}
@php
    use App\Support\Maintenance;
    $mnOn    = Maintenance::enabled();
    $mnAdmin = $mnOn && (bool) auth()->user()?->is_admin;
    $mnBanner = Maintenance::isBanner();
@endphp
@if($mnOn && ($mnAdmin || $mnBanner))
@once
<style>
.ms-mn-admin{background:#b91c1c;color:#fff;font-size:13px;line-height:1.5}
.ms-mn-admin-inner{max-width:1280px;margin:auto;padding:6px 16px;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:4px 12px;text-align:center}
.ms-mn-admin a{color:#fff;font-weight:700;text-decoration:underline}
.ms-mn-admin small{opacity:.9}
.ms-mn-banner{background:#fff7ed;color:#7c2d12;border-bottom:1px solid #fed7aa;font-size:14px;line-height:1.6}
.ms-mn-banner-inner{max-width:1280px;margin:auto;padding:8px 48px 8px 16px;position:relative;text-align:center}
.ms-mn-banner strong{margin-right:6px}
.ms-mn-close{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:40px;height:40px;border:0;background:transparent;font-size:22px;color:#7c2d12;cursor:pointer}
</style>
@endonce
@if($mnAdmin)
<div class="ms-mn-admin" role="status">
    <div class="ms-mn-admin-inner">
        <span>🛠️ Maintenance চালু আছে ({{ Maintenance::mode() === 'full' ? 'পুরো সাইট বন্ধ' : 'নোটিশ ব্যানার' }}{{ Maintenance::expired() ? ' · শেষ সময় পেরিয়ে গেছে' : '' }})</span>
        <a href="{{ route('admin.website-settings.index') }}#maintenance">বন্ধ করুন</a>
        <small>টেস্ট অর্ডার — মেইনটেন্যান্স চলাকালীন অ্যাডমিনের দেওয়া অর্ডার আসল অর্ডার হবে (স্টক কমবে)।</small>
    </div>
</div>
@endif
@if($mnBanner)
@php $mnKey = 'ms_mn_dismissed_'.substr(md5(Maintenance::title().Maintenance::message()), 0, 10); @endphp
<div class="ms-mn-banner" data-mn-banner="{{ $mnKey }}" role="region" aria-label="সাইট নোটিশ">
    <div class="ms-mn-banner-inner">
        <strong>{{ Maintenance::title() }}</strong><span>{{ Maintenance::message() }}</span>
        @if(Maintenance::blocksOrders())<span> · অর্ডার সাময়িকভাবে বন্ধ আছে।</span>@endif
        <button type="button" class="ms-mn-close" aria-label="নোটিশ বন্ধ করুন" data-mn-close>×</button>
    </div>
</div>
<script>
(() => {
    const bar = document.currentScript.previousElementSibling;
    const key = bar.dataset.mnBanner;
    try { if (sessionStorage.getItem(key) === '1') { bar.remove(); return; } } catch (e) {}
    bar.querySelector('[data-mn-close]').addEventListener('click', () => {
        bar.remove();
        try { sessionStorage.setItem(key, '1'); } catch (e) {}
    });
})();
</script>
@endif
@endif
