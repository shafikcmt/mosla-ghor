<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ \App\Models\WebsiteSetting::siteName() }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
@php
    $mailSite = \App\Models\WebsiteSetting::siteName();
    $mailWa   = \App\Models\WebsiteSetting::get('whatsapp_number');
    $mailHost = parse_url(config('app.url'), PHP_URL_HOST) ?: config('app.url');
@endphp
**{{ $mailSite }}** — পাইকারি ও খুচরা মসলা, ড্রাই ফ্রুটস ও বাদাম<br>
@if($mailWa)WhatsApp / কল: {{ $mailWa }} · @endif[{{ $mailHost }}]({{ config('app.url') }})<br>
© {{ date('Y') }} {{ $mailSite }}। সর্বস্বত্ব সংরক্ষিত।
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
