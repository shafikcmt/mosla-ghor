{{--
    Printable voucher (বিক্রয় / ক্রয় বিবরণ, or a payment receipt). Mobile-first: items are
    cards on phones and a table from sm up. Also rendered to an image/PDF for WhatsApp.
    Needs $tx (lines, party, vendor loaded) and $balance (party, nullable).
--}}
@php
    $v       = $tx->vendor;
    $tk      = fn ($n) => '৳'.number_format((float) $n, 0);
    $isTrade = in_array($tx->type, ['sale', 'purchase'], true);
    $title   = ['sale' => 'বিক্রয় বিবরণ', 'purchase' => 'ক্রয় বিবরণ', 'payment_in' => 'টাকা প্রাপ্তির রসিদ', 'payment_out' => 'টাকা পরিশোধের রসিদ', 'expense' => 'খরচের রসিদ', 'stock_in' => 'স্টক যোগ', 'stock_out' => 'স্টক কমানো'][$tx->type] ?? 'রসিদ';
    $accent  = ['sale' => 'bg-blue-50 text-blue-700 ring-blue-200', 'purchase' => 'bg-orange-50 text-orange-700 ring-orange-200', 'payment_in' => 'bg-green-50 text-green-700 ring-green-200',
                'payment_out' => 'bg-red-50 text-red-700 ring-red-200', 'expense' => 'bg-amber-50 text-amber-700 ring-amber-200'][$tx->type] ?? 'bg-gray-100 text-gray-700 ring-gray-200';
    $terms   = trim((string) $v->khataSetting('terms'));
    // "Gorai, Mirzapur, , Bangladesh" → "Gorai, Mirzapur, Bangladesh"
    $address = implode(', ', array_filter(array_map('trim', explode(',', (string) $v->address)), fn ($p) => $p !== ''));
    $date    = \App\Support\BanglaNumber::digits($tx->date->format('d')).' '.['','জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'][(int) $tx->date->format('n')].' '.\App\Support\BanglaNumber::digits($tx->date->format('Y'));
    $due     = $tx->due();
    $status  = $tx->statusLabel();
    $statusCls = $due <= 0 ? 'bg-green-100 text-green-800' : ((float) $tx->paid > 0 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-700');
    $showBal = $balance !== null && $v->khataSetting('show_balance') && $tx->party;
@endphp
<div class="voucher bg-white text-gray-900 rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    {{-- Shop header --}}
    <div class="bg-[#0f7a3e] text-white px-5 sm:px-8 py-5 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <p class="text-2xl font-bold leading-tight">{{ $v->shop_name }}</p>
            <p class="text-sm text-green-50/90 mt-1">
                @if($v->phone)<span class="whitespace-nowrap">☎ {{ $v->phone }}</span>@endif
                @if($address)<span class="block sm:inline">@if($v->phone)<span class="hidden sm:inline"> · </span>@endif{{ $address }}</span>@endif
            </p>
        </div>
        @if($v->logo)<img src="{{ asset($v->logo) }}" alt="" class="h-14 w-14 shrink-0 object-contain bg-white rounded-xl p-1">@endif
    </div>

    <div class="px-5 sm:px-8 py-5">
        {{-- Title + number --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xl sm:text-2xl font-bold">{{ $title }}</span>
                @if($status)<span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $statusCls }}">{{ $status }}</span>@endif
            </div>
            @if($tx->number)
            <span class="text-sm font-bold px-3 py-1 rounded-lg ring-1 {{ $accent }}">{{ $isTrade ? 'চালান' : 'রসিদ' }} #{{ $tx->number }}</span>
            @endif
        </div>

        {{-- Party + details --}}
        <div class="mt-4 grid sm:grid-cols-2 gap-3 text-sm">
            <div class="rounded-xl bg-gray-50 px-4 py-3">
                @if($tx->party)
                    <p class="text-xs text-gray-500">{{ $tx->type === 'purchase' || $tx->type === 'payment_out' ? 'সরবরাহকারী' : 'গ্রাহক' }}</p>
                    <p class="font-bold text-base text-gray-900">{{ $tx->party->name }}</p>
                    @if($tx->party->phone)<p class="text-gray-600 num">{{ $tx->party->phone }}</p>@endif
                @elseif($tx->category)
                    <p class="text-xs text-gray-500">খাত</p>
                    <p class="font-bold text-base">{{ $tx->category }}</p>
                @else
                    <p class="text-xs text-gray-500">পার্টি</p>
                    <p class="font-bold text-base">{{ $isTrade ? 'নগদ' : '—' }}</p>
                @endif
            </div>
            <dl class="rounded-xl bg-gray-50 px-4 py-3 space-y-1">
                <div class="flex justify-between gap-3"><dt class="text-gray-500">তারিখ</dt><dd class="font-semibold text-right">{{ $date }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">পেমেন্ট মোড</dt><dd class="font-semibold text-right">{{ $tx->modeLabel() }}</dd></div>
                @if($showBal)
                <div class="flex justify-between gap-3"><dt class="text-gray-500">মোট হিসাব</dt>
                    <dd class="font-semibold text-right whitespace-nowrap {{ $balance > 0 ? 'text-green-700' : ($balance < 0 ? 'text-red-600' : '') }}">{{ $tk(abs($balance)) }} {{ $balance > 0 ? '(পাওনা)' : ($balance < 0 ? '(বকেয়া)' : '') }}</dd></div>
                @endif
            </dl>
        </div>

        {{-- Items: cards on phones, table from sm up --}}
        @if($tx->lines->isNotEmpty())
        <div class="mt-5">
            <div class="sm:hidden space-y-2">
                @foreach($tx->lines as $i => $line)
                <div class="rounded-xl border border-gray-200 px-3 py-2.5 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $i + 1 }}. {{ $line->name }}</p>
                        <p class="text-xs text-gray-500 num">{{ \App\Models\Khata\KhataItem::formatQty($line->quantity, $line->unit) }} × {{ $tk($line->price) }}</p>
                    </div>
                    <p class="font-bold num whitespace-nowrap">{{ $tk($line->total) }}</p>
                </div>
                @endforeach
            </div>
            <div class="hidden sm:block overflow-hidden rounded-xl border border-gray-200">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-3 py-2.5 text-left font-semibold w-12">#</th>
                            <th class="px-3 py-2.5 text-left font-semibold">পণ্য</th>
                            <th class="px-3 py-2.5 text-right font-semibold">পরিমাণ</th>
                            <th class="px-3 py-2.5 text-right font-semibold">দর</th>
                            <th class="px-3 py-2.5 text-right font-semibold">মোট</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($tx->lines as $i => $line)
                        <tr>
                            <td class="px-3 py-2.5 text-gray-500">{{ $i + 1 }}</td>
                            <td class="px-3 py-2.5 font-semibold">{{ $line->name }}</td>
                            <td class="px-3 py-2.5 text-right num whitespace-nowrap">{{ \App\Models\Khata\KhataItem::formatQty($line->quantity, $line->unit) }}</td>
                            <td class="px-3 py-2.5 text-right num whitespace-nowrap">{{ $tk($line->price) }}</td>
                            <td class="px-3 py-2.5 text-right num whitespace-nowrap font-semibold">{{ $tk($line->total) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Totals --}}
        <div class="mt-5 grid sm:grid-cols-2 gap-5 items-start">
            <div class="order-2 sm:order-1 text-sm space-y-3">
                <div>
                    <p class="text-xs text-gray-500">কথায়</p>
                    <p class="font-semibold">{{ \App\Support\BanglaNumber::taka((float) $tx->total) }}</p>
                </div>
                @if($tx->note)<div><p class="text-xs text-gray-500">নোট</p><p>{{ $tx->note }}</p></div>@endif
                @if($terms !== '')
                <div><p class="text-xs text-gray-500">শর্ত এবং নিয়ম</p><p class="whitespace-pre-line text-gray-700">{{ $terms }}</p></div>
                @endif
            </div>
            <div class="order-1 sm:order-2 rounded-xl border border-gray-200 overflow-hidden text-sm">
                <div class="px-4 py-3 space-y-1.5">
                    @if($isTrade)
                        <div class="flex justify-between"><span class="text-gray-600">উপমোট</span><span class="num whitespace-nowrap">{{ $tk($tx->subtotal) }}</span></div>
                        @if((float) $tx->discount > 0)<div class="flex justify-between"><span class="text-gray-600">ডিসকাউন্ট</span><span class="num whitespace-nowrap text-green-700">− {{ $tk($tx->discount) }}</span></div>@endif
                        @if((float) $tx->extra_charge > 0)<div class="flex justify-between"><span class="text-gray-600">{{ $tx->extra_label ?: 'অতিরিক্ত চার্জ' }}</span><span class="num whitespace-nowrap">+ {{ $tk($tx->extra_charge) }}</span></div>@endif
                        <div class="flex justify-between font-bold text-base pt-1 border-t border-gray-100"><span>মোট</span><span class="num whitespace-nowrap">{{ $tk($tx->total) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">{{ $tx->type === 'sale' ? 'টাকা পেয়েছি' : 'টাকা দিয়েছি' }}</span><span class="num whitespace-nowrap">{{ $tk($tx->paid) }}</span></div>
                    @else
                        <div class="flex justify-between font-bold text-base"><span>পরিমাণ</span><span class="num whitespace-nowrap">{{ $tk($tx->total) }}</span></div>
                    @endif
                </div>
                @if($isTrade)
                <div class="flex justify-between items-center px-4 py-3 {{ $due > 0 ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-800' }}">
                    <span class="font-bold">{{ $due > 0 ? 'বাকি' : 'পুরো পরিশোধিত' }}</span>
                    <span class="text-2xl font-bold num whitespace-nowrap">{{ $tk(max(0, $due)) }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="px-5 sm:px-8 py-3 bg-gray-50 border-t border-gray-100 text-center text-xs text-gray-500">ধন্যবাদ — {{ $v->shop_name }}</div>
</div>
