@extends('legal.layout')
@section('title', 'Return & Refund Policy')
@section('meta_description', $siteName.' — ভুল, নষ্ট বা ক্ষতিগ্রস্ত পণ্যের রিটার্ন ও রিফান্ড নীতি')
@section('head')
    <link rel="canonical" href="{{ route('legal.refund') }}">
@endsection
@section('legal_title', 'রিটার্ন ও রিফান্ড নীতি')
@section('legal_title_en', 'Return & Refund Policy')

@section('legal_body')
<section>
    <h2>কখন রিটার্ন / বদল পাবেন / When you can return</h2>
    <ul>
        <li>ভুল পণ্য, নষ্ট বা ক্ষতিগ্রস্ত পণ্য পেলে ডেলিভারির সময় বা ২৪ ঘণ্টার মধ্যে ছবি বা ভিডিওসহ আমাদের জানান।
            <span class="en">Received a wrong, spoiled or damaged item? Tell us with a photo or video at delivery or within 24 hours.</span></li>
        <li>যাচাই করে আমরা পণ্য বদলে দেব অথবা টাকা ফেরত দেব।
            <span class="en">After checking, we replace the item or refund the money.</span></li>
    </ul>
</section>

<section>
    <h2>যা ফেরত নেওয়া হয় না / Not returnable</h2>
    <ul>
        <li>খাদ্যপণ্য হওয়ায় খোলা বা ব্যবহৃত পণ্য সাধারণত ফেরত নেওয়া হয় না।
            <span class="en">Opened or used food items are generally not returnable.</span></li>
        <li>পছন্দ না হওয়া বা ২৪ ঘণ্টা পরে জানানো অভিযোগ আমরা বিবেচনা করি কেস-বাই-কেস।
            <span class="en">Change-of-mind or late claims are reviewed case by case.</span></li>
    </ul>
</section>

<section>
    <h2>রিফান্ড কীভাবে হয় / How refunds are made</h2>
    <ul>
        <li>bKash / Nagad / Rocket বা ব্যাংক ট্রান্সফারে যেভাবে পেমেন্ট করেছেন, সাধারণত সেই মাধ্যমেই টাকা ফেরত দেওয়া হয়।
            <span class="en">Refunds normally go back through the method you paid with (bKash / Nagad / Rocket / bank).</span></li>
        <li>অগ্রিম পেমেন্ট থাকা অর্ডার আমরা বাতিল করলে সম্পূর্ণ অগ্রিম ফেরত দেওয়া হয়।
            <span class="en">If we cancel an order that had an advance payment, the advance is refunded in full.</span></li>
    </ul>
</section>

<section>
    <h2>কীভাবে জানাবেন / How to claim</h2>
    <p>নিচের যোগাযোগ মাধ্যমে অর্ডার নম্বর ও ছবিসহ জানান। <span class="en">Contact us below with your order number and photos.</span></p>
</section>
@endsection
