@extends('storefront.layout')
@section('title', 'FAQ - MoslaMart')

@section('head')
    <meta name="description" content="MoslaMart খুচরা, পাইকারি, ডেলিভারি ও পেমেন্ট সম্পর্কিত সাধারণ প্রশ্নোত্তর">
    <link rel="canonical" href="{{ url('/faq') }}">
@endsection

@section('content')

{{-- Breadcrumb --}}
<nav class="text-xs text-gray-400 mb-5 flex items-center gap-1.5">
    <a href="/" class="hover:text-[#14532d]">হোম</a>
    <span>/</span>
    <span class="text-gray-700 font-medium">FAQ</span>
</nav>

{{-- Header --}}
<div class="text-center mb-8">
    <div class="flex items-center justify-center gap-4 mb-3">
        <div class="h-px w-12 bg-[#c9a227] opacity-40"></div>
        <span class="text-[#c9a227] text-xs tracking-[.3em] uppercase font-semibold">FAQ</span>
        <div class="h-px w-12 bg-[#c9a227] opacity-40"></div>
    </div>
    <h1 class="font-serif-bn text-[#14532d] text-3xl md:text-4xl font-bold">সাধারণ প্রশ্নোত্তর</h1>
    <p class="text-gray-500 text-sm mt-2 max-w-xl mx-auto">খুচরা, পাইকারি, ডেলিভারি ও পেমেন্ট সম্পর্কিত সাধারণ প্রশ্নের উত্তর এখানে পাবেন।</p>
</div>

@php
    // A few wholesale/app extras appended after the admin-managed FAQs.
    $extraFaqs = [
        ['পাইকারি অর্ডার কীভাবে করবো?', 'হোমপেজের পাইকারি ট্যাব থেকে পণ্য বেছে enquiry পাঠান, অথবা "পাইকারি অর্ডার তৈরি করুন" থেকে একাধিক পণ্য একসাথে যোগ করে দর জানতে চান। MoslaMart টিম quote পাঠাবে।'],
        ['Wholesale price কেন দেখানো হয় না?', 'পাইকারি দর quantity অনুযায়ী পরিবর্তন হতে পারে, তাই enquiry করার পর quote জানানো হয়।'],
        ['MoslaMart app download করতে হবে কি?', 'না, কোনো app download করতে হবে না — website visit করেই সব ব্যবহার করতে পারবেন। চাইলে মোবাইল Home Screen-এ shortcut যোগ করে app-এর মতো ব্যবহার করতে পারবেন।'],
        ['Home Screen-এ কীভাবে যোগ করবো?', 'নিচের "Home Screen-এ কীভাবে যোগ করবো?" অংশে Android ও iPhone-এর সহজ ধাপগুলো দেওয়া আছে।'],
    ];
@endphp

<div class="max-w-3xl mx-auto space-y-3">
    @foreach($faqs as $i => $faq)
        @include('partials.faq-item', ['idx' => $i, 'q' => $faq->question, 'a' => $faq->answer])
    @endforeach
    @foreach($extraFaqs as $j => $ex)
        @include('partials.faq-item', ['idx' => 'x'.$j, 'q' => $ex[0], 'a' => $ex[1]])
    @endforeach
</div>

{{-- App install / Home Screen guide --}}
<div id="app-install" class="max-w-3xl mx-auto mt-10 bg-white rounded-2xl border border-amber-100 shadow-sm p-6 scroll-mt-24">
    <h2 class="font-serif-bn text-[#14532d] text-xl font-bold mb-1">Home Screen-এ কীভাবে যোগ করবো?</h2>
    <p class="text-gray-500 text-sm mb-4">MoslaMart কে app-এর মতো ব্যবহার করতে মোবাইলের Home Screen-এ shortcut যোগ করুন:</p>
    <div class="grid sm:grid-cols-2 gap-4 text-sm">
        <div class="bg-amber-50/60 rounded-xl p-4">
            <h3 class="font-bold text-[#14532d] mb-1.5">Android (Chrome)</h3>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>উপরে ডান কোণে ⋮ মেনুতে ট্যাপ করুন</li>
                <li>"Add to Home screen" নির্বাচন করুন</li>
                <li>"Add" চাপুন — হয়ে গেল!</li>
            </ol>
        </div>
        <div class="bg-amber-50/60 rounded-xl p-4">
            <h3 class="font-bold text-[#14532d] mb-1.5">iPhone (Safari)</h3>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>নিচে Share আইকনে ট্যাপ করুন</li>
                <li>"Add to Home Screen" নির্বাচন করুন</li>
                <li>"Add" চাপুন — হয়ে গেল!</li>
            </ol>
        </div>
    </div>
</div>

{{-- Bottom CTA --}}
<div class="max-w-3xl mx-auto mt-10 text-center bg-[#0f3d22] rounded-2xl p-8">
    <h2 class="font-serif-bn text-[#c9a227] text-2xl font-bold">আরও প্রশ্ন আছে?</h2>
    <p class="text-green-200 text-sm mt-1.5 mb-5 max-w-md mx-auto">আমাদের টিম সাহায্য করতে প্রস্তুত। আপনার তথ্য, quote ও payment record নিরাপদ রাখতে MoslaMart enquiry/chat process ব্যবহার করুন।</p>
    <div class="flex flex-wrap gap-3 justify-center">
        <a href="/#contact" class="btn-gold text-[#0f3d22] font-bold text-sm px-7 py-3 rounded-full shadow-lg">যোগাযোগ করুন</a>
        <a href="/#products" class="border border-[#c9a227] text-[#c9a227] hover:bg-[#c9a227] hover:text-[#0f3d22] font-semibold text-sm px-7 py-3 rounded-full transition-colors">পণ্য দেখুন</a>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function toggleFaq(i) {
        const body = document.getElementById('faq-body-' + i);
        const icon = document.getElementById('faq-icon-' + i);
        if (!body) return;
        const open = !body.classList.contains('hidden');
        body.classList.toggle('hidden', open);
        if (icon) icon.style.transform = open ? '' : 'rotate(180deg)';
    }
</script>
@endsection
