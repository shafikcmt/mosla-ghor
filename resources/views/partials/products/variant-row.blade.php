@php
    $field = $group.'['.$key.']';
    $defaultKey = ($group === 'variants' ? 'existing:' : 'new:').$key;
    $attributes = $row['attributes'] ?? [];
    $attributes = $attributes ?: [['name'=>'', 'value'=>'']];
@endphp
<div class="pe-variant" data-variant data-field="{{ $field }}">
    <div class="pe-grid">
        <label>ভ্যারিয়েন্ট নাম<input name="{{ $field }}[name]" value="{{ $row['name'] ?? '' }}" maxlength="255"></label>
        <label>SKU<input name="{{ $field }}[sku]" value="{{ $row['sku'] ?? '' }}" maxlength="100"></label>
        @foreach(['retail_price'=>'দাম (৳)', 'sale_price'=>'অফার দাম (৳)', 'stock'=>'স্টক'] as $column=>$label)
        <label>{{ $label }}<input type="number" name="{{ $field }}[{{ $column }}]" value="{{ $row[$column] ?? '' }}" step="{{ $column === 'stock' ? '1' : '0.01' }}" min="{{ $column === 'stock' ? '0' : '0.01' }}"><small>খালি রাখলে মূল পণ্যের মান ব্যবহার হবে।</small></label>
        @endforeach
        <div data-media-preview>
            <img data-preview class="pe-main-preview" src="{{ $variant?->imageUrl() }}" alt="ভ্যারিয়েন্টের বর্তমান ছবি" @if(!$variant?->image) hidden @endif>
            <label>ভ্যারিয়েন্ট ছবি<input type="file" name="{{ $field }}[image_file]" accept="image/jpeg,image/png,image/webp" data-preview-input></label>
            @if($variant?->image)<label class="pe-check"><input type="checkbox" name="{{ $field }}[remove_image]" value="1" @checked($row['remove_image'] ?? false)> ছবি মুছুন</label>@endif
        </div>
    </div>
    <div data-attributes class="pe-attributes">
        @foreach($attributes as $index=>$attribute)
        <div class="pe-attribute">
            <label>বৈশিষ্ট্য (Brand / Size / Weight)<input name="{{ $field }}[attributes][{{ $index }}][name]" value="{{ $attribute['name'] ?? '' }}" maxlength="40"></label>
            <label>মান<input name="{{ $field }}[attributes][{{ $index }}][value]" value="{{ $attribute['value'] ?? '' }}" maxlength="80"></label>
            <button type="button" class="pe-button pe-secondary" data-attribute-remove>বৈশিষ্ট্য সরান</button>
        </div>
        @endforeach
    </div>
    <button type="button" class="pe-button pe-secondary" data-attribute-add>+ বৈশিষ্ট্য</button>
    <label class="pe-check"><input type="hidden" name="{{ $field }}[is_active]" value="0"><input type="checkbox" name="{{ $field }}[is_active]" value="1" @checked($row['is_active'] ?? true)> সক্রিয়</label>
    <label class="pe-check"><input type="radio" name="default_variant" value="{{ $defaultKey }}" @checked(old('default_variant', $variant?->is_default ? $defaultKey : '') === $defaultKey)> ডিফল্ট</label>
    @if($variant)
    <label class="pe-check"><input type="checkbox" name="{{ $field }}[_delete]" value="1" @checked($row['_delete'] ?? false)> নিষ্ক্রিয় করুন (তথ্য সংরক্ষিত থাকবে)</label>
    @else
    <button type="button" class="pe-button pe-secondary" data-variant-remove>নতুন ভ্যারিয়েন্ট সরান</button>
    @endif
</div>
