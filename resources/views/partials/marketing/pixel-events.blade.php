{{-- Meta Pixel page events — include just before </body> on storefront shells. --}}
@if(\App\Support\MetaPixel::active())
@php
    if (isset($order) && $order instanceof \App\Models\Order) {
        \App\Support\MetaPixel::registerItems($order->items);
    }
    \App\Support\MetaPixel::releaseNextPage(); // Lead / CompleteRegistration queued on the previous POST
    $mpConfig = \App\Support\MetaPixel::clientConfig();
    $mpConfig['queue'] = \App\Support\MetaPixel::events();
@endphp
<script>window.MoslaPixelConfig = @json($mpConfig);</script>
<script src="{{ asset('js/mosla-pixel.js') }}?v=20260928" defer></script>
@foreach(\App\Support\MetaPixel::platformIds() as $mpId)
<noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ $mpId }}&amp;ev=PageView&amp;noscript=1"></noscript>
@endforeach
@endif
