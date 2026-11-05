@extends('legal.layout')
@section('title', 'Delivery Policy')
@section('meta_description', $siteName.' — ডেলিভারি, কুরিয়ার ও পেমেন্ট সংক্রান্ত তথ্য')
@section('head')
    <link rel="canonical" href="{{ route('legal.delivery') }}">
@endsection
@section('legal_title', 'ডেলিভারি নীতি')
@section('legal_title_en', 'Delivery Policy')

@section('legal_body')
<section>
    <h2>ডেলিভারি / Delivery</h2>
    <ul>
        <li>সারা বাংলাদেশে কুরিয়ারের মাধ্যমে ডেলিভারি দেওয়া হয়। ডেলিভারির সময় ও চার্জ চেকআউটে ঠিকানা অনুযায়ী দেখানো হয়।
            <span class="en">We deliver across Bangladesh by courier. Delivery time and charge are shown at checkout for your address.</span></li>
        <li>অর্ডার কনফার্ম হলে আমরা ফোন বা WhatsApp-এ জানাই; কুরিয়ারে দেওয়ার পর ট্র্যাকিং আপডেট পাবেন।
            <span class="en">We confirm by phone or WhatsApp and share tracking once the parcel is with the courier.</span></li>
        <li>ভুল ঠিকানা বা ফোন বন্ধ থাকলে ডেলিভারি দেরি বা বাতিল হতে পারে। কুরিয়ারের কারণে অনিবার্য দেরি হতে পারে।
            <span class="en">A wrong address or unreachable phone can delay or cancel delivery; courier delays are sometimes unavoidable.</span></li>
    </ul>
</section>

<section>
    <h2>পেমেন্ট / Payment</h2>
    <ul>
        <li>ক্যাশ অন ডেলিভারি, bKash, Nagad, Rocket বা ব্যাংক ট্রান্সফার — চেকআউটে যে মাধ্যমগুলো দেখানো হয় সেগুলো ব্যবহার করা যায়।
            <span class="en">Cash on delivery, bKash, Nagad, Rocket or bank transfer — whichever are offered at checkout.</span></li>
        <li>পণ্য হাতে পেয়ে দেখে নিন; সমস্যা থাকলে <a href="{{ route('legal.refund') }}">রিটার্ন ও রিফান্ড নীতি</a> দেখুন।
            <span class="en">Check your parcel on receipt; for problems see our Return &amp; Refund Policy.</span></li>
    </ul>
</section>

<section>
    <h2>অর্ডার ট্র্যাক / Track your order</h2>
    <p><a href="{{ route('track-order') }}">অর্ডার ট্র্যাক করুন</a> — অর্ডার নম্বর ও মোবাইল নম্বর দিয়ে।</p>
</section>
@endsection
