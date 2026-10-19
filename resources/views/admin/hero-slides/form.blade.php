@extends('admin.layout')
@section('title', $slide->exists ? 'Edit Hero Slide' : 'New Hero Slide')
@section('content')
@php
    // New slides start as picture banners; existing slides keep their layout.
    $mode = old('display_mode', $slide->exists ? ($slide->display_mode ?: 'text') : 'image');
@endphp
<style>.hs-opt{border-color:#e5e7eb}.hs-opt:has(input:checked){border-color:#15803d;background:#f0fdf4}#hs-form [hidden]{display:none!important}</style>
<h1 class="text-xl font-bold mb-5">{{ $slide->exists ? 'স্লাইড সম্পাদনা' : 'নতুন স্লাইড' }}</h1>
<form method="POST" enctype="multipart/form-data" action="{{ $slide->exists ? route('admin.hero-slides.update', $slide) : route('admin.hero-slides.store') }}" class="bg-white border rounded p-6 max-w-4xl space-y-5" id="hs-form">
    @csrf
    @if($slide->exists) @method('PUT') @endif

    <fieldset>
        <legend class="block text-sm font-semibold mb-2">স্লাইডের ধরন</legend>
        <div class="grid sm:grid-cols-2 gap-3">
            <label class="hs-opt flex gap-3 border-2 rounded-lg p-3 cursor-pointer">
                <input type="radio" name="display_mode" value="image" @checked($mode === 'image') class="mt-1">
                <span><span class="block font-semibold">শুধু ছবি (ব্যানার) <span class="text-xs text-green-700">— প্রস্তাবিত</span></span>
                <span class="block text-xs text-gray-500 mt-1">লেখা ও অফার ছবির মধ্যেই ডিজাইন করুন (Canva ইত্যাদি)। পুরো ছবিতে ক্লিক করলে লিংকে যাবে — কোনো বাটন থাকবে না।</span></span>
            </label>
            <label class="hs-opt flex gap-3 border-2 rounded-lg p-3 cursor-pointer">
                <input type="radio" name="display_mode" value="text" @checked($mode === 'text') class="mt-1">
                <span><span class="block font-semibold">লেখা + বাটন</span>
                <span class="block text-xs text-gray-500 mt-1">বাম পাশে শিরোনাম, বিবরণ ও বাটন; ডান পাশে ছবি (আগের ধরন)।</span></span>
            </label>
        </div>
        @error('display_mode')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </fieldset>

    <div><label for="title" class="block text-sm mb-2">শিরোনাম *</label>
        <input id="title" name="title" value="{{ old('title', $slide->title) }}" maxlength="200" required class="w-full border rounded px-3 py-2">
        <p class="text-xs text-gray-500 mt-1" data-hs-mode="image">ব্যানারে লেখা দেখাবে না — Google ও স্ক্রিন-রিডারের জন্য ছবির বিবরণ হিসেবে ব্যবহার হয়। যেমন: “ঈদ অফার — সব মসলায় ১০% ছাড়”।</p>
        @error('title')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror</div>

    <div data-hs-mode="text" class="space-y-5">
    @foreach(['eyebrow'=>'ছোট লেবেল (ঐচ্ছিক)', 'subtitle'=>'সংক্ষিপ্ত বিবরণ (ঐচ্ছিক)'] as $field=>$label)
    <div><label for="{{ $field }}" class="block text-sm mb-2">{{ $label }}</label>
    @if($field === 'subtitle')<textarea id="{{ $field }}" name="{{ $field }}" rows="3" maxlength="500" class="w-full border rounded px-3 py-2">{{ old($field, $slide->$field) }}</textarea>
    @else<input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $slide->$field) }}" maxlength="100" class="w-full border rounded px-3 py-2">@endif
    @error($field)<p class="text-red-600 text-sm">{{ $message }}</p>@enderror</div>
    @endforeach
    </div>

    <div><label for="image" class="block text-sm mb-2"><span data-hs-mode="image">কম্পিউটারের ব্যানার ছবি</span><span data-hs-mode="text">স্লাইডের ছবি</span> {{ $slide->exists ? '' : '*' }}</label>
        @if($slide->exists)<img src="{{ \App\Support\ProductMedia::url($slide->image_path) }}" alt="{{ $slide->title }}" class="h-32 max-w-full object-contain mb-3 rounded border">@endif
        <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" {{ \App\Support\ImageOptimizer::inputAttributes('hero') }} @required(!$slide->exists) class="max-w-full">
        <p class="text-xs text-gray-500 mt-2" data-hs-mode="image"><b>সাইজ: ১৯২০ × ৬০০ px</b> (চওড়া ব্যানার)। জরুরি লেখা মাঝখানে রাখুন — পাশের অংশ ছোট স্ক্রিনে একটু কাটা যেতে পারে।</p>
        <p class="text-xs text-gray-500 mt-2">JPG, PNG বা WebP, সর্বোচ্চ ১০ MB। নতুন ছবি না দিলে আগেরটি থাকবে।</p>
        @error('image')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div data-hs-mode="image" class="space-y-5">
        <div><label for="mobile_image" class="block text-sm mb-2">মোবাইলের ব্যানার ছবি <span class="text-gray-400">(ঐচ্ছিক, প্রস্তাবিত)</span></label>
            @if($slide->mobile_image_path)
                <img src="{{ \App\Support\ProductMedia::url($slide->mobile_image_path) }}" alt="" class="h-32 max-w-full object-contain mb-2 rounded border">
                <label class="inline-flex items-center gap-2 text-sm text-red-600 mb-2"><input type="checkbox" name="remove_mobile_image" value="1"> মোবাইলের ছবি মুছুন</label>
            @endif
            <input id="mobile_image" type="file" name="mobile_image" accept="image/jpeg,image/png,image/webp" {{ \App\Support\ImageOptimizer::inputAttributes('hero') }} class="block max-w-full">
            <p class="text-xs text-gray-500 mt-2"><b>সাইজ: ১০৮০ × ৭২০ px</b>। ফোনে চওড়া ব্যানার খুব সরু দেখায় — তাই ফোনের জন্য আলাদা ছবি দিলে লেখা বড় ও পরিষ্কার দেখাবে। না দিলে কম্পিউটারের ছবিই দেখাবে।</p>
            @error('mobile_image')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>
        <div><label for="link_url" class="block text-sm mb-2">ছবিতে ক্লিক করলে কোথায় যাবে <span class="text-gray-400">(ঐচ্ছিক)</span></label>
            <input id="link_url" name="link_url" value="{{ old('link_url', $slide->link_url) }}" maxlength="300" placeholder="/#products  অথবা  /products/premium-jira  অথবা  https://…" class="w-full border rounded px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">খালি রাখলে ছবিতে ক্লিক করলে কিছু হবে না।</p>
            @error('link_url')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>
    </div>

    <div data-hs-mode="text" class="space-y-5">
    @foreach(['primary'=>'প্রধান বাটন', 'secondary'=>'দ্বিতীয় বাটন'] as $prefix=>$label)
    <fieldset class="border rounded p-4"><legend class="text-sm px-2">{{ $label }} (ঐচ্ছিক)</legend><div class="grid sm:grid-cols-2 gap-4">
        @foreach(['label'=>'লেবেল', 'url'=>'লিংক'] as $suffix=>$fieldLabel)
        @php($field = $prefix.'_'.$suffix)
        <div><label for="{{ $field }}" class="block text-sm mb-2">{{ $fieldLabel }}</label><input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $slide->$field) }}" maxlength="{{ $suffix === 'url' ? 300 : 60 }}" class="w-full border rounded px-3 py-2">
        @error($field)<p class="text-red-600 text-sm">{{ $message }}</p>@enderror</div>
        @endforeach
    </div><p class="text-xs text-gray-500 mt-2">লেবেল ও লিংক একসাথে দিন। /#products অথবা https:// দিয়ে শুরু করুন।</p></fieldset>
    @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-6"><div><label for="sort_order" class="block text-sm mb-2">ক্রম</label><input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $slide->sort_order ?? 0) }}" min="0" max="10000" required class="border rounded px-3 py-2 w-28">@error('sort_order')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror</div>
    <input type="hidden" name="is_active" value="0"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $slide->exists ? $slide->is_active : true))> সক্রিয় রাখুন</label></div>
    <div class="flex gap-4 items-center"><button type="submit" class="bg-gray-800 text-white px-5 py-2 rounded">সংরক্ষণ করুন</button><a href="{{ route('admin.hero-slides.index') }}" class="underline">ফিরে যান</a></div>
</form>
<script>
(function () {
    const form = document.getElementById('hs-form');
    const sync = () => {
        const mode = (form.querySelector('input[name=display_mode]:checked') || {}).value || 'text';
        form.querySelectorAll('[data-hs-mode]').forEach(el => { el.hidden = el.dataset.hsMode !== mode; });
    };
    form.querySelectorAll('input[name=display_mode]').forEach(r => r.addEventListener('change', sync));
    sync();
})();
</script>
@endsection
