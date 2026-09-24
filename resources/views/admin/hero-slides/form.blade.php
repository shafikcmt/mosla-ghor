@extends('admin.layout')
@section('title', $slide->exists ? 'Edit Hero Slide' : 'New Hero Slide')
@section('content')
<h1 class="text-xl font-bold mb-5">{{ $slide->exists ? 'স্লাইড সম্পাদনা' : 'নতুন স্লাইড' }}</h1>
<form method="POST" enctype="multipart/form-data" action="{{ $slide->exists ? route('admin.hero-slides.update', $slide) : route('admin.hero-slides.store') }}" class="bg-white border rounded p-6 max-w-4xl space-y-5">
    @csrf
    @if($slide->exists) @method('PUT') @endif
    @foreach(['title'=>'শিরোনাম *', 'eyebrow'=>'ছোট লেবেল (ঐচ্ছিক)', 'subtitle'=>'সংক্ষিপ্ত বিবরণ (ঐচ্ছিক)'] as $field=>$label)
    <div><label for="{{ $field }}" class="block text-sm mb-2">{{ $label }}</label>
    @if($field === 'subtitle')<textarea id="{{ $field }}" name="{{ $field }}" rows="3" maxlength="500" class="w-full border rounded px-3 py-2">{{ old($field, $slide->$field) }}</textarea>
    @else<input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $slide->$field) }}" maxlength="{{ $field === 'title' ? 200 : 100 }}" @required($field === 'title') class="w-full border rounded px-3 py-2">@endif
    @error($field)<p class="text-red-600 text-sm">{{ $message }}</p>@enderror</div>
    @endforeach
    <div><label for="image" class="block text-sm mb-2">স্লাইডের ছবি {{ $slide->exists ? '' : '*' }}</label>
        @if($slide->exists)<img src="{{ \App\Support\ProductMedia::url($slide->image_path) }}" alt="{{ $slide->title }}" class="h-32 object-contain mb-3">@endif
        <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" @required(!$slide->exists) class="max-w-full">
        <p class="text-xs text-gray-500 mt-2">JPG, PNG বা WebP, সর্বোচ্চ ৫ MB। নতুন ছবি না দিলে আগেরটি থাকবে।</p>
        @error('image')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>
    @foreach(['primary'=>'প্রধান বাটন', 'secondary'=>'দ্বিতীয় বাটন'] as $prefix=>$label)
    <fieldset class="border rounded p-4"><legend class="text-sm px-2">{{ $label }} (ঐচ্ছিক)</legend><div class="grid sm:grid-cols-2 gap-4">
        @foreach(['label'=>'লেবেল', 'url'=>'লিংক'] as $suffix=>$fieldLabel)
        @php($field = $prefix.'_'.$suffix)
        <div><label for="{{ $field }}" class="block text-sm mb-2">{{ $fieldLabel }}</label><input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $slide->$field) }}" maxlength="{{ $suffix === 'url' ? 300 : 60 }}" class="w-full border rounded px-3 py-2">
        @error($field)<p class="text-red-600 text-sm">{{ $message }}</p>@enderror</div>
        @endforeach
    </div><p class="text-xs text-gray-500 mt-2">লেবেল ও লিংক একসাথে দিন। /#products অথবা https:// দিয়ে শুরু করুন।</p></fieldset>
    @endforeach
    <div class="flex flex-wrap items-center gap-6"><div><label for="sort_order" class="block text-sm mb-2">ক্রম</label><input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $slide->sort_order ?? 0) }}" min="0" max="10000" required class="border rounded px-3 py-2 w-28">@error('sort_order')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror</div>
    <input type="hidden" name="is_active" value="0"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $slide->is_active))> সক্রিয় রাখুন</label></div>
    <div class="flex gap-4 items-center"><button type="submit" class="bg-gray-800 text-white px-5 py-2 rounded">সংরক্ষণ করুন</button><a href="{{ route('admin.hero-slides.index') }}" class="underline">ফিরে যান</a></div>
</form>
@endsection
