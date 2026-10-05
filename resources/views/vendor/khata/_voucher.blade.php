{{--
    Printable voucher (বিক্রয় / ক্রয় বিবরণ, or a payment receipt) — layout follows the
    shop's paper chalan. Needs $tx (lines, party, vendor loaded) and $balance (party, nullable).
--}}
@php
    $v      = $tx->vendor;
    $tk     = fn ($n) => '৳ '.number_format((float) $n, 0);
    $q3     = fn ($n) => rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.');
    $isTrade = in_array($tx->type, ['sale', 'purchase'], true);
    $title  = ['sale' => 'বিক্রয় বিবরণ', 'purchase' => 'ক্রয় বিবরণ', 'payment_in' => 'টাকা প্রাপ্তির রসিদ', 'payment_out' => 'টাকা পরিশোধের রসিদ', 'expense' => 'খরচের রসিদ', 'stock_in' => 'স্টক যোগ', 'stock_out' => 'স্টক কমানো'][$tx->type] ?? 'রসিদ';
    $terms  = trim((string) $v->khataSetting('terms'));
@endphp
<div class="voucher bg-white text-gray-900 p-5 sm:p-8 rounded-2xl border border-gray-100 shadow-sm">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-2xl font-bold">{{ $v->shop_name }}</p>
            <p class="text-sm mt-1">{{ $v->phone }}@if($v->address) • {{ $v->address }}@endif</p>
        </div>
        @if($v->logo)<img src="{{ asset($v->logo) }}" alt="" class="h-14 w-14 object-contain">@endif
    </div>

    <p class="text-center text-2xl font-bold my-5">{{ $title }}</p>

    <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
        <div class="space-y-1">
            @if($tx->party)
                <p><span class="inline-block w-20 text-gray-600">পার্টি:</span> <b>{{ $tx->party->name }}</b></p>
                @if($tx->party->phone)<p><span class="inline-block w-20 text-gray-600">ফোন নং:</span> {{ $tx->party->phone }}</p>@endif
                @if($balance !== null && $v->khataSetting('show_balance'))
                <p><span class="inline-block w-20 text-gray-600">ব্যালেন্স:</span> {{ $tk(abs($balance)) }} {{ $balance > 0 ? '(পাওনা)' : ($balance < 0 ? '(বকেয়া)' : '') }}</p>
                @endif
            @elseif($tx->category)
                <p><span class="inline-block w-20 text-gray-600">খাত:</span> <b>{{ $tx->category }}</b></p>
            @endif
        </div>
        <div class="space-y-1 text-right sm:text-left">
            @if($tx->number)<p><span class="inline-block w-28 text-gray-600">{{ $isTrade ? 'চালান নং:' : 'রসিদ নং:' }}</span> <b>{{ $tx->number }}</b></p>@endif
            <p><span class="inline-block w-28 text-gray-600">তারিখ:</span> {{ \App\Support\BanglaNumber::digits($tx->date->format('d')) }} {{ ['','জানু','ফেব্রু','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্ট','অক্টো','নভে','ডিসে'][(int) $tx->date->format('n')] }} {{ \App\Support\BanglaNumber::digits($tx->date->format('Y')) }}</p>
            <p><span class="inline-block w-28 text-gray-600">পেমেন্ট মোড:</span> {{ $tx->modeLabel() }}</p>
        </div>
    </div>

    @if($tx->lines->isNotEmpty())
    <div class="mt-5 overflow-hidden rounded-xl border border-gray-200">
        <table class="w-full text-sm">
            <thead class="bg-[#0891b2] text-white">
                <tr><th class="p-2 text-left w-14">ক্রমিক</th><th class="p-2 text-left">নাম</th><th class="p-2 text-right">পরিমাণ</th><th class="p-2 text-right">মূল্য</th><th class="p-2 text-right">মোট</th></tr>
            </thead>
            <tbody>
                @foreach($tx->lines as $i => $line)
                <tr class="border-t border-gray-200">
                    <td class="p-2">{{ $i + 1 }}</td>
                    <td class="p-2">{{ $line->name }}</td>
                    <td class="p-2 text-right num">{{ $q3($line->quantity) }} {{ \App\Models\Khata\KhataItem::UNITS[$line->unit] ?? $line->unit }}</td>
                    <td class="p-2 text-right num">{{ $tk($line->price) }}</td>
                    <td class="p-2 text-right num">{{ $tk($line->total) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="mt-6 grid sm:grid-cols-2 gap-6">
        <div>
            <p class="font-bold">কথায় পরিমাণ</p>
            <p class="mt-1">{{ \App\Support\BanglaNumber::taka((float) $tx->total) }}</p>
            @if($tx->note)<p class="mt-3 text-sm text-gray-600">নোট: {{ $tx->note }}</p>@endif
            @if($terms !== '')
                <p class="font-bold mt-4">শর্ত এবং নিয়ম</p>
                <p class="mt-1 whitespace-pre-line text-sm">{{ $terms }}</p>
            @endif
        </div>
        <div class="text-sm space-y-1.5">
            @if($isTrade)
                <div class="flex justify-between font-semibold"><span>উপমোট:</span><span class="num">{{ $tk($tx->subtotal) }}</span></div>
                @if((float) $tx->discount > 0)<div class="flex justify-between"><span>ডিসকাউন্ট:</span><span class="num">− {{ $tk($tx->discount) }}</span></div>@endif
                @if((float) $tx->extra_charge > 0)<div class="flex justify-between"><span>{{ $tx->extra_label ?: 'অতিরিক্ত চার্জ' }}</span><span class="num">{{ $tk($tx->extra_charge) }}</span></div>@endif
                <div class="flex justify-between"><span>মোট পরিমাণ:</span><span class="num">{{ $tk($tx->total) }}</span></div>
                <div class="flex justify-between font-semibold"><span>{{ $tx->type === 'sale' ? 'প্রাপ্ত পরিমাণ:' : 'পরিশোধিত:' }}</span><span class="num">{{ $tk($tx->paid) }}</span></div>
                <div class="flex justify-between text-xl font-bold border-y border-dashed border-gray-300 py-2 mt-2"><span>বকেয়া পরিমাণ</span><span class="num">{{ $tk($tx->due()) }}</span></div>
            @else
                <div class="flex justify-between text-xl font-bold border-y border-dashed border-gray-300 py-2"><span>পরিমাণ</span><span class="num">{{ $tk($tx->total) }}</span></div>
            @endif
        </div>
    </div>
</div>
