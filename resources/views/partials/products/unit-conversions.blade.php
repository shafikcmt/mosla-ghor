{{-- Unit conversion rows: "1 <unit> = <qty> <base>". Blank rows are ignored on save. --}}
@php
    $convRows = array_values((array) old('unit_conversions', $product?->unit_conversions ?? []));
    $convRows = array_pad($convRows, max(3, count($convRows) + 1), []);
    $unitOptions = \App\Models\Product::UNIT_LABELS;
    unset($unitOptions['piece']);
@endphp
<div class="pe-conversions">
    <h3>ইউনিট কনভার্শন <span class="pe-hint">ঐচ্ছিক</span></h3>
    <small>যেমন: ১ কার্টন = ২০ কেজি, ১ ব্যাগ = ২৫ কেজি, ১ কার্টন = ১০ প্যাকেট। কেজিতে পৌঁছানো গেলে পাইকারি দাম থেকে প্রতি কার্টন/ব্যাগের দাম নিজে থেকে হিসাব হবে। দরকার না হলে ঘর খালি রাখুন।</small>
    @foreach($convRows as $i => $row)
    <div class="pe-conversion" data-conversion-row>
        <span>১</span>
        <select name="unit_conversions[{{ $i }}][unit]" aria-label="একক">
            <option value="">— একক —</option>
            @foreach($unitOptions as $u => $label)<option value="{{ $u }}" @selected(($row['unit'] ?? null) === $u)>{{ $label }}</option>@endforeach
        </select>
        <span>=</span>
        <input type="number" name="unit_conversions[{{ $i }}][qty]" value="{{ isset($row['qty']) && $row['qty'] !== '' ? \App\Models\Product::formatQty((float) $row['qty']) : '' }}" min="0" step="0.001" placeholder="পরিমাণ" aria-label="পরিমাণ">
        <select name="unit_conversions[{{ $i }}][base]" aria-label="মূল একক">
            @foreach($unitOptions as $u => $label)<option value="{{ $u }}" @selected(($row['base'] ?? 'kg') === $u)>{{ $label }}</option>@endforeach
        </select>
    </div>
    @error("unit_conversions.$i")<p class="pe-required">{{ $message }}</p>@enderror
    @endforeach
</div>
