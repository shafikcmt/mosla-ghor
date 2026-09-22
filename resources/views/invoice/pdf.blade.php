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
     * whole layout is table-based with solid borders + background-color fills.
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
    .badge           { display: inline-block; font-size: 9pt; padding: 4px 12px; }
    .badge-paid      { border: 1px solid #16a34a; color: #14532d; background-color: #ecfdf3; }
    .badge-partial   { border: 1px solid #d97706; color: #92400e; background-color: #fffbeb; }
    .badge-due       { border: 1px solid #dc2626; color: #991b1b; background-color: #fef2f2; }

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
    table.totals .discount .amt { color: #b91c1c; }
    table.totals .sep td { border-top: 1px solid #e5e7eb; }
    table.totals .grand td       { border-top: 2px solid #1a1a1a; padding-top: 8px; }
    table.totals .grand .glbl    { font-size: 12pt; color: #1a1a1a; }
    table.totals .grand .gamt    { font-size: 15pt; color: #0f3d22; text-align: right; }
    table.totals .paid .amt      { color: #15803d; }
    table.totals .due  .amt      { color: #b91c1c; }

    /* ── Footer / meta ──────────────────────────────────────────── */
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
    $methodLabels = [
        'cash_on_delivery' => 'ক্যাশ অন ডেলিভারি',
        'cash'             => 'ক্যাশ',
        'bkash'            => 'বিকাশ',
        'rocket'           => 'রকেট',
        'nagad'            => 'নগদ',
    ];
    $statusMap = [
        'verified' => ['পরিশোধিত', 'badge-paid'],
        'paid'     => ['পরিশোধিত', 'badge-paid'],
        'partial'  => ['আংশিক পরিশোধিত', 'badge-partial'],
        'pending'  => ['বাকি', 'badge-due'],
        'failed'   => ['ব্যর্থ', 'badge-due'],
    ];
    [$statusText, $statusClass] = $statusMap[$order->payment_status] ?? ['বাকি', 'badge-due'];

    $customerName = $order->customer_name ?: ($order->vendorCustomer?->name ?? '—');
    $addressParts = array_filter([
        $order->division_name, $order->district_name, $order->upazila_name, $order->union_name,
    ]);
    $deliveryArea = implode(', ', array_filter([$order->delivery_location_name, $order->delivery_zone_name]));
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
        <div class="doc">ইনভয়েস / INVOICE</div>
        <div class="num">{{ $order->order_number }}</div>
        <div class="date">{{ $order->created_at->format('d M Y, h:i A') }}</div>
    </td>
</tr></table>
<table class="accent-band"><tr><td></td></tr></table>

{{-- ── Bill To + payment status ───────────────────────────────── --}}
<table class="info"><tr>
    <td width="62%">
        <div class="billbox">
            <div class="lbl">গ্রাহক / BILL TO</div>
            <div class="val">{{ $customerName }}</div>
            @if($order->mobile_number)<div class="sub">ফোন: {{ $order->mobile_number }}</div>@endif
            @if($deliveryArea)<div class="sub">এলাকা: {{ $deliveryArea }}</div>@endif
            @if(count($addressParts))<div class="sub">{{ implode(' › ', $addressParts) }}</div>@endif
            @if($order->full_address)<div class="sub">{{ $order->full_address }}</div>@endif
        </div>
    </td>
    <td width="4%"></td>
    <td width="34%" class="statuscell">
        <div class="muted">পেমেন্ট স্ট্যাটাস</div>
        <div style="padding-top:6px;"><span class="badge {{ $statusClass }}">{{ $statusText }}</span></div>
    </td>
</tr></table>

{{-- ── Items ──────────────────────────────────────────────────── --}}
<table class="items">
    <thead>
        <tr>
            <td width="46%">পণ্য / ITEM</td>
            <td width="18%" class="right">পরিমাণ</td>
            <td width="18%" class="right">ইউনিট মূল্য</td>
            <td width="18%" class="right">মোট</td>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $i => $item)
        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
            <td class="pname">{{ $item->product_name }}@if($item->variant_name) <span class="muted">({{ $item->variant_name }})</span>@endif</td>
            <td class="right">{{ $item->quantityLabel() }}</td>
            <td class="right">৳{{ number_format($item->unit_price, 2) }}</td>
            <td class="right">৳{{ number_format($item->line_total, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ── Totals (right-aligned) ─────────────────────────────────── --}}
<table class="totals-wrap"><tr>
    <td width="48%"></td>
    <td width="52%">
        <table class="totals">
            <tr>
                <td>সাবটোটাল</td>
                <td class="amt">৳{{ number_format($order->subtotal, 2) }}</td>
            </tr>
            @if($order->packaging_cost > 0)
            <tr>
                <td>প্যাকেজিং চার্জ</td>
                <td class="amt">৳{{ number_format($order->packaging_cost, 2) }}</td>
            </tr>
            @endif
            @if($order->delivery_charge > 0)
            <tr>
                <td>ডেলিভারি চার্জ</td>
                <td class="amt">৳{{ number_format($order->delivery_charge, 2) }}</td>
            </tr>
            @endif
            @if($order->discount_amount > 0)
            <tr class="discount">
                <td>ছাড়</td>
                <td class="amt">−৳{{ number_format($order->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if($order->payment_discount > 0)
            <tr class="discount">
                <td>পেমেন্ট ডিসকাউন্ট</td>
                <td class="amt">−৳{{ number_format($order->payment_discount, 2) }}</td>
            </tr>
            @endif
            <tr class="grand">
                <td class="glbl">সর্বমোট</td>
                <td class="gamt">৳{{ number_format($order->grand_total, 2) }}</td>
            </tr>
            @if($order->paid_amount > 0 || $order->due_amount > 0)
            <tr class="paid sep">
                <td>পরিশোধিত</td>
                <td class="amt">৳{{ number_format($order->paid_amount, 2) }}</td>
            </tr>
            <tr class="due">
                <td>বাকি</td>
                <td class="amt">৳{{ number_format($order->due_amount, 2) }}</td>
            </tr>
            @endif
        </table>
    </td>
</tr></table>

{{-- ── Payment meta ───────────────────────────────────────────── --}}
<table class="terms"><tr>
    <td width="33%">
        <div class="lbl">পেমেন্ট পদ্ধতি</div>
        <div class="val">{{ $methodLabels[$order->payment_method] ?? $order->payment_method }}</div>
    </td>
    @if($order->estimated_delivery)
    <td width="34%">
        <div class="lbl">আনুমানিক ডেলিভারি</div>
        <div class="val">{{ $order->estimated_delivery }}</div>
    </td>
    @endif
    @if($order->transaction_id)
    <td width="33%">
        <div class="lbl">ট্রানজেকশন আইডি</div>
        <div class="val">{{ $order->transaction_id }}</div>
    </td>
    @endif
</tr></table>

@if($order->order_note)
<div class="notebox">
    <div class="lbl">বিশেষ নির্দেশনা</div>
    <div class="val">{{ $order->order_note }}</div>
</div>
@endif

<div class="foot">{{ $siteName }} — MoslaMart &nbsp;•&nbsp; ধন্যবাদ / Thank you for your order</div>

</body>
</html>
