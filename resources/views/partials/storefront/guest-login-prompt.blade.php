{{-- Shown instead of a form when the admin has turned the guest option off. Params: $feature (checkout|enquiry|review), optional $back. --}}
@php
    $gpBack = $back ?? request()->getRequestUri();
    $gpReg  = \App\Support\AuthSettings::customerRegistrationEnabled();
@endphp
<div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-center" data-guest-login-prompt="{{ $feature }}">
    <p class="text-sm font-semibold text-amber-900">{{ \App\Support\AuthSettings::GUEST_FEATURES[$feature] ?? 'লগইন করুন' }}</p>
    <div class="flex flex-wrap justify-center gap-2 mt-2">
        <a href="{{ \App\Support\AuthSettings::loginUrl($gpBack) }}"
           class="bg-[#14532d] hover:bg-[#166534] text-white font-semibold text-sm px-5 py-2 rounded-lg transition">লগইন</a>
        @if($gpReg)
        <a href="{{ \App\Support\AuthSettings::registerUrl($gpBack) }}"
           class="border border-[#14532d] text-[#14532d] font-semibold text-sm px-5 py-2 rounded-lg hover:bg-green-50 transition">রেজিস্টার</a>
        @endif
    </div>
</div>
