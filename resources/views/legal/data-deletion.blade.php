@extends('legal.layout')
@section('title', 'Data Deletion')
@section('meta_description', $siteName.' — আপনার তথ্য মুছে ফেলার নির্দেশনা (User Data Deletion Instructions)')
@section('head')
    <link rel="canonical" href="{{ route('legal.data-deletion') }}">
@endsection
@section('legal_title', 'ডেটা মুছে ফেলার নির্দেশনা')
@section('legal_title_en', 'User Data Deletion Instructions')

@section('legal_body')
<section>
    <p>আপনি চাইলে {{ $siteName }}-এর কাছে থাকা আপনার তথ্য — ওয়েবসাইট অ্যাকাউন্ট, Facebook কমেন্ট, Messenger/WhatsApp কথোপকথন ও লিড — মুছে ফেলতে বলতে পারেন।</p>
    <p class="en">You can ask {{ $siteName }} to delete the data we hold about you — website account, Facebook comments, Messenger/WhatsApp conversations and leads.</p>
</section>

<section>
    <h2>কীভাবে অনুরোধ করবেন / How to request deletion</h2>
    <ol>
        <li>আমাদের Facebook পেজে Messenger-এ, অথবা নিচের WhatsApp/ফোনে <b>“Delete my data”</b> লিখে পাঠান।
            <span class="en">Send <b>“Delete my data”</b> to our Facebook Page on Messenger, or to the WhatsApp/phone number below.</span></li>
        <li>অর্ডার বা অ্যাকাউন্টের সাথে যুক্ত মোবাইল নম্বরটি জানান, যাতে সঠিক তথ্য খুঁজে পাই।
            <span class="en">Include the mobile number used for your orders or account so we can find your data.</span></li>
        <li>আমরা <b>৩০ দিনের মধ্যে</b> আপনার তথ্য মুছে ফেলে জানিয়ে দেব।
            <span class="en">We delete your data and confirm <b>within 30 days</b>.</span></li>
    </ol>
</section>

<section>
    <h2>Facebook থেকে অ্যাপের অ্যাক্সেস সরানো / Remove the app’s access on Facebook</h2>
    <ol>
        <li>Facebook-এ <b>Settings &amp; privacy → Settings → Apps and websites</b> (অথবা <b>Business integrations</b>) খুলুন।
            <span class="en">On Facebook open <b>Settings &amp; privacy → Settings → Apps and websites</b> (or <b>Business integrations</b>).</span></li>
        <li>“MoslaMart Automation” বেছে <b>Remove</b> চাপুন।
            <span class="en">Select “MoslaMart Automation” and click <b>Remove</b>.</span></li>
        <li>এরপর উপরের ধাপে আমাদের জানান, যাতে আমাদের সিস্টেমে থাকা তথ্যও মুছে ফেলা হয়।
            <span class="en">Then contact us as above so the data in our systems is deleted too.</span></li>
    </ol>
</section>

<section>
    <h2>যা রাখা হতে পারে / What may be kept</h2>
    <p>সম্পন্ন অর্ডারের ইনভয়েস ও হিসাবের রেকর্ড আইন অনুযায়ী নির্দিষ্ট সময় পর্যন্ত রাখতে হতে পারে; সেগুলো অন্য কোনো কাজে ব্যবহার করা হয় না।</p>
    <p class="en">Invoices and accounting records of completed orders may have to be kept for the period the law requires; they are not used for anything else.</p>
</section>
@endsection
