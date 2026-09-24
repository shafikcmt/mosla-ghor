<section id="why-us" class="hm-container hm-section">
    <div class="hm-section-heading"><h2>কেনাকাটা হোক সহজ</h2></div>
    <div class="hm-benefits">
        @foreach([
            ['প্যাক বেছে কেনাকাটা', 'প্যাকের পরিমাণ ও দাম দেখে আপনার প্রয়োজন অনুযায়ী বেছে নিন।'],
            ['খুচরা ও পাইকারি', 'ছোট প্যাক কিনুন অথবা বাল্ক অর্ডারের জন্য কোটেশন নিন।'],
            ['অর্ডারের খোঁজ রাখুন', 'অর্ডার ট্র্যাকিং থেকে আপনার অর্ডারের অবস্থা দেখুন।'],
            ['প্রয়োজনে যোগাযোগ', 'পণ্য বা অর্ডার নিয়ে প্রশ্ন থাকলে আমাদের সাথে যোগাযোগ করুন।'],
        ] as [$title, $description])
        <div><svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m5 12 4 4L19 6"/><path d="M21 12a9 9 0 1 1-9-9"/></svg><h3>{{ $title }}</h3><p>{{ $description }}</p></div>
        @endforeach
    </div>
</section>
