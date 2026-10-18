{{-- Trust strip under the hero: what a first-time buyer wants to know at a glance. --}}
@php
    $trustWa = trim((string) \App\Models\WebsiteSetting::get('whatsapp_number'));
    $trustItems = array_values(array_filter([
        ['M3 7h11v9H3zM14 10h4l3 3v3h-7M7.5 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm10 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3z', 'হোম ডেলিভারি', 'ঘরে বসে অর্ডার, দরজায় পৌঁছে যাবে'],
        $paymentSettings->cash_on_delivery_enabled
            ? ['M3 7h18v10H3zM12 15a3 3 0 100-6 3 3 0 000 6zM6 10v4M18 10v4', 'ক্যাশ অন ডেলিভারি', 'পণ্য হাতে পেয়ে টাকা দিন']
            : ['M3 7h18v10H3zM3 11h18', 'সহজ পেমেন্ট', 'bKash / Nagad দিয়ে পেমেন্ট'],
        ['M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6zM9 12l2 2 4-4', 'বাছাইকৃত খাঁটি মসলা', 'মান যাচাই করে প্যাক করা'],
        $trustWa !== ''
            ? ['M12 3a9 9 0 00-7.8 13.5L3 21l4.6-1.2A9 9 0 1012 3zM8.5 9.5c.5 2 2 3.5 4 4l1.3-1.2 2 .9c-.2 1.3-1.4 2.1-2.8 1.8A7 7 0 017 10c-.3-1.4.5-2.6 1.8-2.8l.9 2z', 'WhatsApp সাপোর্ট', 'প্রশ্ন থাকলে সরাসরি মেসেজ দিন']
            : ['M9 11l3 3 8-8M20 12v7a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2h9', 'অর্ডার ট্র্যাকিং', 'অর্ডার কোথায় আছে দেখুন'],
    ]));
@endphp
<section class="hm-container hm-trust" aria-label="আমাদের সুবিধা">
    @foreach($trustItems as [$icon, $title, $text])
    <div class="hm-trust-item">
        <span class="hm-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg></span>
        <span><strong>{{ $title }}</strong><small>{{ $text }}</small></span>
    </div>
    @endforeach
</section>
