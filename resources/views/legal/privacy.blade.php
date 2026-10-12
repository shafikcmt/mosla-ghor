@extends('legal.layout')
@section('title', 'Privacy Policy')
@section('meta_description', $siteName.' কীভাবে আপনার তথ্য সংগ্রহ, ব্যবহার ও সুরক্ষিত রাখে — Privacy Policy')
@section('head')
    <link rel="canonical" href="{{ route('legal.privacy') }}">
@endsection
@section('legal_title', 'গোপনীয়তা নীতি')
@section('legal_title_en', 'Privacy Policy')

@section('legal_body')
<section>
    <p>{{ $siteName }} (“আমরা”) এই ওয়েবসাইট, আমাদের Facebook পেজ, Messenger ও WhatsApp-এর মাধ্যমে আপনার দেওয়া তথ্য কীভাবে সংগ্রহ, ব্যবহার ও সুরক্ষিত রাখি — এই নীতিতে তা বলা হয়েছে।</p>
    <p class="en">This policy explains how {{ $siteName }} collects, uses and protects information on this website, our Facebook Page, Messenger and WhatsApp.</p>
</section>

<section>
    <h2>১. আমরা যে তথ্য সংগ্রহ করি / Information we collect</h2>
    <ul>
        <li><b>অর্ডার ও অ্যাকাউন্ট:</b> নাম, মোবাইল নম্বর, ইমেইল (যদি দেন), ডেলিভারি ঠিকানা, অর্ডারের পণ্য ও পেমেন্টের তথ্য (যেমন পেমেন্টের স্ক্রিনশট)।
            <span class="en">Name, phone, email, delivery address, order and payment details.</span></li>
        <li><b>Facebook / Messenger / WhatsApp:</b> আপনি আমাদের পেজে কমেন্ট বা মেসেজ করলে Meta আমাদের সেই লেখা, আপনার পাবলিক প্রোফাইল নাম ও পেজ-ভিত্তিক আইডি পাঠায়; মেসেজে আপনি নিজে যে ফোন নম্বর, ঠিকানা বা পরিমাণ লেখেন সেটিও।
            <span class="en">When you comment on or message our Page, Meta sends us the text, your public name and a Page-scoped ID, plus anything you write (phone, address, quantity).</span></li>
        <li><b>ওয়েবসাইট ব্যবহার:</b> IP ঠিকানা, ব্রাউজার এবং Meta Pixel-এর কুকি (<code>_fbp</code>, <code>_fbc</code>)।
            <span class="en">IP address, browser and Meta Pixel cookies.</span></li>
    </ul>
</section>

<section>
    <h2>২. তথ্য কীভাবে ব্যবহার করি / How we use it</h2>
    <ul>
        <li>অর্ডার প্রসেস, ডেলিভারি ও অর্ডারের স্ট্যাটাস জানানো।
            <span class="en">To process and deliver orders and tell you their status.</span></li>
        <li>দাম, স্টক ও অর্ডার নিয়ে আপনার প্রশ্নের উত্তর দেওয়া। কমেন্ট ও মেসেজের উত্তর আমাদের স্বয়ংক্রিয় সিস্টেম (AI সহায়তায়) দিতে পারে; প্রয়োজনে আমাদের টিম নিজে উত্তর দেয়।
            <span class="en">To answer questions about prices, stock and orders. Replies may be automated (AI-assisted); our team steps in when needed.</span></li>
        <li>আপনি ফোন নম্বর দিলে অর্ডার বা যোগাযোগের জন্য সেটি “লিড” হিসেবে সংরক্ষণ।
            <span class="en">To save a follow-up lead when you share your phone number.</span></li>
        <li>বিজ্ঞাপনের ফলাফল মাপা। Meta Pixel ও Conversions API-তে ফোন/ইমেইল শুধু <b>SHA-256 হ্যাশ</b> করে পাঠানো হয়।
            <span class="en">To measure ads. Phone and email are sent to Meta only as SHA-256 hashes.</span></li>
    </ul>
    <p>আমরা আপনার তথ্য <b>বিক্রি করি না</b>। <span class="en">We do not sell your information.</span></p>
</section>

<section>
    <h2>৩. কার সাথে তথ্য শেয়ার হয় / Who we share it with</h2>
    <ul>
        <li><b>কুরিয়ার সার্ভিস</b> — ডেলিভারির জন্য নাম, ফোন ও ঠিকানা। <span class="en">Courier partners, for delivery.</span></li>
        <li><b>Meta Platforms</b> (Facebook, Messenger, WhatsApp) — মেসেজ আদান-প্রদান ও বিজ্ঞাপন পরিমাপ। <span class="en">Messaging and ad measurement.</span></li>
        <li><b>AI সেবা প্রদানকারী</b> (যেমন Anthropic, Google, OpenAI) — শুধু উত্তর তৈরির জন্য কমেন্ট/মেসেজের লেখা প্রসেস হয়। <span class="en">AI providers process comment and message text only to draft replies.</span></li>
        <li>আইনগত প্রয়োজনে সরকারি কর্তৃপক্ষ। <span class="en">Authorities, when the law requires it.</span></li>
    </ul>
</section>

<section>
    <h2>৪. কতদিন রাখি / Retention</h2>
    <p>অর্ডারের রেকর্ড হিসাব ও আইনগত প্রয়োজনে রাখা হয়। Messenger/কমেন্টের কথোপকথন ও লিড প্রয়োজন শেষ হলে, অথবা আপনি মুছতে বললে মুছে ফেলা হয়।</p>
    <p class="en">Order records are kept for accounting and legal needs. Chats, comments and leads are deleted when no longer needed, or when you ask.</p>
</section>

<section>
    <h2>৫. আপনার অধিকার / Your rights</h2>
    <p>আপনি আপনার তথ্য দেখতে, সংশোধন করতে বা মুছে ফেলতে বলতে পারেন — <a href="{{ route('legal.data-deletion') }}">ডেটা মুছে ফেলার নির্দেশনা</a> দেখুন।</p>
    <p class="en">You can ask to see, correct or delete your data — see <a href="{{ route('legal.data-deletion') }}">Data Deletion</a>.</p>
</section>

<section>
    <h2>৬. নিরাপত্তা / Security</h2>
    <p>ওয়েবসাইট HTTPS-এ চলে, অ্যাক্সেস টোকেন ও গোপন কী এনক্রিপ্ট করে রাখা হয়, এবং শুধু অনুমোদিত টিম সদস্যরা তথ্য দেখতে পারেন।</p>
    <p class="en">The site runs on HTTPS, access tokens and secret keys are stored encrypted, and only authorised staff can see your data.</p>
</section>

<section>
    <h2>৭. পরিবর্তন / Changes</h2>
    <p>এই নীতি পরিবর্তন হলে নতুন তারিখসহ এই পেজে প্রকাশ করা হবে। <span class="en">Changes are published on this page with a new date.</span></p>
</section>
@endsection
