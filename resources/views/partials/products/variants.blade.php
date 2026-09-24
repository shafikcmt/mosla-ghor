@php $savedVariants = $product?->variants ?? collect(); @endphp
<p class="pe-note">নাম অথবা বৈশিষ্ট্য দিন। সংরক্ষিত ভ্যারিয়েন্ট নিষ্ক্রিয় করলে পুরোনো অর্ডার, স্টক ও ছবি থাকবে।</p>
<div data-variant-list>
    @foreach($savedVariants as $variant)
        @include('partials.products.variant-row', ['group'=>'variants', 'key'=>$variant->id, 'row'=>old('variants.'.$variant->id, $variant->toArray()), 'variant'=>$variant])
    @endforeach
    @foreach(old('new_variants', []) as $key=>$row)
        @include('partials.products.variant-row', ['group'=>'new_variants', 'key'=>$key, 'row'=>$row, 'variant'=>null])
    @endforeach
</div>
<template data-variant-template>
    @include('partials.products.variant-row', ['group'=>'new_variants', 'key'=>'__INDEX__', 'row'=>['is_active'=>true], 'variant'=>null])
</template>
<button type="button" class="pe-button pe-secondary" data-variant-add>+ নতুন ভ্যারিয়েন্ট</button>
