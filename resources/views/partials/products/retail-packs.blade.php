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
@endif
