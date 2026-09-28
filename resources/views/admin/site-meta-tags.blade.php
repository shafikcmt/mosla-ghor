@extends('admin.layout')

@section('title', 'সাইট ভেরিফিকেশন ও মেটা ট্যাগ')

@section('content')

<h1 class="text-xl font-bold text-gray-800 mb-2">সাইট ভেরিফিকেশন ও মেটা ট্যাগ</h1>
<p class="text-sm text-gray-500 mb-6">Facebook, Google, Bing ইত্যাদির ডোমেইন ভেরিফিকেশন ট্যাগ এখান থেকে যোগ করুন। এগুলো ওয়েবসাইটের সব পেজের &lt;head&gt;-এ বসবে (মেইনটেন্যান্স চলাকালীনও) — অ্যাডমিন/ভেন্ডর প্যানেলে নয়। শুধু নাম ও মান দিন; ট্যাগটি সাইট নিজে তৈরি করে।</p>

{{-- ── Existing tags ─────────────────────────────────────────────── --}}
<div class="bg-white rounded shadow-sm border border-gray-100 mb-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-sm font-semibold text-gray-600">যোগ করা ট্যাগ</h2>
    </div>
    @if($tags->isEmpty())
        <p class="px-6 py-5 text-sm text-gray-400">এখনও কোনো ট্যাগ নেই। নিচের ফর্ম থেকে যোগ করুন।</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">লেবেল</th>
                    <th class="px-4 py-3 text-left">যে ট্যাগ বসবে</th>
                    <th class="px-4 py-3 text-left">অবস্থা</th>
                    <th class="px-4 py-3 text-right">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($tags as $tag)
                <tr>
                    <td class="px-4 py-3 text-gray-800">{{ $tag->label ?: '—' }}</td>
                    <td class="px-4 py-3"><code class="font-mono text-xs text-gray-700" style="word-break:break-all">{{ $tag->preview() }}</code></td>
                    <td class="px-4 py-3 {{ $tag->is_active ? 'text-green-700' : 'text-gray-500' }}">{{ $tag->is_active ? 'চালু' : 'বন্ধ' }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('admin.site-meta-tags.index', ['edit' => $tag->id]) }}#tag-form" class="text-xs font-medium text-blue-600 mr-3">সম্পাদনা</a>
                        <form method="POST" action="{{ route('admin.site-meta-tags.toggle', $tag) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-xs font-medium mr-3 {{ $tag->is_active ? 'text-red-600' : 'text-green-700' }}">{{ $tag->is_active ? 'বন্ধ করুন' : 'চালু করুন' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.site-meta-tags.destroy', $tag) }}" class="inline"
                              onsubmit="return confirm('এই মেটা ট্যাগটি মুছে ফেলবেন? ভেরিফিকেশন ট্যাগ মুছলে ভেরিফিকেশন বাতিল হতে পারে।')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-red-500">মুছুন</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ── Smart paste ───────────────────────────────────────────────── --}}
<div class="bg-white rounded shadow-sm border border-gray-100 mb-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-sm font-semibold text-gray-600">দ্রুত যোগ: পুরো ট্যাগ পেস্ট করুন</h2>
    </div>
    <form method="POST" action="{{ route('admin.site-meta-tags.parse') }}" class="px-6 py-5">
        @csrf
        @if($editing)<input type="hidden" name="edit" value="{{ $editing->id }}">@endif
        <label class="block text-xs font-medium text-gray-600 mb-1" for="paste">Meta/Google যে ট্যাগ দিয়েছে সেটি এখানে পেস্ট করুন</label>
        <textarea id="paste" name="paste" rows="2" maxlength="1000"
                  placeholder='<meta name="facebook-domain-verification" content="xxxxxxxxxxxx" />'
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-gray-400"></textarea>
        <p class="text-gray-400 text-xs mt-1">শুধু name/property ও content নেওয়া হবে এবং নিচের ফর্মে বসবে — পেস্ট করা লেখা সংরক্ষণ হয় না। একটির বেশি ট্যাগ বা অন্য কোনো কোড গ্রহণ করা হবে না।</p>
        @error('paste')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        <button type="submit" class="mt-3 bg-gray-800 text-white px-5 py-2 rounded text-sm font-medium hover:bg-gray-700">ফর্মে বসান</button>
    </form>
</div>

{{-- ── Add / edit form ───────────────────────────────────────────── --}}
@php
    $f = fn ($key, $default = '') => old($key, $editing?->{$key} ?? $default);
    $preset = old('preset', $editing ? \App\Support\MetaTagRules::presetFor($editing->name) : 'facebook');
    $isActive = (string) old('is_active', $editing ? ($editing->is_active ? '1' : '0') : '1') === '1';
