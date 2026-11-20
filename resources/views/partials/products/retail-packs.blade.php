@if($retailPrices->isEmpty())
<p class="pe-note">খুচরা চালু রেখে সংরক্ষণ করলে ২৫, ৫০, ১০০, ২৫০, ৫০০ ও ১০০০ গ্রামের প্যাক তৈরি হবে। এরপর ম্যানুয়াল দাম নির্ধারণ করতে পারবেন।</p>
@else
<div class="pe-table-wrap" tabindex="0" role="region" aria-label="খুচরা প্যাকের দাম">
    <table class="pe-table">
        <thead><tr><th scope="col">প্যাক</th><th scope="col">হিসাব করা দাম</th><th scope="col">ম্যানুয়াল দাম (৳)</th><th scope="col">ম্যানুয়াল?</th><th scope="col">সক্রিয়?</th><th scope="col">বর্তমান দাম</th></tr></thead>
        <tbody>@foreach($retailPrices as $price)
        <tr>
            <th scope="row">{{ $price->label }}<small>{{ $price->quantity_gram }}g</small></th>
            <td>৳{{ $price->auto_price }}</td>
            <td><input aria-label="{{ $price->label }} ম্যানুয়াল দাম" type="number" name="prices[{{ $price->id }}][manual_price]" value="{{ old('prices.'.$price->id.'.manual_price', $price->manual_price) }}" min="0.01" step="0.01" max="99999999.99"></td>
            @foreach(['is_manual_override'=>'ম্যানুয়াল দাম ব্যবহার', 'is_active'=>'সক্রিয়'] as $field=>$label)
            <td><input type="hidden" name="prices[{{ $price->id }}][{{ $field }}]" value="0"><input aria-label="{{ $price->label }} {{ $label }}" type="checkbox" name="prices[{{ $price->id }}][{{ $field }}]" value="1" @checked(old('prices.'.$price->id.'.'.$field, $price->{$field}))></td>
            @endforeach
            <td><strong>৳{{ $price->final_price }}</strong></td>
        </tr>
        @endforeach</tbody>
    </table>
</div>
<small>ভিত্তিমূল্য বদলালে ম্যানুয়াল দাম অপরিবর্তিত থাকবে। নতুন হিসাব সংরক্ষণের পর দেখা যাবে।</small>
<div class="pe-note" style="margin-top:14px">
    <strong>নতুন প্যাক সাইজ যোগ করুন</strong> — ক্রেতা শুধু এই তালিকার সক্রিয় প্যাকই কিনতে পারবে (কম-বেশি পরিমাণ নয়)। দাম ফাঁকা রাখলে ১ কেজির ভিত্তিমূল্য থেকে হিসাব হবে; অপ্রয়োজনীয় সাইজ উপরে "সক্রিয়" টিক তুলে বন্ধ করুন।
    @for($i = 0; $i < 3; $i++)
    <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
        <input type="number" name="new_packs[{{ $i }}][grams]" value="{{ old('new_packs.'.$i.'.grams') }}" min="1" max="100000" step="1" placeholder="গ্রাম (যেমন ১৫০)" aria-label="নতুন প্যাকের গ্রাম" style="max-width:170px">
        <input type="number" name="new_packs[{{ $i }}][manual_price]" value="{{ old('new_packs.'.$i.'.manual_price') }}" min="0.01" step="0.01" placeholder="দাম ৳ (ঐচ্ছিক)" aria-label="নতুন প্যাকের দাম" style="max-width:170px">
        @error('new_packs.'.$i.'.grams')<span class="pe-error" style="color:#b91c1c;font-size:12px">{{ $message }}</span>@enderror
    </div>
    @endfor
</div>
@endif
