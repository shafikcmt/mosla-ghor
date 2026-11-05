@extends('legal.layout')
@section('title', 'আমাদের সম্পর্কে ও যোগাযোগ')
@section('meta_description', $siteName.' — খাঁটি, ফ্রেশ ও বাছাইকৃত মশলার অনলাইন দোকান। আমাদের সম্পর্কে ও যোগাযোগ')
@section('head')
    <link rel="canonical" href="{{ route('legal.about') }}">
@endsection
@section('legal_title', 'আমাদের সম্পর্কে')
@section('legal_title_en', 'About Us')

@section('legal_body')
<section>
    <p>{{ $siteName }} খাঁটি, ফ্রেশ ও বাছাইকৃত মশলা খুচরা ও পাইকারি দামে সরাসরি আপনার কাছে পৌঁছে দেয়। প্রতিটি পণ্য বাছাই ও প্যাক করা হয় যত্ন নিয়ে, যাতে রান্নায় আসল স্বাদ পান।</p>
    <p class="en">{{ $siteName }} delivers pure, fresh, hand-selected spices at retail and wholesale prices across Bangladesh.</p>
</section>

<section>
    <h2>কেন আমাদের থেকে কিনবেন / Why shop with us</h2>
    <ul>
        <li>খুচরা ও পাইকারি — দুই ধরনের অর্ডারই নেওয়া হয়। <span class="en">Retail and wholesale orders.</span></li>
        <li>ছোট প্যাক (২৫ গ্রাম থেকে) ও কেজি দরে কেনার সুযোগ। <span class="en">Small packs from 25 g up to kilos.</span></li>
        <li>ফোন, WhatsApp বা ওয়েবসাইটে অর্ডার; কুরিয়ারে সারা দেশে ডেলিভারি। <span class="en">Order by phone, WhatsApp or web; nationwide courier.</span></li>
        <li>সমস্যা হলে <a href="{{ route('legal.refund') }}">সহজ রিটার্ন নীতি</a>। <span class="en">Simple return policy.</span></li>
    </ul>
</section>
@endsection
