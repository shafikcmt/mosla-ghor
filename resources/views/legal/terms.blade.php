@extends('legal.layout')
@section('title', 'Terms of Service')
@section('meta_description', $siteName.' ওয়েবসাইট ও অর্ডারের শর্তাবলি — Terms of Service')
@section('head')
    <link rel="canonical" href="{{ route('legal.terms') }}">
@endsection
@section('legal_title', 'শর্তাবলি')
@section('legal_title_en', 'Terms of Service')

@section('legal_body')
<section>
    <p>{{ $siteName }}-এর ওয়েবসাইট, Facebook পেজ, Messenger বা WhatsApp ব্যবহার করে অর্ডার বা যোগাযোগ করলে আপনি নিচের শর্তগুলোতে সম্মত হচ্ছেন।</p>
    <p class="en">By ordering from or contacting {{ $siteName }} through this website, our Facebook Page, Messenger or WhatsApp, you agree to these terms.</p>
</section>

<section>
    <h2>১. পণ্য ও দাম / Products and prices</h2>
    <ul>
        <li>পণ্যের ছবি ও বিবরণ যথাসম্ভব সঠিক রাখা হয়; প্রাকৃতিক পণ্য হওয়ায় রং বা আকারে সামান্য পার্থক্য হতে পারে।
            <span class="en">We keep photos and descriptions accurate; natural products can vary slightly in colour or size.</span></li>
        <li>দাম ও স্টক ওয়েবসাইটে দেখানো অনুযায়ী এবং যেকোনো সময় পরিবর্তন হতে পারে। অর্ডার কনফার্ম হওয়ার সময়ের দামই প্রযোজ্য।
            <span class="en">Prices and stock are as shown on the website and may change; the price at order confirmation applies.</span></li>
        <li>পাইকারি দাম পরিমাণ অনুযায়ী আলাদা হতে পারে এবং quote দেওয়ার পর নিশ্চিত হয়।
            <span class="en">Wholesale prices depend on quantity and are confirmed by quotation.</span></li>
    </ul>
</section>

<section>
    <h2>২. অর্ডার ও পেমেন্ট / Orders and payment</h2>
    <ul>
        <li>সঠিক নাম, ফোন নম্বর ও ঠিকানা দেওয়া আপনার দায়িত্ব। ভুল তথ্যের কারণে ডেলিভারি দেরি বা বাতিল হতে পারে।
            <span class="en">You are responsible for giving a correct name, phone and address; wrong details can delay or cancel delivery.</span></li>
        <li>সন্দেহজনক বা অসম্পূর্ণ অর্ডার আমরা বাতিল করতে পারি; অগ্রিম পেমেন্ট থাকলে তা ফেরত দেওয়া হবে।
            <span class="en">We may cancel suspicious or incomplete orders; any advance payment is refunded.</span></li>
    </ul>
</section>

<section>
    <h2>৩. ডেলিভারি / Delivery</h2>
    <p>ডেলিভারির সময় ও চার্জ চেকআউটে দেখানো হয়। কুরিয়ারের কারণে অনিবার্য দেরি হতে পারে।</p>
    <p class="en">Delivery time and charge are shown at checkout. Courier delays can happen.</p>
</section>

<section>
    <h2>৪. রিটার্ন ও রিফান্ড / Returns and refunds</h2>
    <p>ভুল, নষ্ট বা ক্ষতিগ্রস্ত পণ্য পেলে ডেলিভারির সময় বা ২৪ ঘণ্টার মধ্যে ছবিসহ জানান — আমরা পণ্য বদলে দেব বা টাকা ফেরত দেব। খাদ্যপণ্য হওয়ায় খোলা বা ব্যবহৃত পণ্য সাধারণত ফেরত নেওয়া হয় না।</p>
    <p class="en">If you receive a wrong or damaged item, tell us with photos at delivery or within 24 hours and we will replace or refund it. As these are food items, opened or used products are normally not returned.</p>
</section>

<section>
    <h2>৫. স্বয়ংক্রিয় উত্তর / Automated replies</h2>
    <p>আমাদের Facebook কমেন্ট ও মেসেজের কিছু উত্তর স্বয়ংক্রিয়ভাবে (AI সহায়তায়) দেওয়া হয়। দাম বা অর্ডারের চূড়ান্ত তথ্য ওয়েবসাইট ও অর্ডার কনফার্মেশন অনুযায়ী প্রযোজ্য; কোনো ভুল হলে আমাদের টিম সংশোধন করবে।</p>
    <p class="en">Some replies to Facebook comments and messages are automated (AI-assisted). Final prices and order details are those on the website and in your order confirmation; our team corrects any mistakes.</p>
</section>

<section>
    <h2>৬. গোপনীয়তা / Privacy</h2>
    <p>আপনার তথ্য কীভাবে ব্যবহার হয় তা <a href="{{ route('legal.privacy') }}">গোপনীয়তা নীতি</a>-তে আছে। <span class="en">See our <a href="{{ route('legal.privacy') }}">Privacy Policy</a>.</span></p>
</section>

<section>
    <h2>৭. পরিবর্তন / Changes</h2>
    <p>এই শর্তাবলি পরিবর্তন হলে নতুন তারিখসহ এই পেজে প্রকাশ করা হবে। <span class="en">Changes are published on this page with a new date.</span></p>
</section>
@endsection