@endphp
<form id="tag-form" method="POST"
      action="{{ $editing ? route('admin.site-meta-tags.update', $editing) : route('admin.site-meta-tags.store') }}"
      class="bg-white rounded shadow-sm border border-gray-100 mb-6" data-meta-form>
    @csrf
    @if($editing) @method('PUT') @endif
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-sm font-semibold text-gray-600">{{ $editing ? 'ট্যাগ সম্পাদনা' : 'নতুন ট্যাগ যোগ করুন' }}</h2>
    </div>
    <div class="px-6 py-5 space-y-5">
        @if($errors->hasAny(['label', 'attribute', 'name', 'content', 'sort_order']))
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-4 py-3">
                @foreach(['attribute', 'name', 'content', 'label', 'sort_order'] as $k)@error($k)<p>{{ $message }}</p>@enderror @endforeach
            </div>
        @endif

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1" for="preset">সার্ভিস</label>
            <select id="preset" name="preset" data-preset
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                @foreach(\App\Support\MetaTagRules::PRESETS as $key => [$label, $attr, $name])
                    <option value="{{ $key }}" data-attribute="{{ $attr }}" data-name="{{ $name }}" data-label="{{ $label }}" @selected($preset === $key)>{{ $label }} ({{ $name }})</option>
                @endforeach
                <option value="custom" @selected($preset === 'custom')>Custom (নিজে নাম দিন)</option>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="label">লেবেল <span class="text-gray-400">(শুধু আপনার জন্য)</span></label>
                <input id="label" name="label" maxlength="100" value="{{ $f('label') }}" placeholder="যেমন: Facebook ভেরিফিকেশন"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="attribute">ট্যাগের ধরন</label>
                <select id="attribute" name="attribute" data-field
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    @foreach(\App\Support\MetaTagRules::ATTRIBUTES as $attr)
                        <option value="{{ $attr }}" @selected($f('attribute', 'name') === $attr)>{{ $attr }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1" for="name">নাম (name / property)</label>
            <input id="name" name="name" maxlength="100" value="{{ $f('name', 'facebook-domain-verification') }}" data-field
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-gray-400">
            <p class="text-gray-400 text-xs mt-1">শুধু ইংরেজি অক্ষর, সংখ্যা এবং : . _ -। description, keywords, robots, viewport, og:*, twitter:* সাইট নিজেই দেয় — এগুলো দেওয়া যাবে না।</p>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1" for="content">Content (মান)</label>
            <input id="content" name="content" maxlength="500" value="{{ $f('content') }}" data-field placeholder="যেমন: 66ubp1k34fbb0nykwwf1rhhftbxij9"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-gray-400">
            <p class="text-gray-400 text-xs mt-1">শুধু content="…" এর ভেতরের মানটি দিন। &lt; বা &gt; চিহ্ন দেওয়া যাবে না।</p>
        </div>

        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="sort_order">ক্রম</label>
                <input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ $f('sort_order', 0) }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            </div>
            <div>
                <span class="block text-xs font-medium text-gray-600 mb-1">অবস্থা</span>
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-3 cursor-pointer" style="min-height:40px">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" @checked($isActive)>
                    <span class="text-sm text-gray-700">চালু (ওয়েবসাইটে দেখাবে)</span>
                </label>
            </div>
        </div>

        <div>
            <span class="block text-xs font-medium text-gray-600 mb-1">প্রিভিউ — ঠিক এই ট্যাগটি সাইটের &lt;head&gt;-এ বসবে</span>
            <code data-preview class="block font-mono text-xs text-gray-800 bg-gray-50 border border-gray-100 rounded px-3 py-2" style="word-break:break-all"></code>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-gray-800 text-white px-8 py-2.5 rounded text-sm font-medium hover:bg-gray-700">{{ $editing ? 'আপডেট করুন' : 'সংরক্ষণ করুন' }}</button>
            @if($editing)<a href="{{ route('admin.site-meta-tags.index') }}" class="px-4 py-2.5 text-sm text-gray-600">বাতিল</a>@endif
        </div>
    </div>
</form>

<script>
(function () {
    var form = document.querySelector('[data-meta-form]');
    if (!form) return;
    var preset = form.querySelector('[data-preset]');
    var attr = form.querySelector('[name="attribute"]');
    var name = form.querySelector('[name="name"]');
    var content = form.querySelector('[name="content"]');
    var label = form.querySelector('[name="label"]');
    var preview = form.querySelector('[data-preview]');
    // Mirrors e(): the preview text is set via textContent, so nothing here is parsed as HTML.
    var esc = function (s) { return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); };
    function render() {
        preview.textContent = '<meta ' + (attr.value === 'property' ? 'property' : 'name') + '="' + esc(name.value.trim()) + '" content="' + esc(content.value.trim()) + '">';
    }
    preset.addEventListener('change', function () {
        var opt = preset.options[preset.selectedIndex];
        if (opt.value !== 'custom') {
            attr.value = opt.dataset.attribute;
            name.value = opt.dataset.name;
            if (!label.value.trim()) label.value = opt.dataset.label;
        } else {
            name.focus();
        }
        render();
    });
    form.addEventListener('input', render);
    form.addEventListener('change', render);
    render();
})();
</script>

@endsection
