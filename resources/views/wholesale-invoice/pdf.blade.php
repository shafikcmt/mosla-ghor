<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<style>
    /*
     * IMPORTANT — Bengali + mpdf: the bundled FreeSerif (auto-selected for
     * Bengali) has NO glyphs in its BOLD face, so any bold Bengali (or the ৳
     * sign) renders as tofu boxes. So we NEVER use font-weight:bold here — not
     * even the <th> default. Emphasis is done with font-size, colour and
     * borders instead. Verified by rasterising and inspecting the output.
     */
    body   { font-size: 11pt; color: #1a1a1a; font-weight: normal; }
    h1, h2, h3, p, table, th, td { margin: 0; padding: 0; font-weight: normal; }
    .muted { color: #666; font-size: 9pt; }
    .header {
        background-color: #1a1a1a; color: #ffffff;
        padding: 14px 16px;
    }
    .header .shop  { color: #c9a227; font-size: 16pt; }
    .header .site  { color: #cfcfcf; font-size: 9pt; }
    .meta-right    { text-align: right; }
    .section       { padding: 10px 4px; border-bottom: 1px solid #e5e7eb; }
    .prod-name     { font-size: 12pt; }
    .badge         { font-size: 9.5pt; padding: 2px 8px; border: 1px solid #cbd5e1; color: #334155; }
    table.items    { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.items th { text-align: left; font-size: 9pt; color: #666; border-bottom: 1px solid #cbd5e1; padding: 6px 4px; }
    table.items td { padding: 6px 4px; border-bottom: 1px solid #eee; }
    .right         { text-align: right; }
    table.totals   { width: 48%; border-collapse: collapse; margin-top: 8px; margin-left: 52%; }
    table.totals td{ padding: 3px 4px; font-size: 10.5pt; }
    .grand td      { border-top: 2px solid #1a1a1a; font-size: 13.5pt; color: #0f3d22; padding-top: 6px; }
    .terms         { margin-top: 12px; }
    .terms .lbl    { color: #666; font-size: 9pt; }
    .note-box      { background-color: #f5f5f5; padding: 8px 10px; margin-top: 8px; }
    .foot          { margin-top: 18px; text-align: center; color: #999; font-size: 8.5pt; }
</style>
</head>
<body>

@php
    $statusLabels = \App\Models\WholesaleQuote::statuses();
    $statusText   = $statusLabels[$quote->status] ?? $quote->statusLabel();
    $productLabel = $quote->enquiry?->productLabel() ?? $quote->enquiry?->product_name ?? '—';
    $qty          = rtrim(rtrim(number_format((float) $quote->quantity, 2), '0'), '.');
@endphp

<table width="100%" class="header"><tr>
    <td>
        <div class="shop">{{ $vendor->shop_name ?? $siteName }}</div>
        @if($vendor?->phone)<div class="site">{{ $vendor->phone }}</div>@endif
        @if($vendor?->address)<div class="site">{{ $vendor->address }}</div>@endif
        @if(! $vendor)<div class="site">{{ $siteName }}</div>@endif
    </td>
    <td class="meta-right">
        <div class="site">কোটেশন</div>
        <div style="font-size:13pt;">#{{ $quote->id }}</div>
        <div class="site">{{ $quote->created_at->format('d M Y') }}</div>
    </td>
</tr></table>

<table width="100%" class="section"><tr>
    <td>
        <div class="muted">পণ্য</div>
        <div class="prod-name">{{ $productLabel }}</div>
    </td>
    <td class="right">
        <span class="badge">{{ $statusText }}</span>
    </td>
</tr></table>

<table class="items">
    <thead>
        <tr>
            <th>পণ্য</th>
            <th class="right">পরিমাণ</th>
            <th class="right">ইউনিট মূল্য</th>
            <th class="right">সাবটোটাল</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $productLabel }}</td>
            <td class="right">{{ $qty }} {{ $quote->quantity_unit }}</td>
            <td class="right">৳{{ number_format($quote->unit_price, 2) }}</td>
            <td class="right">৳{{ number_format($quote->subtotal, 2) }}</td>
        </tr>
    </tbody>
</table>

<table class="totals">
    <tr><td>সাবটোটাল</td><td class="right">৳{{ number_format($quote->subtotal, 2) }}</td></tr>
    <tr><td>ডেলিভারি চার্জ</td><td class="right">৳{{ number_format($quote->delivery_charge, 2) }}</td></tr>
    @if($quote->advanceAmount() > 0)
    <tr>
        <td>অগ্রিম@if($quote->advance_percentage) ({{ rtrim(rtrim(number_format($quote->advance_percentage, 2), '0'), '.') }}%)@endif</td>
        <td class="right">৳{{ number_format($quote->advanceAmount(), 2) }}</td>
    </tr>
    @endif
    <tr class="grand"><td>সর্বমোট</td><td class="right">৳{{ number_format($quote->grandTotal(), 2) }}</td></tr>
</table>

<div class="terms">
    @if($quote->delivery_time)
    <p><span class="lbl">ডেলিভারি সময়:</span> {{ $quote->delivery_time }}</p>
    @endif
    @if($quote->valid_until)
    <p><span class="lbl">বৈধতা:</span> {{ \Carbon\Carbon::parse($quote->valid_until)->format('d M Y') }}</p>
    @endif
    @if($quote->payment_options)
    <p><span class="lbl">পেমেন্ট শর্ত:</span> {{ implode(', ', (array) $quote->payment_options) }}</p>
    @endif
    @if($quote->note)
    <div class="note-box">
        <span class="lbl">নোট:</span> {{ $quote->note }}
    </div>
    @endif
</div>

<div class="foot">{{ $siteName }} — MoslaMart</div>

</body>
</html>
