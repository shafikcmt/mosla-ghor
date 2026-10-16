{{-- What the code really sends. Keep in sync with MetaPixel / MetaCapi / public/js/mosla-pixel.js. --}}
<div class="overflow-x-auto border border-gray-100 rounded">
    <table class="w-full text-xs">
        <thead class="bg-gray-50 text-gray-600">
            <tr>
                <th class="px-3 py-2 text-left">Event</th>
                <th class="px-3 py-2 text-left">Browser Pixel</th>
                <th class="px-3 py-2 text-left">Conversions API</th>
                <th class="px-3 py-2 text-left">কখন যায়</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-600">
            @foreach([
                ['PageView',            true,  false, 'প্রতিটি পাবলিক পেজ খোলায়'],
                ['ViewContent',         true,  true,  'পণ্যের পেজ খোলায়'],
                ['Search',              true,  false, 'হোমপেজে পণ্য খুঁজে টাইপ থামালে/Enter চাপলে (শুধু ব্রাউজার)'],
                ['AddToWishlist',       true,  true,  'উইশলিস্টে নতুন পণ্য সফলভাবে যোগ হলে'],
                ['AddToCart',           true,  true,  'কার্টে নতুন পণ্য বা আরও প্যাক যোগ করলে'],
                ['InitiateCheckout',    true,  true,  'অর্ডার রিভিউ ধাপে এলে (রিফ্রেশে বারবার নয়)'],
                ['AddPaymentInfo',      true,  false, 'পেমেন্ট ধাপে “অর্ডার দিন” চাপলে, পেমেন্ট মাধ্যমসহ (শুধু ব্রাউজার)'],
                ['Purchase',            true,  true,  'অর্ডার সফলভাবে প্লেস হলে (পেমেন্ট কনফার্ম হওয়ার জন্য অপেক্ষা করে না — COD-ও গণ্য)'],
                ['Lead',                true,  true,  'পাইকারি/কম্বো enquiry জমা হলে; বট লিড শুধু সার্ভার'],
                ['CompleteRegistration', true, true,  'নতুন অ্যাকাউন্ট সফলভাবে তৈরি হলে (চেকআউটে অটো-অ্যাকাউন্টে নয়)'],
                ['Contact',             true,  true,  'WhatsApp বা ফোন লিংকে ক্লিক করলে'],
            ] as [$ev, $browser, $server, $when])
            <tr>
                <td class="px-3 py-2 font-mono text-gray-800">{{ $ev }}</td>
                <td class="px-3 py-2">{{ $browser ? '✔' : '—' }}</td>
                <td class="px-3 py-2">{{ $server ? '✔' : '—' }}</td>
                <td class="px-3 py-2">{{ $when }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p class="text-xs text-gray-400">
    <b>Browser Pixel</b> = ভিজিটরের ব্রাউজার থেকে Meta-তে। <b>Conversions API</b> = আমাদের সার্ভার থেকে সরাসরি Meta-তে (ad-blocker/iPhone-এ মিস হয় না)।
    <b>event ID</b> = একই ঘটনার ব্রাউজার ও সার্ভার কপির একই পরিচয়; এতে Meta ডুপ্লিকেট বাদ দেয়। অ্যাডমিন (লগইন থাকলে) ও কনফিগার করা অফিস IP ট্র্যাক হয় না।
</p>
