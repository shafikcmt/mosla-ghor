<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<style>
    /*
     * IMPORTANT — Bengali + mpdf: the bundled FreeSerif (auto-selected for
     * Bengali) has NO glyphs in its BOLD face, so any bold Bengali (or the ৳
     * sign) renders as tofu boxes. So we NEVER use font-weight:bold here — not
     * even the <th> default. Emphasis is done with font-size, colour, background
     * fills and borders instead. Verified by rasterising and inspecting output.
     *
     * mpdf has weak/no support for flexbox, border-radius and box-shadow, so the
     * whole layout is table-based with solid borders + background-color fills,
     * which mpdf renders reliably.
     */
    body        { font-size: 10.5pt; color: #1f2937; font-weight: normal; line-height: 1.4; }
    table       { border-collapse: collapse; }
    h1, h2, h3, p, table, th, td { margin: 0; padding: 0; font-weight: normal; }
    .muted      { color: #6b7280; font-size: 8.5pt; }
    .right      { text-align: right; }
    .center     { text-align: center; }

    /* ── Letterhead ─────────────────────────────────────────────── */
    .letterhead        { width: 100%; background-color: #1a1a1a; }
    .letterhead td     { padding: 16px 18px; vertical-align: top; }
    .letterhead .shop  { color: #c9a227; font-size: 18pt; }
    .letterhead .line  { color: #d1d5db; font-size: 8.5pt; padding-top: 2px; }
    .letterhead .doc   { color: #c9a227; font-size: 9pt; letter-spacing: 1px; }
    .letterhead .num   { color: #ffffff; font-size: 14pt; padding-top: 2px; }
    .letterhead .date  { color: #9ca3af; font-size: 8.5pt; padding-top: 2px; }
    .accent-band       { width: 100%; background-color: #c9a227; }
    .accent-band td    { height: 4px; line-height: 4px; font-size: 1pt; }

    /* ── Bill To / meta ─────────────────────────────────────────── */
    .info            { width: 100%; margin-top: 16px; }
    .info td         { vertical-align: top; padding: 0; }
    .billbox         { border: 1px solid #e5e7eb; background-color: #fafafa; padding: 10px 12px; }
    .billbox .lbl    { color: #9ca3af; font-size: 8pt; letter-spacing: .5px; }
    .billbox .val    { color: #111827; font-size: 11pt; padding-top: 2px; }
    .billbox .sub    { color: #4b5563; font-size: 9pt; padding-top: 1px; }
    .statuscell      { text-align: right; vertical-align: top; }
    .badge           { display: inline-block; font-size: 9pt; padding: 4px 12px;
                       border: 1px solid #c9a227; color: #7c5e0e; background-color: #fdf6e3; }

    /* ── Items table ────────────────────────────────────────────── */
    table.items         { width: 100%; margin-top: 18px; border: 1px solid #e5e7eb; }
    table.items thead td{ background-color: #1a1a1a; color: #f3f4f6; font-size: 8.5pt;
                          letter-spacing: .5px; padding: 8px 10px; }
    table.items tbody td{ padding: 9px 10px; font-size: 10.5pt; color: #1f2937;
                          border-top: 1px solid #eceff3; }
    table.items .alt td { background-color: #faf8f2; }
    table.items .pname  { color: #111827; }

    /* ── Totals ─────────────────────────────────────────────────── */
    .totals-wrap        { width: 100%; margin-top: 14px; }
    .totals-wrap > td   { vertical-align: top; }
    table.totals        { width: 100%; }
    table.totals td     { padding: 4px 10px; font-size: 10.5pt; color: #374151; }
    table.totals .amt   { text-align: right; color: #111827; }
    table.totals .sep td { border-top: 1px solid #e5e7eb; }
    table.totals .grand td       { border-top: 2px solid #1a1a1a; padding-top: 8px; }
    table.totals .grand .glbl    { font-size: 12pt; color: #1a1a1a; }
    table.totals .grand .gamt    { font-size: 15pt; color: #0f3d22; text-align: right; }

    /* ── Footer / terms ─────────────────────────────────────────── */
    .terms          { width: 100%; margin-top: 20px; background-color: #f7f7f5;
                      border: 1px solid #ececec; }
    .terms td       { padding: 10px 12px; vertical-align: top; font-size: 9.5pt; }
    .terms .lbl     { color: #9ca3af; font-size: 8pt; letter-spacing: .5px; }
    .terms .val     { color: #1f2937; font-size: 10pt; padding-top: 1px; }
    .notebox        { margin-top: 10px; border-left: 3px solid #c9a227;
                      background-color: #fdfcf7; padding: 8px 12px; }
    .notebox .lbl   { color: #9ca3af; font-size: 8pt; }
    .notebox .val   { color: #374151; font-size: 10pt; padding-top: 1px; }
    .foot           { margin-top: 22px; text-align: center; color: #9ca3af; font-size: 8pt;
                      border-top: 1px solid #eee; padding-top: 8px; }
</style>
</head>
<body>

@php
    $statusLabels = \App\Models\WholesaleQuote::statuses();
    $statusText   = $statusLabels[$quote->status] ?? $quote->statusLabel();
    $productLabel = $quote->enquiry?->productLabel() ?? $quote->enquiry?->product_name ?? '—';
    $qty          = rtrim(rtrim(number_format((float) $quote->quantity, 2), '0'), '.');

    // One line today, but built as a list so N line items shade correctly later.
    $lineItems = [[
        'name'     => $productLabel,
        'quantity' => trim($qty . ' ' . $quote->quantity_unit),
        'unit'     => $quote->unit_price,
        'subtotal' => $quote->subtotal,
    ]];

    $enq          = $quote->enquiry;
    $customerName = $enq?->customer_name ?: '—';
    $customerPhone = $enq?->customer_phone;
    $deliveryTo   = $enq?->delivery_location;
@endphp

{{-- ── Letterhead ─────────────────────────────────────────────── --}}
<table class="letterhead"><tr>
    <td>
        <div class="shop">{{ $vendor->shop_name ?? $siteName }}</div>
        @if($vendor?->phone)<div class="line">ফোন: {{ $vendor->phone }}</div>@endif
        @if($vendor?->address)<div class="line">{{ $vendor->address }}</div>@endif
        @if(! $vendor)<div class="line">{{ $siteName }}</div>@endif
    </td>
    <td class="right">
        <div class="doc">কোটেশন / QUOTATION</div>
        <div class="num">#{{ $quote->id }}</div>
        <div class="date">{{ $quote->created_at->format('d M Y') }}</div>
    </td>
</tr></table>
<table class="accent-band"><tr><td></td></tr></table>

{{-- ── Bill To + status ───────────────────────────────────────── --}}
<table class="info"><tr>
    <td width="62%">
        <div class="billbox">
            <div class="lbl">গ্রাহক / BILL TO</div>
            <div class="val">{{ $customerName }}</div>
            @if($customerPhone)<div class="sub">ফোন: {{ $customerPhone }}</div>@endif
            @if($deliveryTo)<div class="sub">ডেলিভারি: {{ $deliveryTo }}</div>@endif
        </div>
    </td>
    <td width="4%"></td>
    <td width="34%" class="statuscell">
        <div class="muted">স্ট্যাটাস</div>
        <div style="padding-top:6px;"><span class="badge">{{ $statusText }}</span></div>
    </td>
</tr></table>

{{-- ── Items ──────────────────────────────────────────────────── --}}
<table class="items">
    <thead>
        <tr>
            <td width="46%">পণ্য / ITEM</td>
            <td width="18%" class="right">পরিমাণ</td>
            <td width="18%" class="right">ইউনিট মূল্য</td>
            <td width="18%" class="right">সাবটোটাল</td>
        </tr>
    </thead>
    <tbody>
        @foreach($lineItems as $i => $item)
        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
            <td class="pname">{{ $item['name'] }}</td>
            <td class="right">{{ $item['quantity'] }}</td>
            <td class="right">৳{{ number_format($item['unit'], 2) }}</td>
            <td class="right">৳{{ number_format($item['subtotal'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ── Totals (right-aligned) ─────────────────────────────────── --}}
<table class="totals-wrap"><tr>
    <td width="52%"></td>
    <td width="48%">
        <table class="totals">
            <tr>
                <td>সাবটোটাল</td>
                <td class="amt">৳{{ number_format($quote->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>ডেলিভারি চার্জ</td>
                <td class="amt">{{ $quote->deliveryChargeLabel() }}</td>
            </tr>
            @if($quote->advanceAmount() > 0)
            <tr class="sep">
                <td>অগ্রিম@if($quote->advance_percentage) ({{ rtrim(rtrim(number_format($quote->advance_percentage, 2), '0'), '.') }}%)@endif</td>
                <td class="amt">৳{{ number_format($quote->advanceAmount(), 2) }}</td>
            </tr>
            @endif
            <tr class="grand">
                <td class="glbl">{{ $quote->deliveryLater() ? 'সর্বমোট (ডেলিভারি চার্জ ছাড়া)' : 'সর্বমোট' }}</td>
                <td class="gamt">৳{{ number_format($quote->grandTotal(), 2) }}</td>
            </tr>
        </table>
    </td>
</tr></table>

{{-- ── Terms / validity / payment ─────────────────────────────── --}}
@if($quote->delivery_time || $quote->valid_until || $quote->payment_options)
<table class="terms"><tr>
    @if($quote->delivery_time)
    <td width="33%">
        <div class="lbl">ডেলিভারি সময়</div>
        <div class="val">{{ $quote->delivery_time }}</div>
    </td>
    @endif
    @if($quote->valid_until)
    <td width="33%">
        <div class="lbl">বৈধতা</div>
        <div class="val">{{ \Carbon\Carbon::parse($quote->valid_until)->format('d M Y') }}</div>
    </td>
    @endif
    @if($quote->payment_options)
    <td width="34%">
        <div class="lbl">পেমেন্ট শর্ত</div>
        <div class="val">{{ implode(', ', (array) $quote->payment_options) }}</div>
    </td>
    @endif
</tr></table>
@endif

@if(! empty($quote->terms))
<div class="notebox">
    <div class="lbl">শর্তাবলী</div>
    @foreach((array) $quote->terms as $term)<div class="val">• {{ $term }}</div>@endforeach
</div>
@endif

@if($quote->note)
<div class="notebox">
    <div class="lbl">নোট</div>
    <div class="val">{{ $quote->note }}</div>
</div>
@endif

<div class="foot">{{ $siteName }} — MoslaMart &nbsp;•&nbsp; ধন্যবাদ / Thank you for your business</div>

</body>
</html>
